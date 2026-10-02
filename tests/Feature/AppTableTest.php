<?php

namespace Tests\Feature;

use App\Models\AppTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private string $adminToken;
    private string $viewerToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'admin',  'guard_name' => 'web']);
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        $this->admin  = User::factory()->create()->assignRole('admin');
        $this->viewer = User::factory()->create()->assignRole('viewer');
        $this->adminToken  = $this->admin->createToken('api')->plainTextToken;
        $this->viewerToken = $this->viewer->createToken('api')->plainTextToken;
    }

    private function adminHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->adminToken}"];
    }

    private function viewerHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->viewerToken}"];
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_list_tables(): void
    {
        AppTable::create(['name' => 'invoices', 'label' => 'Invoices']);

        $this->getJson('/api/tables', $this->viewerHeaders())
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_unauthenticated_cannot_list_tables(): void
    {
        $this->getJson('/api/tables')->assertStatus(401);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_table(): void
    {
        $res = $this->postJson('/api/tables', [
            'name'  => 'customers',
            'label' => 'Customers',
        ], $this->adminHeaders());

        $res->assertStatus(201)
            ->assertJsonPath('name', 'customers')
            ->assertJsonPath('label', 'Customers');

        $this->assertDatabaseHas('app_tables', ['name' => 'customers']);
    }

    public function test_viewer_cannot_create_table(): void
    {
        $this->postJson('/api/tables', [
            'name'  => 'customers',
            'label' => 'Customers',
        ], $this->viewerHeaders())->assertStatus(403);
    }

    public function test_create_table_requires_name_and_label(): void
    {
        $this->postJson('/api/tables', [], $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'label']);
    }

    public function test_create_table_rejects_invalid_name_format(): void
    {
        $this->postJson('/api/tables', [
            'name'  => 'My Table!',
            'label' => 'My Table',
        ], $this->adminHeaders())->assertStatus(422)
          ->assertJsonValidationErrors(['name']);
    }

    public function test_create_table_rejects_duplicate_name(): void
    {
        AppTable::create(['name' => 'orders', 'label' => 'Orders']);

        $this->postJson('/api/tables', [
            'name'  => 'orders',
            'label' => 'Orders Again',
        ], $this->adminHeaders())->assertStatus(422)
          ->assertJsonValidationErrors(['name']);
    }

    public function test_create_table_with_all_types(): void
    {
        foreach (['standard', 'header', 'detail', 'footer'] as $type) {
            $res = $this->postJson('/api/tables', [
                'name'       => "tbl_{$type}",
                'label'      => ucfirst($type),
                'table_type' => $type,
            ], $this->adminHeaders());
            $res->assertStatus(201)->assertJsonPath('table_type', $type);
        }
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_can_get_single_table(): void
    {
        $table = AppTable::create(['name' => 'products', 'label' => 'Products']);

        $this->getJson("/api/tables/{$table->id}", $this->viewerHeaders())
            ->assertOk()
            ->assertJsonPath('name', 'products');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_table(): void
    {
        $table = AppTable::create(['name' => 'old_name', 'label' => 'Old']);

        $this->putJson("/api/tables/{$table->id}", [
            'label' => 'New Label',
        ], $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('label', 'New Label');
    }

    public function test_viewer_cannot_update_table(): void
    {
        $table = AppTable::create(['name' => 'some_table', 'label' => 'Some']);

        $this->putJson("/api/tables/{$table->id}", [
            'label' => 'Hacked',
        ], $this->viewerHeaders())->assertStatus(403);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_table(): void
    {
        $table = AppTable::create(['name' => 'temp_table', 'label' => 'Temp']);

        $this->deleteJson("/api/tables/{$table->id}", [], $this->adminHeaders())
            ->assertStatus(204);

        $this->assertDatabaseMissing('app_tables', ['id' => $table->id]);
    }

    public function test_viewer_cannot_delete_table(): void
    {
        $table = AppTable::create(['name' => 'safe_table', 'label' => 'Safe']);

        $this->deleteJson("/api/tables/{$table->id}", [], $this->viewerHeaders())
            ->assertStatus(403);
    }

    // ── Parent/Detail relationship ────────────────────────────────────────────

    public function test_detail_table_can_reference_header(): void
    {
        $header = AppTable::create(['name' => 'sales_order', 'label' => 'Sales Order', 'table_type' => 'header']);

        $res = $this->postJson('/api/tables', [
            'name'               => 'order_lines',
            'label'              => 'Order Lines',
            'table_type'         => 'detail',
            'detail_of_table_id' => $header->id,
        ], $this->adminHeaders());

        $res->assertStatus(201)
            ->assertJsonPath('detail_of_table_id', $header->id);
    }
}
