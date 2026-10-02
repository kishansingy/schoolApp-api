<?php

namespace Database\Seeders;

use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class LiveSessionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        foreach (['teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Ensure teacher user exists
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@school.test'],
            ['name' => 'Test Teacher', 'password' => Hash::make('Test@1234')]
        );
        $teacher->syncRoles(['teacher']);

        // Ensure student user exists
        $student = User::firstOrCreate(
            ['email' => 'student@school.test'],
            ['name' => 'Test Student', 'password' => Hash::make('Test@1234')]
        );
        $student->syncRoles(['student']);

        // Seed demo live sessions
        $sessions = [
            [
                'title'            => 'Mathematics - Algebra Basics',
                'description'      => 'Introduction to algebraic expressions and equations.',
                'class_name'       => 'Grade 10-A',
                'subject'          => 'Mathematics',
                'scheduled_at'     => now()->addHours(1),
                'duration_minutes' => 60,
                'status'           => 'scheduled',
            ],
            [
                'title'            => 'Science - Newton\'s Laws of Motion',
                'description'      => 'Understanding the three laws of motion with examples.',
                'class_name'       => 'Grade 10-B',
                'subject'          => 'Science',
                'scheduled_at'     => now()->addHours(3),
                'duration_minutes' => 45,
                'status'           => 'scheduled',
            ],
            [
                'title'            => 'English - Essay Writing',
                'description'      => 'Tips and techniques for writing effective essays.',
                'class_name'       => 'Grade 9-A',
                'subject'          => 'English',
                'scheduled_at'     => now()->subHour(),
                'duration_minutes' => 50,
                'status'           => 'live',
            ],
            [
                'title'            => 'History - World War II Overview',
                'description'      => 'Key events and outcomes of World War II.',
                'class_name'       => 'Grade 11-A',
                'subject'          => 'History',
                'scheduled_at'     => now()->subDays(1),
                'duration_minutes' => 60,
                'status'           => 'ended',
            ],
        ];

        foreach ($sessions as $data) {
            LiveSession::firstOrCreate(
                ['title' => $data['title'], 'teacher_id' => $teacher->id],
                array_merge($data, [
                    'teacher_id' => $teacher->id,
                    'room_code'  => Str::random(12),
                ])
            );
        }

        $this->command->info('✓ Live sessions seeded.');
        $this->command->info('  Teacher login: teacher@school.test / Test@1234');
        $this->command->info('  Student login: student@school.test / Test@1234');
    }
}
