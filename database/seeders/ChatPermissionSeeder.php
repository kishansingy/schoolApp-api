<?php

namespace Database\Seeders;

use App\Models\ChatPermission;
use Illuminate\Database\Seeder;

class ChatPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Default role-to-role chat permissions
        // admin can chat with everyone
        // teacher <-> parent (discuss student issues)
        // teacher <-> student (homework/queries)
        // parent  <-> admin  (school issues)
        $defaults = [
            ['from_role' => 'admin',   'to_role' => 'teacher', 'enabled' => true],
            ['from_role' => 'admin',   'to_role' => 'student', 'enabled' => true],
            ['from_role' => 'admin',   'to_role' => 'parent',  'enabled' => true],
            ['from_role' => 'teacher', 'to_role' => 'parent',  'enabled' => true],
            ['from_role' => 'teacher', 'to_role' => 'student', 'enabled' => true],
            ['from_role' => 'parent',  'to_role' => 'student', 'enabled' => false], // off by default
            ['from_role' => 'teacher', 'to_role' => 'teacher', 'enabled' => true],
        ];

        foreach ($defaults as $row) {
            ChatPermission::firstOrCreate(
                ['from_role' => $row['from_role'], 'to_role' => $row['to_role']],
                ['enabled'   => $row['enabled']]
            );
        }

        $this->command->info('Chat permissions seeded.');
    }
}
