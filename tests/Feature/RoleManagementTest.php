<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->admin  = $this->makeUser('admin');
        $this->viewer = $this->makeUser('viewer');
    }

    public function test_admin_can_list_roles(): void
    {
        $this->getJson('/api/roles', $this->authHeaders($this->admin))
            ->assertOk()->assertJsonStructure([['name']]);
    }

    public function test_non_admin_cannot_list_roles(): void
    {
        $this->getJson('/api/roles', $this->authHeaders($this->viewer))->assertStatus(403);
    }

    public function test_admin_can_create_role(): void
    {
        $res = $this->postJson('/api/roles', ['name' => 'librarian'], $this->authHeaders($this->admin));
        $res->assertOk();
        $this->assertDatabaseHas('roles', ['name' => 'librarian']);
    }

    public function test_admin_can_delete_role(): void
    {
        $role = Role::create(['name' => 'temp_role', 'guard_name' => 'web']);
        $this->deleteJson("/api/roles/{$role->id}", [], $this->authHeaders($this->admin))
            ->assertStatus(204);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_can_list_users(): void
    {
        $this->getJson('/api/users', $this->authHeaders($this->admin))->assertOk();
    }

    public function test_admin_can_assign_role_to_user(): void
    {
        $user = User::factory()->create();
        $this->putJson("/api/users/{$user->id}/roles", [
            'roles' => ['viewer'],
        ], $this->authHeaders($this->admin))->assertOk();

        $this->assertTrue($user->fresh()->hasRole('viewer'));
    }

    public function test_non_admin_cannot_assign_roles(): void
    {
        $user = User::factory()->create();
        $this->putJson("/api/users/{$user->id}/roles", [
            'roles' => ['admin'],
        ], $this->authHeaders($this->viewer))->assertStatus(403);
    }
}
