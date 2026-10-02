<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;

class MigrateSchool extends Command
{
    protected $signature   = 'migrate:school {--fresh : Drop all school tables first} {--seed : Run SchoolSeeder after migration}';
    protected $description = 'Run school-specific migrations from database/migrations/school/';

    public function handle(): int
    {
        $path = database_path('migrations/school');

        if ($this->option('fresh')) {
            $this->info('Dropping school tables...');
            $this->dropSchoolTables();
        }

        $this->info('Running school migrations...');
        $this->call('migrate', [
            '--path'  => 'database/migrations/school',
            '--force' => true,
        ]);

        if ($this->option('seed')) {
            $this->info('Seeding school app_tables & app_fields...');
            $this->call('db:seed', ['--class' => 'SchoolSeeder', '--force' => true]);
        }

        $this->info('School migration complete.');
        return self::SUCCESS;
    }

    private function dropSchoolTables(): void
    {
        $tables = [
            'sch_timetable', 'sch_notices', 'sch_hostel_allotments', 'sch_hostels',
            'sch_student_transport', 'sch_transport_routes',
            'sch_book_issues', 'sch_books',
            'sch_fee_payments', 'sch_fee_structures',
            'sch_staff_attendance', 'sch_student_attendance',
            'sch_marks', 'sch_exams',
            'sch_subjects', 'sch_sections', 'sch_classes',
            'sch_student_parent', 'sch_parents', 'sch_students', 'sch_staff',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
            $this->line("  Dropped: {$table}");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Remove school migration records
        DB::table('migrations')->where('migration', 'like', '2024_02_%')->delete();
    }
}
