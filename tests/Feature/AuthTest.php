<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed required roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'admin',  'guard_name' => 'web']);
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);
    }

    // ── Register ─────────────────────────────────────────────────────────────

    public function test_first_user_registers_and_gets_admin_role(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name'                  => 'Admin User',
            'email'                 => 'admin@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(201)
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', 'admin@test.com');

        $this->assertDatabaseHas('users', ['email' => 'admin@test.com']);

        $user = User::where('email', 'admin@test.com')->first();
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_second_user_registers_and_gets_viewer_role(): void
    {
        // Create first user (admin)
        User::factory()->create()->assignRole('admin');

        $res = $this->postJson('/api/auth/register', [
            'name'                  => 'Viewer User',
            'email'                 => 'viewer@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertStatus(201);

        $user = User::where('email', 'viewer@test.com')->first();
        $this->assertTrue($user->hasRole('viewer'));
    }

    public function test_register_requires_all_fields(): void
    {
        $this->postJson('/api/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@test.com'])->assignRole('admin');

        $this->postJson('/api/auth/register', [
            'name'                  => 'Another',
            'email'                 => 'dup@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_password_mismatch(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'test@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'different123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_short_password(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'test@test.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')])->assignRole('viewer');

        $res = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')])->assignRole('viewer');

        $res = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $this->assertNotEmpty($res->json('token'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct')])->assignRole('viewer');

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'nobody@test.com',
            'password' => 'password123',
        ])->assertStatus(422);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_revokes_old_tokens_and_issues_new_one(): void
    {
        $user = User::factory()->create(['password' => bcrypt('pass1234')])->assignRole('viewer');
        $user->createToken('old-token');

        $this->assertEquals(1, $user->tokens()->count());

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'pass1234',
        ])->assertOk();

        // Old tokens deleted, one new token issued
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    // ── Me ────────────────────────────────────────────────────────────────────

    public function test_me_returns_authenticated_user(): void
    {
        $user  = User::factory()->create()->assignRole('admin');
        $token = $user->createToken('api')->plainTextToken;

        $this->getJson('/api/auth/me', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_me_returns_user_roles(): void
    {
        $user  = User::factory()->create()->assignRole('admin');
        $token = $user->createToken('api')->plainTextToken;

        $res = $this->getJson('/api/auth/me', ['Authorization' => "Bearer $token"]);

        $res->assertOk();
        $roles = collect($res->json('roles'))->pluck('name');
        $this->assertTrue($roles->contains('admin'));
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create()->assignRole('viewer');
        $token = $user->createToken('api')->plainTextToken;

        $this->postJson('/api/auth/logout', [], ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        // Token should be deleted
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    // ── Protected routes ──────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_tables(): void
    {
        $this->getJson('/api/tables')
            ->assertStatus(401);
    }

    public function test_non_admin_cannot_create_table(): void
    {
        $user  = User::factory()->create()->assignRole('viewer');
        $token = $user->createToken('api')->plainTextToken;

        $this->postJson('/api/tables', ['name' => 'test', 'label' => 'Test'], [
            'Authorization' => "Bearer $token",
        ])->assertStatus(403);
    }

    public function test_admin_can_access_roles_endpoint(): void
    {
        $user  = User::factory()->create()->assignRole('admin');
        $token = $user->createToken('api')->plainTextToken;

        $this->getJson('/api/roles', ['Authorization' => "Bearer $token"])
            ->assertOk();
    }

    public function test_viewer_cannot_access_roles_endpoint(): void
    {
        $user  = User::factory()->create()->assignRole('viewer');
        $token = $user->createToken('api')->plainTextToken;

        $this->getJson('/api/roles', ['Authorization' => "Bearer $token"])
            ->assertStatus(403);
    }
}
