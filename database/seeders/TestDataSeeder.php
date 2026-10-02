<?php

namespace Database\Seeders;

use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Links test users to real student/parent records and seeds dummy data.
 * Run after TesterUsersSeeder + DummyDataSeeder.
 *
 * php artisan db:seed --class=TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->linkTestUsers();
        $this->seedNotices();
        $this->seedHomework();
        $this->seedEvents();
        $this->seedYoutubeVideos();
        $this->seedRemarks();

        $this->command->info('Test data seeded and users linked.');
    }

    // ── Link student@school.test and parent@school.test to records ────────────

    private function linkTestUsers(): void
    {
        $studentUser = User::where('email', 'student@school.test')->first();
        $parentUser  = User::where('email', 'parent@school.test')->first();

        // Pick first student record
        $studentRecord = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'students'))
            ->first();

        // Pick first parent record
        $parentRecord = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'parents'))
            ->first();

        if ($studentUser && $studentRecord) {
            $studentUser->update(['app_record_id' => $studentRecord->id, 'record_type' => 'student']);
            $this->command->info("✓ student@school.test linked to student record #{$studentRecord->id}");
        } else {
            $this->command->warn('student@school.test or student record not found — run TesterUsersSeeder + StudentSeeder first.');
        }

        if ($parentUser && $parentRecord) {
            $parentUser->update(['app_record_id' => $parentRecord->id, 'record_type' => 'parent']);
            $this->command->info("✓ parent@school.test linked to parent record #{$parentRecord->id}");
        } else {
            $this->command->warn('parent@school.test or parent record not found — run TesterUsersSeeder + ParentSeeder first.');
        }
    }

    // ── Notices ───────────────────────────────────────────────────────────────

    private function seedNotices(): void
    {
        $t = AppTable::where('name', 'notices')->first();
        if (!$t || AppRecord::where('app_table_id', $t->id)->exists()) return;

        $notices = [
            ['title' => 'Annual Sports Day',        'content' => 'Annual Sports Day will be held on 10th May. All students must participate.', 'date' => '2026-04-28', 'audience' => 'all',      'status' => 'active'],
            ['title' => 'Parent-Teacher Meeting',   'content' => 'PTM scheduled for 5th May from 10am to 1pm. Parents are requested to attend.', 'date' => '2026-04-25', 'audience' => 'parents',  'status' => 'active'],
            ['title' => 'Exam Schedule Released',   'content' => 'Final exam timetable has been uploaded. Check the notice board.', 'date' => '2026-04-20', 'audience' => 'students', 'status' => 'active'],
            ['title' => 'Holiday Notice',           'content' => 'School will remain closed on 1st May for Labour Day.', 'date' => '2026-04-18', 'audience' => 'all',      'status' => 'active'],
            ['title' => 'Fee Payment Reminder',     'content' => 'Last date for fee payment is 30th April. Kindly pay to avoid late charges.', 'date' => '2026-04-15', 'audience' => 'parents',  'status' => 'active'],
        ];

        foreach ($notices as $n) {
            AppRecord::create(['app_table_id' => $t->id, 'data' => $n]);
        }
        $this->command->info('Notices seeded.');
    }

    // ── Homework ──────────────────────────────────────────────────────────────

    private function seedHomework(): void
    {
        $t = AppTable::where('name', 'homework')->first();
        if (!$t || AppRecord::where('app_table_id', $t->id)->exists()) return;

        $classId   = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'classes'))->value('id');
        $sectionId = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'sections'))->value('id');
        $staffId   = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'staff'))->value('id');
        $subjectIds = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'subjects'))->pluck('id')->take(4)->toArray();

        $items = [
            ['title' => 'Math Chapter 5 Exercise',    'description' => 'Complete exercises 5.1 to 5.4 from the textbook.', 'assigned_date' => '2026-04-21', 'submission_date' => '2026-04-25'],
            ['title' => 'Science Lab Report',         'description' => 'Write a report on the photosynthesis experiment conducted in class.', 'assigned_date' => '2026-04-22', 'submission_date' => '2026-04-27'],
            ['title' => 'English Essay',              'description' => 'Write a 300-word essay on "My Favourite Season".', 'assigned_date' => '2026-04-23', 'submission_date' => '2026-04-28'],
            ['title' => 'History Map Work',           'description' => 'Mark the important rivers of India on the outline map.', 'assigned_date' => '2026-04-24', 'submission_date' => '2026-04-29'],
        ];

        foreach ($items as $i => $hw) {
            AppRecord::create(['app_table_id' => $t->id, 'data' => array_merge($hw, [
                'class_id'   => $classId,
                'section_id' => $sectionId,
                'subject_id' => $subjectIds[$i] ?? $subjectIds[0] ?? null,
                'teacher_id' => $staffId,
                'status'     => 'active',
            ])]);
        }
        $this->command->info('Homework seeded.');
    }

    // ── Events ────────────────────────────────────────────────────────────────

    private function seedEvents(): void
    {
        $t = AppTable::where('name', 'events')->first();
        if (!$t || AppRecord::where('app_table_id', $t->id)->exists()) return;

        $events = [
            ['title' => 'Annual Day Celebration',  'description' => 'Grand annual day with cultural performances by students.', 'event_date' => '2026-05-10', 'end_date' => '2026-05-10', 'venue' => 'School Auditorium', 'audience' => 'all',      'status' => 'upcoming'],
            ['title' => 'Science Exhibition',      'description' => 'Students showcase their science projects and innovations.', 'event_date' => '2026-05-15', 'end_date' => '2026-05-16', 'venue' => 'School Hall',      'audience' => 'all',      'status' => 'upcoming'],
            ['title' => 'Inter-School Quiz',       'description' => 'Quiz competition between top schools in the district.', 'event_date' => '2026-04-30', 'end_date' => '2026-04-30', 'venue' => 'Conference Room',  'audience' => 'students', 'status' => 'upcoming'],
            ['title' => 'Republic Day Celebration','description' => 'Flag hoisting and cultural programs on Republic Day.', 'event_date' => '2026-01-26', 'end_date' => '2026-01-26', 'venue' => 'School Ground',    'audience' => 'all',      'status' => 'completed'],
        ];

        foreach ($events as $e) {
            AppRecord::create(['app_table_id' => $t->id, 'data' => $e]);
        }
        $this->command->info('Events seeded.');
    }

    // ── YouTube Videos ────────────────────────────────────────────────────────

    private function seedYoutubeVideos(): void
    {
        $t = AppTable::where('name', 'youtube_videos')->first();
        if (!$t || AppRecord::where('app_table_id', $t->id)->exists()) return;

        $videos = [
            ['title' => 'Introduction to Algebra',     'description' => 'Learn the basics of algebra with simple examples.', 'youtube_url' => 'https://www.youtube.com/watch?v=NybHckSEQBI', 'youtube_id' => 'NybHckSEQBI', 'audience' => 'students', 'sort_order' => 1, 'is_active' => true],
            ['title' => 'Photosynthesis Explained',    'description' => 'How plants make food using sunlight.', 'youtube_url' => 'https://www.youtube.com/watch?v=UPBMG5EYydo', 'youtube_id' => 'UPBMG5EYydo', 'audience' => 'students', 'sort_order' => 2, 'is_active' => true],
            ['title' => 'English Grammar Tips',        'description' => 'Common grammar mistakes and how to avoid them.', 'youtube_url' => 'https://www.youtube.com/watch?v=7Xr9BDDrlGo', 'youtube_id' => '7Xr9BDDrlGo', 'audience' => 'all',      'sort_order' => 3, 'is_active' => true],
            ['title' => 'School Safety Guidelines',    'description' => 'Important safety rules for students and parents.', 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube_id' => 'dQw4w9WgXcQ', 'audience' => 'parents',  'sort_order' => 4, 'is_active' => true],
        ];

        foreach ($videos as $v) {
            AppRecord::create(['app_table_id' => $t->id, 'data' => $v]);
        }
        $this->command->info('YouTube videos seeded.');
    }

    // ── Remarks ───────────────────────────────────────────────────────────────

    private function seedRemarks(): void
    {
        $t = AppTable::where('name', 'remarks')->first();
        if (!$t || AppRecord::where('app_table_id', $t->id)->exists()) return;

        $studentIds = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'students'))->pluck('id')->take(3)->toArray();
        $staffId    = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'staff'))->value('id');

        if (empty($studentIds) || !$staffId) return;

        $remarks = [
            ['student_id' => $studentIds[0], 'type' => 'remark',    'remark_by' => 'teacher', 'message' => 'Excellent performance in the science test. Keep it up!', 'status' => 'open'],
            ['student_id' => $studentIds[0], 'type' => 'complaint',  'remark_by' => 'parent',  'message' => 'Child is not completing homework regularly.', 'status' => 'acknowledged'],
            ['student_id' => $studentIds[1] ?? $studentIds[0], 'type' => 'remark', 'remark_by' => 'teacher', 'message' => 'Needs to improve in mathematics. Extra practice recommended.', 'status' => 'open'],
            ['student_id' => $studentIds[2] ?? $studentIds[0], 'type' => 'remark', 'remark_by' => 'teacher', 'message' => 'Very active in class discussions. Good communication skills.', 'status' => 'resolved'],
        ];

        foreach ($remarks as $r) {
            AppRecord::create(['app_table_id' => $t->id, 'data' => array_merge($r, [
                'teacher_id' => $staffId,
                'visibility' => 'parent_only',
            ])]);
        }
        $this->command->info('Remarks seeded.');
    }
}
