<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Creates one login account per role for manual testing.
 *
 * Credentials summary (all passwords: Test@1234):
 * ┌──────────┬──────────────────────────┬────────────┐
 * │ Role     │ Email                    │ Password   │
 * ├──────────┼──────────────────────────┼────────────┤
 * │ admin    │ admin@school.test        │ Test@1234  │
 * │ teacher  │ teacher@school.test      │ Test@1234  │
 * │ student  │ student@school.test      │ Test@1234  │
 * │ parent   │ parent@school.test       │ Test@1234  │
 * │ viewer   │ viewer@school.test       │ Test@1234  │
 * │ driver   │ driver@school.test       │ Test@1234  │
 * └──────────┴──────────────────────────┴────────────┘
 */
class TesterUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure all roles exist (safe to run standalone)
        $allRoles = ['admin', 'teacher', 'student', 'parent', 'viewer', 'driver'];
        foreach ($allRoles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $users = [
            [
                'name'  => 'Test Admin',
                'email' => 'admin@school.test',
                'role'  => 'admin',
            ],
            [
                'name'  => 'Test Teacher',
                'email' => 'teacher@school.test',
                'role'  => 'teacher',
            ],
            [
                'name'  => 'Test Student',
                'email' => 'student@school.test',
                'role'  => 'student',
            ],
            [
                'name'  => 'Test Parent',
                'email' => 'parent@school.test',
                'role'  => 'parent',
            ],
            [
                'name'  => 'Test Viewer',
                'email' => 'viewer@school.test',
                'role'  => 'viewer',
            ],
            [
                'name'  => 'Test Driver',
                'email' => 'driver@school.test',
                'role'  => 'driver',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('Test@1234'),
                ]
            );

            // Sync role (replaces any existing role assignment)
            $user->syncRoles([$data['role']]);

            $this->command->info("✓ {$data['role']}: {$data['email']} / Test@1234");
        }

        $this->command->newLine();
        $this->command->info('Tester accounts ready. Use the credentials above to test each role.');
    }
}
