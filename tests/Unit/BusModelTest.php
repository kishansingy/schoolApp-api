<?php

namespace Tests\Unit;

use App\Models\Bus;
use App\Models\BusAssignment;
use App\Models\BusLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_bus_has_location_relationship(): void
    {
        $bus = Bus::create(['name' => 'Bus A']);
        BusLocation::create(['bus_id' => $bus->id, 'latitude' => 12.9, 'longitude' => 77.5, 'is_active' => true]);

        $this->assertInstanceOf(BusLocation::class, $bus->location);
        $this->assertEquals(12.9, $bus->location->latitude);
    }

    public function test_bus_has_assignments_relationship(): void
    {
        $bus = Bus::create(['name' => 'Bus B']);
        BusAssignment::create(['bus_id' => $bus->id, 'student_record_id' => 1]);
        BusAssignment::create(['bus_id' => $bus->id, 'student_record_id' => 2]);

        $this->assertCount(2, $bus->assignments);
    }

    public function test_deleting_bus_cascades_to_location(): void
    {
        $bus = Bus::create(['name' => 'Bus C']);
        BusLocation::create(['bus_id' => $bus->id, 'latitude' => 12.0, 'longitude' => 77.0, 'is_active' => true]);

        $bus->delete();
        $this->assertDatabaseMissing('bus_locations', ['bus_id' => $bus->id]);
    }

    public function test_deleting_bus_cascades_to_assignments(): void
    {
        $bus = Bus::create(['name' => 'Bus D']);
        BusAssignment::create(['bus_id' => $bus->id, 'student_record_id' => 5]);

        $bus->delete();
        $this->assertDatabaseMissing('bus_assignments', ['bus_id' => $bus->id]);
    }

    public function test_bus_location_casts_booleans(): void
    {
        $bus = Bus::create(['name' => 'Bus E']);
        $loc = BusLocation::create(['bus_id' => $bus->id, 'latitude' => 12.0, 'longitude' => 77.0, 'is_active' => true]);

        $this->assertIsBool($loc->is_active);
        $this->assertTrue($loc->is_active);
    }

    public function test_bus_location_casts_floats(): void
    {
        $bus = Bus::create(['name' => 'Bus F']);
        $loc = BusLocation::create(['bus_id' => $bus->id, 'latitude' => 12.9716, 'longitude' => 77.5946, 'speed' => 45.5]);

        $this->assertIsFloat($loc->latitude);
        $this->assertIsFloat($loc->speed);
    }
}
