<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SampleDataSeeder::class);

        // Tester accounts — one user per role for manual QA
        $this->call(TesterUsersSeeder::class);

        // Run school seeder only when explicitly called:
        // php artisan db:seed --class=SchoolSeeder
        // php artisan db:seed --class=MenuSeeder
        // php artisan db:seed --class=RolePermissionSeeder
        // php artisan db:seed --class=ExamMarksSeeder
    }
}
