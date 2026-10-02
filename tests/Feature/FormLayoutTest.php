<?php

namespace Tests\Feature;

use App\Models\AppField;
use App\Models\AppTable;
use App\Models\FormLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private string $adminToken;
    private string $viewerToken;
    private AppTable $table;
    private AppField $field1;
    private AppField $field2;

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

        $this->table  = AppTable::create(['name' => 'incidents', 'label' => 'Incidents']);
        $this->field1 = $this->table->fields()->create(['name' => 'title',       'label' => 'Title',       'type' => 'string']);
        $this->field2 = $this->table->fields()->create(['name' => 'description', 'label' => 'Description', 'type' => 'text']);
    }

    private function adminHeaders(): array  { return ['Authorization' => "Bearer {$this->adminToken}"]; }
    private function viewerHeaders(): array { return ['Authorization' => "Bearer {$this->viewerToken}"]; }

    // ── Get layout ────────────────────────────────────────────────────────────

    public function test_get_layout_returns_empty_when_none_saved(): void
    {
        $this->getJson("/api/tables/{$this->table->id}/form-layout", $this->viewerHeaders())
            ->assertOk()
            ->assertJson([]);
    }

    public function test_get_layout_returns_sections_with_fields(): void
    {
        FormLayout::create(['app_table_id' => $this->table->id, 'section_name' => 'Main', 'section_order' => 0, 'app_field_id' => $this->field1->id, 'field_order' => 0]);
        FormLayout::create(['app_table_id' => $this->table->id, 'section_name' => 'Main', 'section_order' => 0, 'app_field_id' => $this->field2->id, 'field_order' => 1]);

        $res = $this->getJson("/api/tables/{$this->table->id}/form-layout", $this->viewerHeaders());

        $res->assertOk()
            ->assertJsonCount(1)                          // 1 section
            ->assertJsonPath('0.name', 'Main')
            ->assertJsonCount(2, '0.fields');
    }

    // ── Save layout ───────────────────────────────────────────────────────────

    public function test_admin_can_save_layout(): void
    {
        $res = $this->postJson("/api/tables/{$this->table->id}/form-layout", [
            'sections' => [
                [
                    'name'          => 'Details',
                    'section_order' => 0,
                    'fields'        => [
                        ['id' => $this->field1->id, 'field_order' => 0],
                        ['id' => $this->field2->id, 'field_order' => 1],
                    ],
                ],
            ],
        ], $this->adminHeaders());

        $res->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Details')
            ->assertJsonCount(2, '0.fields');

        $this->assertDatabaseCount('form_layouts', 2);
    }

    public function test_viewer_cannot_save_layout(): void
    {
        $this->postJson("/api/tables/{$this->table->id}/form-layout", [
            'sections' => [['name' => 'Main', 'section_order' => 0, 'fields' => []]],
        ], $this->viewerHeaders())->assertStatus(403);
    }

    public function test_save_layout_replaces_existing(): void
    {
        // Save initial layout with 2 fields
        $this->postJson("/api/tables/{$this->table->id}/form-layout", [
            'sections' => [[
                'name' => 'Old', 'section_order' => 0,
                'fields' => [
                    ['id' => $this->field1->id, 'field_order' => 0],
                    ['id' => $this->field2->id, 'field_order' => 1],
                ],
            ]],
        ], $this->adminHeaders());

        $this->assertDatabaseCount('form_layouts', 2);

        // Save new layout with only 1 field
        $this->postJson("/api/tables/{$this->table->id}/form-layout", [
            'sections' => [[
                'name' => 'New', 'section_order' => 0,
                'fields' => [['id' => $this->field1->id, 'field_order' => 0]],
            ]],
        ], $this->adminHeaders());

        // Old rows replaced
        $this->assertDatabaseCount('form_layouts', 1);
    }

    public function test_save_layout_with_multiple_sections(): void
    {
        $res = $this->postJson("/api/tables/{$this->table->id}/form-layout", [
            'sections' => [
                [
                    'name' => 'Section A', 'section_order' => 0,
                    'fields' => [['id' => $this->field1->id, 'field_order' => 0]],
                ],
                [
                    'name' => 'Section B', 'section_order' => 1,
                    'fields' => [['id' => $this->field2->id, 'field_order' => 0]],
                ],
            ],
        ], $this->adminHeaders());

        $res->assertOk()->assertJsonCount(2);
        $this->assertDatabaseCount('form_layouts', 2);
    }

    public function test_save_layout_rejects_invalid_field_id(): void
    {
        $this->postJson("/api/tables/{$this->table->id}/form-layout", [
            'sections' => [[
                'name' => 'Main', 'section_order' => 0,
                'fields' => [['id' => 9999, 'field_order' => 0]],
            ]],
        ], $this->adminHeaders())->assertStatus(422);
    }

    public function test_save_layout_requires_sections(): void
    {
        $this->postJson("/api/tables/{$this->table->id}/form-layout", [], $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sections']);
    }
}
