<?php

namespace Tests\Feature;

use App\Models\AppField;
use App\Models\AppTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppFieldTest extends TestCase
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

        $this->table = AppTable::create(['name' => 'contacts', 'label' => 'Contacts']);
    }

    private function adminHeaders(): array  { return ['Authorization' => "Bearer {$this->adminToken}"]; }
    private function viewerHeaders(): array { return ['Authorization' => "Bearer {$this->viewerToken}"]; }

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_can_list_fields_for_table(): void
    {
        $this->table->fields()->create(['name' => 'first_name', 'label' => 'First Name', 'type' => 'string']);

        $this->getJson("/api/tables/{$this->table->id}/fields", $this->viewerHeaders())
            ->assertOk()
            ->assertJsonCount(1);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_field(): void
    {
        $res = $this->postJson("/api/tables/{$this->table->id}/fields", [
            'name'  => 'email',
            'label' => 'Email Address',
            'type'  => 'email',
        ], $this->adminHeaders());

        $res->assertStatus(201)
            ->assertJsonPath('name', 'email')
            ->assertJsonPath('type', 'email');

        $this->assertDatabaseHas('app_fields', ['name' => 'email', 'app_table_id' => $this->table->id]);
    }

    public function test_viewer_cannot_create_field(): void
    {
        $this->postJson("/api/tables/{$this->table->id}/fields", [
            'name'  => 'phone',
            'label' => 'Phone',
            'type'  => 'phone',
        ], $this->viewerHeaders())->assertStatus(403);
    }

    public function test_create_field_requires_name_label_type(): void
    {
        $this->postJson("/api/tables/{$this->table->id}/fields", [], $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'label', 'type']);
    }

    public function test_create_field_rejects_invalid_type(): void
    {
        $this->postJson("/api/tables/{$this->table->id}/fields", [
            'name'  => 'col',
            'label' => 'Col',
            'type'  => 'invalid_type',
        ], $this->adminHeaders())->assertStatus(422)
          ->assertJsonValidationErrors(['type']);
    }

    public function test_create_field_rejects_duplicate_name_in_same_table(): void
    {
        $this->table->fields()->create(['name' => 'status', 'label' => 'Status', 'type' => 'string']);

        $this->postJson("/api/tables/{$this->table->id}/fields", [
            'name'  => 'status',
            'label' => 'Status Again',
            'type'  => 'string',
        ], $this->adminHeaders())->assertStatus(422);
    }

    public function test_create_field_with_choices(): void
    {
        $res = $this->postJson("/api/tables/{$this->table->id}/fields", [
            'name'    => 'priority',
            'label'   => 'Priority',
            'type'    => 'choice',
            'choices' => [
                ['label' => 'High',   'value' => 'high'],
                ['label' => 'Medium', 'value' => 'medium'],
                ['label' => 'Low',    'value' => 'low'],
            ],
        ], $this->adminHeaders());

        $res->assertStatus(201);
        $this->assertCount(3, $res->json('choices'));
    }

    public function test_create_reference_field(): void
    {
        $refTable = AppTable::create(['name' => 'users_ref', 'label' => 'Users Ref']);

        $res = $this->postJson("/api/tables/{$this->table->id}/fields", [
            'name'               => 'assigned_to',
            'label'              => 'Assigned To',
            'type'               => 'reference',
            'reference_table_id' => $refTable->id,
        ], $this->adminHeaders());

        $res->assertStatus(201)
            ->assertJsonPath('reference_table_id', $refTable->id);
    }

    public function test_all_field_types_are_accepted(): void
    {
        $types = ['string','integer','boolean','text','date','datetime','reference','choice','email','url','phone','currency','percent','html'];

        foreach ($types as $i => $type) {
            $res = $this->postJson("/api/tables/{$this->table->id}/fields", [
                'name'  => "field_{$i}",
                'label' => "Field {$i}",
                'type'  => $type,
            ], $this->adminHeaders());
            $res->assertStatus(201, "Failed for type: {$type}");
        }
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_field(): void
    {
        $field = $this->table->fields()->create(['name' => 'notes', 'label' => 'Notes', 'type' => 'text']);

        $this->putJson("/api/tables/{$this->table->id}/fields/{$field->id}", [
            'label'     => 'Internal Notes',
            'mandatory' => true,
        ], $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('label', 'Internal Notes')
            ->assertJsonPath('mandatory', true);
    }

    public function test_viewer_cannot_update_field(): void
    {
        $field = $this->table->fields()->create(['name' => 'desc', 'label' => 'Desc', 'type' => 'text']);

        $this->putJson("/api/tables/{$this->table->id}/fields/{$field->id}", [
            'label' => 'Hacked',
        ], $this->viewerHeaders())->assertStatus(403);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_field(): void
    {
        $field = $this->table->fields()->create(['name' => 'temp_col', 'label' => 'Temp', 'type' => 'string']);

        $this->deleteJson("/api/tables/{$this->table->id}/fields/{$field->id}", [], $this->adminHeaders())
            ->assertStatus(204);

        $this->assertDatabaseMissing('app_fields', ['id' => $field->id]);
    }

    public function test_viewer_cannot_delete_field(): void
    {
        $field = $this->table->fields()->create(['name' => 'safe_col', 'label' => 'Safe', 'type' => 'string']);

        $this->deleteJson("/api/tables/{$this->table->id}/fields/{$field->id}", [], $this->viewerHeaders())
            ->assertStatus(403);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_can_get_single_field(): void
    {
        $field = $this->table->fields()->create(['name' => 'city', 'label' => 'City', 'type' => 'string']);

        $this->getJson("/api/tables/{$this->table->id}/fields/{$field->id}", $this->viewerHeaders())
            ->assertOk()
            ->assertJsonPath('name', 'city');
    }
}
