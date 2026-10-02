<?php

namespace Tests\Feature;

use App\Models\AppField;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\TablePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppRecordTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private string $adminToken;
    private string $viewerToken;
    private AppTable $table;

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

        $this->table = AppTable::create(['name' => 'tasks', 'label' => 'Tasks', 'can_read' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true]);
        $this->table->fields()->create(['name' => 'title', 'label' => 'Title', 'type' => 'string']);
    }

    private function adminHeaders(): array  { return ['Authorization' => "Bearer {$this->adminToken}"]; }
    private function viewerHeaders(): array { return ['Authorization' => "Bearer {$this->viewerToken}"]; }

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_can_list_records(): void
    {
        AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Task 1']]);
        AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Task 2']]);

        $this->getJson("/api/tables/{$this->table->id}/records", $this->viewerHeaders())
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_unauthenticated_cannot_list_records(): void
    {
        $this->getJson("/api/tables/{$this->table->id}/records")->assertStatus(401);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_record(): void
    {
        $res = $this->postJson("/api/tables/{$this->table->id}/records", [
            'data' => ['title' => 'New Task'],
        ], $this->adminHeaders());

        $res->assertStatus(201);
        $this->assertDatabaseHas('app_records', ['app_table_id' => $this->table->id]);
    }

    public function test_viewer_can_create_record_when_table_allows(): void
    {
        // Table has can_create = true (set in setUp), viewer has no role-specific row → falls back to table default
        $res = $this->postJson("/api/tables/{$this->table->id}/records", [
            'data' => ['title' => 'Viewer Task'],
        ], $this->viewerHeaders());

        $res->assertStatus(201);
    }

    public function test_viewer_blocked_when_table_permission_denies_create(): void
    {
        TablePermission::create([
            'app_table_id' => $this->table->id,
            'role'         => 'viewer',
            'can_read'     => true,
            'can_create'   => false,
            'can_update'   => false,
            'can_delete'   => false,
        ]);

        $this->postJson("/api/tables/{$this->table->id}/records", [
            'data' => ['title' => 'Blocked'],
        ], $this->viewerHeaders())->assertStatus(403);
    }

    public function test_mandatory_field_validation(): void
    {
        $this->table->fields()->create(['name' => 'required_col', 'label' => 'Required Col', 'type' => 'string', 'mandatory' => true]);

        $res = $this->postJson("/api/tables/{$this->table->id}/records", [
            'data' => ['title' => 'Missing required'],
        ], $this->adminHeaders());

        $res->assertStatus(422)
            ->assertJsonPath('errors.required_col.0', 'The Required Col field is required.');
    }

    public function test_default_value_applied_on_create(): void
    {
        $this->table->fields()->create([
            'name'          => 'status',
            'label'         => 'Status',
            'type'          => 'string',
            'default_value' => 'open',
        ]);

        $res = $this->postJson("/api/tables/{$this->table->id}/records", [
            'data' => ['title' => 'Task with default'],
        ], $this->adminHeaders());

        $res->assertStatus(201);
        $this->assertEquals('open', $res->json('data.status'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_can_get_single_record(): void
    {
        $record = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Find Me']]);

        $this->getJson("/api/tables/{$this->table->id}/records/{$record->id}", $this->viewerHeaders())
            ->assertOk()
            ->assertJsonPath('data.title', 'Find Me');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_record(): void
    {
        $record = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Old']]);

        $this->putJson("/api/tables/{$this->table->id}/records/{$record->id}", [
            'data' => ['title' => 'Updated'],
        ], $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated');
    }

    public function test_readonly_field_preserved_on_update(): void
    {
        $this->table->fields()->create(['name' => 'locked', 'label' => 'Locked', 'type' => 'string', 'readonly' => true]);
        $record = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'T', 'locked' => 'original']]);

        $this->putJson("/api/tables/{$this->table->id}/records/{$record->id}", [
            'data' => ['title' => 'T', 'locked' => 'tampered'],
        ], $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('data.locked', 'original');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_record(): void
    {
        $record = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Delete Me']]);

        $this->deleteJson("/api/tables/{$this->table->id}/records/{$record->id}", [], $this->adminHeaders())
            ->assertStatus(204);

        $this->assertDatabaseMissing('app_records', ['id' => $record->id]);
    }

    public function test_delete_cascades_to_child_records(): void
    {
        $header  = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Header']]);
        $child   = AppRecord::create(['app_table_id' => $this->table->id, 'parent_record_id' => $header->id, 'data' => ['title' => 'Child']]);

        $this->deleteJson("/api/tables/{$this->table->id}/records/{$header->id}", [], $this->adminHeaders())
            ->assertStatus(204);

        $this->assertDatabaseMissing('app_records', ['id' => $child->id]);
    }

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function test_lookup_returns_records(): void
    {
        AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Lookup Item']]);

        $res = $this->getJson("/api/tables/{$this->table->id}/lookup", $this->viewerHeaders());

        $res->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.label', 'Lookup Item');
    }

    // ── Detail records ────────────────────────────────────────────────────────

    public function test_can_save_and_retrieve_detail_records(): void
    {
        $header      = AppTable::create(['name' => 'orders', 'label' => 'Orders', 'table_type' => 'header']);
        $detailTable = AppTable::create(['name' => 'order_lines', 'label' => 'Order Lines', 'table_type' => 'detail', 'detail_of_table_id' => $header->id]);
        $detailTable->fields()->create(['name' => 'product', 'label' => 'Product', 'type' => 'string']);

        $headerRecord = AppRecord::create(['app_table_id' => $header->id, 'data' => ['ref' => 'ORD-001']]);

        // Save detail rows
        $this->postJson("/api/tables/{$header->id}/records/{$headerRecord->id}/details/{$detailTable->id}", [
            'rows' => [
                ['product' => 'Widget A'],
                ['product' => 'Widget B'],
            ],
        ], $this->adminHeaders())->assertStatus(201)->assertJsonCount(2);

        // Retrieve detail rows
        $this->getJson("/api/tables/{$header->id}/records/{$headerRecord->id}/details/{$detailTable->id}", $this->viewerHeaders())
            ->assertOk()
            ->assertJsonCount(2);
    }
}
