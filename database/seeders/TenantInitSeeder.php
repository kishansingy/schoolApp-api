<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Runs inside a freshly created tenant database.
 * Seeds only the bare minimum: roles.
 */
class TenantInitSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'teacher', 'student', 'parent', 'driver', 'viewer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->command->info('Tenant roles seeded.');
    }
}
