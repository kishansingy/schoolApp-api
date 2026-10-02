<?php

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // ── Resolve dependency tables ─────────────────────────────────────────
        $students = AppTable::where('name', 'students')->first();
        $exams    = AppTable::where('name', 'exams')->first();
        $subjects = AppTable::where('name', 'subjects')->first();
        $classes  = AppTable::where('name', 'classes')->first();
        $sections = AppTable::where('name', 'sections')->first();

        // ── 1. Header table: exam_marks ───────────────────────────────────────
        $header = AppTable::firstOrCreate(
            ['name' => 'exam_marks'],
            [
                'label'      => 'Exam Marks',
                'table_type' => 'header',
                'can_read'   => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ]
        );

        $headerFields = [
            ['name' => 'student_id',     'label' => 'Student',       'type' => 'reference', 'mandatory' => true,  'display' => true,  'order' => 0, 'ref' => $students?->id],
            ['name' => 'exam_id',        'label' => 'Exam',          'type' => 'reference', 'mandatory' => true,  'display' => true,  'order' => 1, 'ref' => $exams?->id],
            ['name' => 'class_id',       'label' => 'Class',         'type' => 'reference', 'mandatory' => false, 'display' => true,  'order' => 2, 'ref' => $classes?->id],
            ['name' => 'section_id',     'label' => 'Section',       'type' => 'reference', 'mandatory' => false, 'display' => false, 'order' => 3, 'ref' => $sections?->id],
            ['name' => 'academic_year',  'label' => 'Academic Year', 'type' => 'string',    'mandatory' => false, 'display' => true,  'order' => 4],
            ['name' => 'remarks',        'label' => 'Remarks',       'type' => 'text',      'mandatory' => false, 'display' => false, 'order' => 5],
        ];

        foreach ($headerFields as $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $header->id, 'name' => $f['name']],
                [
                    'label'              => $f['label'],
                    'type'               => $f['type'],
                    'mandatory'          => $f['mandatory'],
                    'display'            => $f['display'],
                    'active'             => true,
                    'order'              => $f['order'],
                    'reference_table_id' => $f['ref'] ?? null,
                ]
            );
        }

        // ── 2. Detail table: exam_mark_details ────────────────────────────────
        $detail = AppTable::firstOrCreate(
            ['name' => 'exam_mark_details'],
            [
                'label'              => 'Subject Marks',
                'table_type'         => 'detail',
                'detail_of_table_id' => $header->id,
                'can_read'           => true,
                'can_create'         => true,
                'can_update'         => true,
                'can_delete'         => true,
            ]
        );

        // Ensure detail_of_table_id is set even if record already existed
        if (!$detail->detail_of_table_id) {
            $detail->detail_of_table_id = $header->id;
            $detail->save();
        }

        $detailFields = [
            ['name' => 'subject_id',     'label' => 'Subject',        'type' => 'reference', 'mandatory' => true,  'display' => true,  'order' => 0, 'ref' => $subjects?->id],
            ['name' => 'written_date',   'label' => 'Written Date',   'type' => 'date',      'mandatory' => false, 'display' => true,  'order' => 1],
            ['name' => 'max_marks',      'label' => 'Max Marks',      'type' => 'integer',   'mandatory' => false, 'display' => true,  'order' => 2, 'default' => '100'],
            ['name' => 'marks_obtained', 'label' => 'Marks Obtained', 'type' => 'integer',   'mandatory' => true,  'display' => true,  'order' => 3],
            ['name' => 'percentage',     'label' => 'Percentage',     'type' => 'currency',  'mandatory' => false, 'display' => true,  'order' => 4],
            ['name' => 'grade',          'label' => 'Grade',          'type' => 'string',    'mandatory' => false, 'display' => true,  'order' => 5],
            ['name' => 'remarks',        'label' => 'Remarks',        'type' => 'text',      'mandatory' => false, 'display' => false, 'order' => 6],
        ];

        foreach ($detailFields as $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $detail->id, 'name' => $f['name']],
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
    }

    public function down(): void
    {
        $detail = AppTable::where('name', 'exam_mark_details')->first();
        if ($detail) {
            AppField::where('app_table_id', $detail->id)->delete();
            $detail->delete();
        }

        $header = AppTable::where('name', 'exam_marks')->first();
        if ($header) {
            AppField::where('app_table_id', $header->id)->delete();
            $header->delete();
        }
    }
};
