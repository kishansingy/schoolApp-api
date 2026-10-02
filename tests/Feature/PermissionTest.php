<?php

namespace Tests\Feature;

use App\Models\AppField;
use App\Models\AppTable;
use App\Models\FieldPermission;
use App\Models\TablePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private string $adminToken;
    private string $viewerToken;
    private AppTable $table;
    private AppField $field;

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

        $this->table = AppTable::create(['name' => 'projects', 'label' => 'Projects']);
        $this->field = $this->table->fields()->create(['name' => 'name', 'label' => 'Name', 'type' => 'string']);
    }

    private function adminHeaders(): array  { return ['Authorization' => "Bearer {$this->adminToken}"]; }
    private function viewerHeaders(): array { return ['Authorization' => "Bearer {$this->viewerToken}"]; }

    // ── Table permissions ─────────────────────────────────────────────────────

    public function test_admin_can_get_table_permissions(): void
    {
        $res = $this->getJson("/api/tables/{$this->table->id}/permissions", $this->adminHeaders());

        $res->assertOk();
        // Should return a row per role
        $roles = collect($res->json())->pluck('role');
        $this->assertTrue($roles->contains('admin'));
        $this->assertTrue($roles->contains('viewer'));
    }

    public function test_viewer_cannot_get_table_permissions(): void
    {
        $this->getJson("/api/tables/{$this->table->id}/permissions", $this->viewerHeaders())
            ->assertStatus(403);
    }

    public function test_admin_can_save_table_permissions(): void
    {
        $res = $this->postJson("/api/tables/{$this->table->id}/permissions", [
            'permissions' => [
                ['role' => 'viewer', 'can_read' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false],
                ['role' => 'admin',  'can_read' => true, 'can_create' => true,  'can_update' => true,  'can_delete' => true],
            ],
        ], $this->adminHeaders());

        $res->assertOk();

        $this->assertDatabaseHas('table_permissions', [
            'app_table_id' => $this->table->id,
            'role'         => 'viewer',
            'can_read'     => 1,
            'can_create'   => 0,
        ]);
    }

    public function test_save_table_permissions_upserts_existing(): void
    {
        TablePermission::create([
            'app_table_id' => $this->table->id,
            'role'         => 'viewer',
            'can_read'     => false,
            'can_create'   => false,
            'can_update'   => false,
            'can_delete'   => false,
        ]);

        $this->postJson("/api/tables/{$this->table->id}/permissions", [
            'permissions' => [
                ['role' => 'viewer', 'can_read' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false],
            ],
        ], $this->adminHeaders())->assertOk();

        $this->assertDatabaseHas('table_permissions', [
            'app_table_id' => $this->table->id,
            'role'         => 'viewer',
            'can_read'     => 1,
        ]);
        // Still only one row
        $this->assertDatabaseCount('table_permissions', 1);
    }

    public function test_save_table_permissions_rejects_invalid_role(): void
    {
        $this->postJson("/api/tables/{$this->table->id}/permissions", [
            'permissions' => [
                ['role' => 'nonexistent_role', 'can_read' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false],
            ],
        ], $this->adminHeaders())->assertStatus(422);
    }

    // ── Field permissions ─────────────────────────────────────────────────────

    public function test_admin_can_get_field_permissions(): void
    {
        $res = $this->getJson("/api/tables/{$this->table->id}/field-permissions", $this->adminHeaders());

        $res->assertOk()
            ->assertJsonCount(1);  // 1 field

        $fieldRow = $res->json('0');
        $this->assertEquals($this->field->id, $fieldRow['field_id']);
        $this->assertCount(2, $fieldRow['permissions']); // 2 roles
    }

    public function test_viewer_cannot_get_field_permissions(): void
    {
        $this->getJson("/api/tables/{$this->table->id}/field-permissions", $this->viewerHeaders())
            ->assertStatus(403);
    }

    public function test_admin_can_save_field_permissions(): void
    {
        $res = $this->postJson("/api/tables/{$this->table->id}/field-permissions", [
            'fields' => [
                [
                    'field_id'    => $this->field->id,
                    'permissions' => [
                        ['role' => 'viewer', 'visible' => true,  'editable' => false, 'mandatory' => false],
                        ['role' => 'admin',  'visible' => true,  'editable' => true,  'mandatory' => false],
                    ],
                ],
            ],
        ], $this->adminHeaders());

        $res->assertOk();

        $this->assertDatabaseHas('field_permissions', [
            'app_field_id' => $this->field->id,
            'role'         => 'viewer',
            'editable'     => 0,
        ]);
    }

    public function test_save_field_permissions_upserts(): void
    {
        FieldPermission::create([
            'app_field_id' => $this->field->id,
            'role'         => 'viewer',
            'visible'      => false,
            'editable'     => false,
            'mandatory'    => false,
        ]);

        $this->postJson("/api/tables/{$this->table->id}/field-permissions", [
            'fields' => [[
                'field_id'    => $this->field->id,
                'permissions' => [
                    ['role' => 'viewer', 'visible' => true, 'editable' => true, 'mandatory' => false],
                ],
            ]],
        ], $this->adminHeaders())->assertOk();

        $this->assertDatabaseHas('field_permissions', [
            'app_field_id' => $this->field->id,
            'role'         => 'viewer',
            'visible'      => 1,
            'editable'     => 1,
        ]);
        $this->assertDatabaseCount('field_permissions', 1);
    }

    // ── Table permission middleware enforcement ────────────────────────────────

    public function test_viewer_blocked_from_read_when_permission_denies(): void
    {
        TablePermission::create([
            'app_table_id' => $this->table->id,
            'role'         => 'viewer',
            'can_read'     => false,
            'can_create'   => false,
            'can_update'   => false,
            'can_delete'   => false,
        ]);

        $this->getJson("/api/tables/{$this->table->id}/records", $this->viewerHeaders())
            ->assertStatus(403);
    }

    public function test_admin_always_bypasses_table_permission_check(): void
    {
        TablePermission::create([
            'app_table_id' => $this->table->id,
            'role'         => 'admin',
            'can_read'     => false,
            'can_create'   => false,
            'can_update'   => false,
            'can_delete'   => false,
        ]);

        // Admin bypasses even if permission row says false
        $this->getJson("/api/tables/{$this->table->id}/records", $this->adminHeaders())
            ->assertOk();
    }
}
