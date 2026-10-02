<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\BusAssignment;
use App\Models\BusLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $driver;
    private User $parent;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->admin   = $this->makeUser('admin');
        $this->driver  = $this->makeUser('driver');
        $this->parent  = $this->makeUser('parent');
        $this->teacher = $this->makeUser('teacher');
    }

    // ── Admin: CRUD buses ─────────────────────────────────────────────────────

    public function test_admin_can_list_buses(): void
    {
        Bus::create(['name' => 'Bus A', 'number_plate' => 'KA01AB1234']);
        $this->getJson('/api/bus-tracking/buses', $this->authHeaders($this->admin))
            ->assertOk()->assertJsonCount(1);
    }

    public function test_admin_can_create_bus(): void
    {
        $res = $this->postJson('/api/bus-tracking/buses', [
            'name'         => 'Route 1',
            'number_plate' => 'KA01AB0001',
            'driver_name'  => 'Raju',
            'driver_phone' => '9876543210',
        ], $this->authHeaders($this->admin));

        $res->assertOk()->assertJsonPath('name', 'Route 1');
        $this->assertDatabaseHas('buses', ['name' => 'Route 1']);
    }

    public function test_admin_can_update_bus(): void
    {
        $bus = Bus::create(['name' => 'Old Name']);
        $this->putJson("/api/bus-tracking/buses/{$bus->id}", ['name' => 'New Name'], $this->authHeaders($this->admin))
            ->assertOk()->assertJsonPath('name', 'New Name');
    }

    public function test_admin_can_delete_bus(): void
    {
        $bus = Bus::create(['name' => 'Temp Bus']);
        $this->deleteJson("/api/bus-tracking/buses/{$bus->id}", [], $this->authHeaders($this->admin))
            ->assertStatus(204);
        $this->assertDatabaseMissing('buses', ['id' => $bus->id]);
    }

    public function test_non_admin_cannot_create_bus(): void
    {
        $this->postJson('/api/bus-tracking/buses', ['name' => 'Hack'], $this->authHeaders($this->teacher))
            ->assertStatus(403);
    }

    // ── Admin: assignments ────────────────────────────────────────────────────

    public function test_admin_can_assign_student_to_bus(): void
    {
        $bus = Bus::create(['name' => 'Bus B']);
        $res = $this->postJson("/api/bus-tracking/buses/{$bus->id}/assign", [
            'student_record_id' => 42,
            'pickup_stop'       => 'Main Gate',
        ], $this->authHeaders($this->admin));

        $res->assertOk();
        $this->assertDatabaseHas('bus_assignments', ['bus_id' => $bus->id, 'student_record_id' => 42]);
    }

    public function test_admin_can_unassign_student(): void
    {
        $bus = Bus::create(['name' => 'Bus C']);
        BusAssignment::create(['bus_id' => $bus->id, 'student_record_id' => 10]);

        $this->deleteJson("/api/bus-tracking/buses/{$bus->id}/assign/10", [], $this->authHeaders($this->admin))
            ->assertStatus(204);
        $this->assertDatabaseMissing('bus_assignments', ['bus_id' => $bus->id, 'student_record_id' => 10]);
    }

    public function test_assign_upserts_existing_assignment(): void
    {
        $bus = Bus::create(['name' => 'Bus D']);
        BusAssignment::create(['bus_id' => $bus->id, 'student_record_id' => 5, 'pickup_stop' => 'Stop A']);

        $this->postJson("/api/bus-tracking/buses/{$bus->id}/assign", [
            'student_record_id' => 5,
            'pickup_stop'       => 'Stop B',
        ], $this->authHeaders($this->admin));

        $this->assertDatabaseCount('bus_assignments', 1);
        $this->assertDatabaseHas('bus_assignments', ['pickup_stop' => 'Stop B']);
    }

    // ── Driver: update location ───────────────────────────────────────────────

    public function test_driver_can_update_location(): void
    {
        $bus = Bus::create(['name' => 'Bus E', 'driver_user_id' => $this->driver->id]);

        $res = $this->postJson('/api/bus-tracking/location', [
            'latitude'  => 12.9716,
            'longitude' => 77.5946,
            'speed'     => 40.5,
            'is_active' => true,
        ], $this->authHeaders($this->driver));

        $res->assertOk()->assertJsonPath('bus.id', $bus->id);
        $this->assertDatabaseHas('bus_locations', ['bus_id' => $bus->id, 'is_active' => 1]);
    }

    public function test_driver_without_bus_gets_404(): void
    {
        $this->postJson('/api/bus-tracking/location', [
            'latitude'  => 12.9716,
            'longitude' => 77.5946,
        ], $this->authHeaders($this->driver))->assertStatus(404);
    }

    public function test_driver_location_validates_coordinates(): void
    {
        $this->postJson('/api/bus-tracking/location', [
            'latitude'  => 999,
            'longitude' => 77.5946,
        ], $this->authHeaders($this->driver))->assertStatus(422);
    }

    public function test_driver_can_stop_sharing(): void
    {
        $bus = Bus::create(['name' => 'Bus F', 'driver_user_id' => $this->driver->id]);
        BusLocation::create(['bus_id' => $bus->id, 'latitude' => 12.9, 'longitude' => 77.5, 'is_active' => true]);

        $this->postJson('/api/bus-tracking/stop', [], $this->authHeaders($this->driver))->assertOk();
        $this->assertDatabaseHas('bus_locations', ['bus_id' => $bus->id, 'is_active' => 0]);
    }

    public function test_location_upserts_on_repeated_updates(): void
    {
        $bus = Bus::create(['name' => 'Bus G', 'driver_user_id' => $this->driver->id]);

        $this->postJson('/api/bus-tracking/location', ['latitude' => 12.0, 'longitude' => 77.0], $this->authHeaders($this->driver));
        $this->postJson('/api/bus-tracking/location', ['latitude' => 13.0, 'longitude' => 78.0], $this->authHeaders($this->driver));

        $this->assertDatabaseCount('bus_locations', 1);
        $this->assertDatabaseHas('bus_locations', ['latitude' => 13.0]);
    }

    // ── Viewer: get bus location ──────────────────────────────────────────────

    public function test_teacher_can_get_bus_location(): void
    {
        $bus = Bus::create(['name' => 'Bus H']);
        BusLocation::create(['bus_id' => $bus->id, 'latitude' => 12.9, 'longitude' => 77.5, 'is_active' => true]);

        $this->getJson("/api/bus-tracking/buses/{$bus->id}", $this->authHeaders($this->teacher))
            ->assertOk()
            ->assertJsonPath('bus.id', $bus->id)
            ->assertJsonPath('location.is_active', true);
    }

    public function test_inactive_bus_returns_404(): void
    {
        $bus = Bus::create(['name' => 'Bus I']);
        BusLocation::create(['bus_id' => $bus->id, 'latitude' => 12.9, 'longitude' => 77.5, 'is_active' => false]);

        $this->getJson("/api/bus-tracking/buses/{$bus->id}", $this->authHeaders($this->teacher))
            ->assertStatus(404);
    }

    public function test_bus_with_no_location_returns_404(): void
    {
        $bus = Bus::create(['name' => 'Bus J']);
        $this->getJson("/api/bus-tracking/buses/{$bus->id}", $this->authHeaders($this->teacher))
            ->assertStatus(404);
    }

    public function test_unauthenticated_cannot_access_bus_tracking(): void
    {
        $this->getJson('/api/bus-tracking/my-bus')->assertStatus(401);
        $this->postJson('/api/bus-tracking/location', [])->assertStatus(401);
    }
}
