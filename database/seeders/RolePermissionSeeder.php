<?php

namespace Database\Seeders;

use App\Models\AppTable;
use App\Models\TablePermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create roles ───────────────────────────────────────────────────
        $roles = ['admin', 'teacher', 'student', 'parent', 'viewer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // ── 2. Table permission matrix ────────────────────────────────────────
        // Format: table_name => [ role => [read, create, update, delete] ]
        $matrix = [
            'students' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'parents' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, true,  false],
            ],
            'marks' => [
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'exams' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'student_attendance' => [
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'fee_payments' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'fee_structures' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'notices' => [
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'classes' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'sections' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'subjects' => [
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'timetable' => [
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'staff' => [
                'teacher' => [true,  false, false, false],
                'student' => [false, false, false, false],
                'parent'  => [false, false, false, false],
            ],
            'admissions' => [
                'teacher' => [true,  true,  true,  false],
                'student' => [false, false, false, false],
                'parent'  => [false, false, false, false],
            ],
            'book_issues' => [
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],

            // ── Events ────────────────────────────────────────────────────────
            'events' => [
                'admin'   => [true,  true,  true,  true],
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],
            'event_media' => [
                'admin'   => [true,  true,  true,  true],
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],

            // ── Homework ──────────────────────────────────────────────────────
            'homework' => [
                'admin'   => [true,  true,  true,  true],
                'teacher' => [true,  true,  true,  false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],

            // ── YouTube Channel ───────────────────────────────────────────────
            'youtube_videos' => [
                'admin'   => [true,  true,  true,  true],
                'teacher' => [true,  false, false, false],
                'student' => [true,  false, false, false],
                'parent'  => [true,  false, false, false],
            ],

            // ── Remarks & Complaints ──────────────────────────────────────────
            // teacher: full CRUD on remarks they raise
            // parent:  can create complaints, view own student's remarks
            // student: no access (remarks are parent-only)
            'remarks' => [
                'admin'   => [true,  true,  true,  true],
                'teacher' => [true,  true,  true,  false],
                'student' => [false, false, false, false],
                'parent'  => [true,  true,  false, false],
            ],
        ];

        foreach ($matrix as $tableName => $rolePerms) {
            $table = AppTable::where('name', $tableName)->first();
            if (!$table) continue;

            foreach ($rolePerms as $role => [$read, $create, $update, $delete]) {
                TablePermission::updateOrCreate(
                    ['app_table_id' => $table->id, 'role' => $role],
                    [
                        'can_read'   => $read,
                        'can_create' => $create,
                        'can_update' => $update,
                        'can_delete' => $delete,
                    ]
                );
            }
        }

        $this->command->info('Roles and table permissions seeded.');
    }
}
