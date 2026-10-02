<?php

namespace Database\Seeders;

use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class ExamMarksSeeder extends Seeder
{
    public function run(): void
    {
        $headerTable = AppTable::where('name', 'exam_marks')->first();
        $detailTable = AppTable::where('name', 'exam_mark_details')->first();

        if (!$headerTable || !$detailTable) {
            $this->command->warn('ExamMarksSeeder: run the migration first.');
            return;
        }

        // Skip if already seeded
        if (AppRecord::where('app_table_id', $headerTable->id)->exists()) {
            $this->command->info('Exam marks already seeded — skipping.');
            return;
        }

        // ── Resolve reference IDs ─────────────────────────────────────────────
        $studentIds = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'students'))
                        ->pluck('id')->toArray();
        $examIds    = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'exams'))
                        ->pluck('id')->toArray();
        $subjectIds = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'subjects'))
                        ->pluck('id')->toArray();
        $classIds   = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'classes'))
                        ->pluck('id')->toArray();
        $sectionIds = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'sections'))
                        ->pluck('id')->toArray();

        if (empty($studentIds) || empty($examIds) || empty($subjectIds)) {
            $this->command->warn('ExamMarksSeeder: students/exams/subjects not found. Run SchoolSeeder first.');
            return;
        }

        $writtenDates = ['2024-07-12', '2024-09-05', '2024-11-08'];

        // Create one header per student per exam (first 3 exams, first 5 students)
        foreach (array_slice($examIds, 0, 3) as $eIdx => $examId) {
            foreach (array_slice($studentIds, 0, 5) as $sIdx => $studentId) {

                // ── Header record ─────────────────────────────────────────────
                $header = AppRecord::create([
                    'app_table_id' => $headerTable->id,
                    'data'         => [
                        'student_id'    => $studentId,
                        'exam_id'       => $examId,
                        'class_id'      => $classIds[$sIdx]   ?? null,
                        'section_id'    => $sectionIds[$sIdx] ?? null,
                        'academic_year' => '2024-2025',
                        'remarks'       => null,
                    ],
                ]);

                // ── Detail rows — one per subject ─────────────────────────────
                foreach ($subjectIds as $subjectId) {
                    $obtained   = rand(45, 98);
                    $max        = 100;
                    $percentage = round(($obtained / $max) * 100, 2);

                    AppRecord::create([
                        'app_table_id'     => $detailTable->id,
                        'parent_record_id' => $header->id,
                        'data'             => [
                            'subject_id'     => $subjectId,
                            'written_date'   => $writtenDates[$eIdx] ?? null,
                            'max_marks'      => $max,
                            'marks_obtained' => $obtained,
                            'percentage'     => $percentage,
                            'grade'          => $this->calcGrade($percentage),
                            'remarks'        => null,
                        ],
                    ]);
                }
            }
        }

        $headers = AppRecord::where('app_table_id', $headerTable->id)->count();
        $details = AppRecord::where('app_table_id', $detailTable->id)->count();
        $this->command->info("Exam Marks seeded: {$headers} header records, {$details} subject detail rows.");
    }

    private function calcGrade(float $pct): string
    {
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B+';
        if ($pct >= 60) return 'B';
        if ($pct >= 50) return 'C';
        if ($pct >= 40) return 'D';
        return 'F';
    }
}
