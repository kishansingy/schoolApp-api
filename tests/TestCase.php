<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Run all migrations including the school/ subfolder before each test.
     * RefreshDatabase calls migrate, but only the default path.
     * We override to also run school migrations first.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function seedRoles(array $names = ['admin', 'viewer', 'student', 'parent', 'teacher', 'driver']): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach ($names as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    protected function makeUser(string $role, array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole($role);
        return $user;
    }

    protected function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    protected function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $this->tokenFor($user)];
    }
}
