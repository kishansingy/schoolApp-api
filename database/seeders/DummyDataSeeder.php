<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ClassSectionSeeder;
use Database\Seeders\StaffSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\ParentSeeder;
use Database\Seeders\StudentSeeder;
use Database\Seeders\ExamSeeder;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\FeeSeeder;
use Database\Seeders\HostelSeeder;
use Database\Seeders\LibrarySeeder;
use Database\Seeders\NoticeSeeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters — independent tables first, dependents last
        $this->call([
            ClassSectionSeeder::class,  // no deps
            StaffSeeder::class,         // no deps
            SubjectSeeder::class,       // needs classes, staff
            ParentSeeder::class,        // no deps
            StudentSeeder::class,       // needs classes, sections, parents
            ExamSeeder::class,          // needs students, subjects
            AttendanceSeeder::class,    // needs students, staff
            FeeSeeder::class,           // needs classes, students
            HostelSeeder::class,        // needs staff, students
            LibrarySeeder::class,       // needs students
            NoticeSeeder::class,        // no deps
        ]);
        $this->command->info('All dummy data seeded successfully.');
    }
}
