<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class MarksSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Ensure marks table exists with all required fields ─────────────
        $this->ensureMarksTable();

        // ── 2. Seed sample mark records ───────────────────────────────────────
        $this->seedMarkRecords();
    }

    // ── Table & field setup ───────────────────────────────────────────────────

    private function ensureMarksTable(): void
    {
        $exams    = AppTable::where('name', 'exams')->first();
        $students = AppTable::where('name', 'students')->first();
        $subjects = AppTable::where('name', 'subjects')->first();
        $classes  = AppTable::where('name', 'classes')->first();
        $sections = AppTable::where('name', 'sections')->first();

        $marks = AppTable::firstOrCreate(
            ['name' => 'marks'],
            [
                'label'      => 'Marks / Results',
                'table_type' => 'standard',
                'can_read'   => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ]
        );

        $fields = [
            ['name' => 'student_id',     'label' => 'Student',        'type' => 'reference', 'mandatory' => true,  'display' => true,  'order' => 0,  'ref' => $students?->id],
            ['name' => 'exam_id',        'label' => 'Exam',           'type' => 'reference', 'mandatory' => true,  'display' => true,  'order' => 1,  'ref' => $exams?->id],
            ['name' => 'subject_id',     'label' => 'Subject',        'type' => 'reference', 'mandatory' => true,  'display' => true,  'order' => 2,  'ref' => $subjects?->id],
            ['name' => 'class_id',       'label' => 'Class',          'type' => 'reference', 'mandatory' => false, 'display' => false, 'order' => 3,  'ref' => $classes?->id],
            ['name' => 'section_id',     'label' => 'Section',        'type' => 'reference', 'mandatory' => false, 'display' => false, 'order' => 4,  'ref' => $sections?->id],
            ['name' => 'written_date',   'label' => 'Written Date',   'type' => 'date',      'mandatory' => false, 'display' => false, 'order' => 5],
            ['name' => 'max_marks',      'label' => 'Max Marks',      'type' => 'currency',  'mandatory' => false, 'display' => true,  'order' => 6,  'default' => '100'],
            ['name' => 'marks_obtained', 'label' => 'Marks Obtained', 'type' => 'currency',  'mandatory' => true,  'display' => true,  'order' => 7],
            ['name' => 'percentage',     'label' => 'Percentage',     'type' => 'currency',  'mandatory' => false, 'display' => true,  'order' => 8],
            ['name' => 'grade',          'label' => 'Grade',          'type' => 'string',    'mandatory' => false, 'display' => true,  'order' => 9],
            ['name' => 'remarks',        'label' => 'Remarks',        'type' => 'text',      'mandatory' => false, 'display' => false, 'order' => 10],
        ];

        foreach ($fields as $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $marks->id, 'name' => $f['name']],
                [
                    'label'              => $f['label'],
                    'type'               => $f['type'],
                    'mandatory'          => $f['mandatory'],
                    'display'            => $f['display'],
                    'active'             => true,
                    'order'              => $f['order'],
                    'default_value'      => $f['default'] ?? null,
                    'reference_table_id' => $f['ref'] ?? null,
                ]
            );
        }

        $this->command->info('Marks table structure ensured.');
    }

    // ── Sample data ───────────────────────────────────────────────────────────

    private function seedMarkRecords(): void
    {
        $marksTable = AppTable::where('name', 'marks')->first();
        if (!$marksTable) return;

        // Skip if records already exist
        if (AppRecord::where('app_table_id', $marksTable->id)->exists()) {
            $this->command->info('Marks records already exist — skipping sample data.');
            return;
        }

        $examIds    = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'exams'))
                        ->pluck('id')->toArray();
        $studentIds = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'students'))
                        ->pluck('id')->toArray();
        $subjectIds = AppRecord::whereHas('appTable', fn($q) => $q->where('name', 'subjects'))
                        ->pluck('id')->toArray();

        if (empty($examIds) || empty($studentIds) || empty($subjectIds)) {
            $this->command->warn('Marks seeder: exams/students/subjects not found — run SchoolSeeder first.');
            return;
        }

        // Use first 3 exams, first 5 students, all subjects
        $examSample    = array_slice($examIds, 0, 3);
        $studentSample = array_slice($studentIds, 0, 5);

        $writtenDates = ['2024-07-12', '2024-09-05', '2024-11-08'];

        foreach ($examSample as $eIdx => $examId) {
            foreach ($studentSample as $studentId) {
                foreach ($subjectIds as $subjectId) {
                    $obtained   = rand(45, 98);
                    $max        = 100;
                    $percentage = round(($obtained / $max) * 100, 2);
                    $grade      = $this->calcGrade($percentage);

                    AppRecord::create([
                        'app_table_id' => $marksTable->id,
                        'data'         => [
                            'student_id'     => $studentId,
                            'exam_id'        => $examId,
                            'subject_id'     => $subjectId,
                            'written_date'   => $writtenDates[$eIdx] ?? null,
                            'max_marks'      => $max,
                            'marks_obtained' => $obtained,
                            'percentage'     => $percentage,
                            'grade'          => $grade,
                            'remarks'        => null,
                        ],
                    ]);
                }
            }
        }

        $count = count($examSample) * count($studentSample) * count($subjectIds);
        $this->command->info("Marks seeded: {$count} records.");
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
