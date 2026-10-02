<?php

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $header  = AppTable::where('name', 'exam_marks')->first();
        $detail  = AppTable::where('name', 'exam_mark_details')->first();

        if (!$header) return;

        // ── Footer table linked to exam_marks header ───────────────────────────
        $footer = AppTable::firstOrCreate(
            ['name' => 'exam_marks_footer'],
            [
                'label'              => 'Marks Summary',
                'table_type'         => 'footer',
                'detail_of_table_id' => $header->id,
                'can_read'           => true,
                'can_create'         => true,
                'can_update'         => true,
                'can_delete'         => true,
            ]
        );

        if (!$footer->detail_of_table_id) {
            $footer->detail_of_table_id = $header->id;
            $footer->save();
        }

        // Footer fields — all calculated using sum() from the detail table
        $detailName = $detail?->name ?? 'exam_mark_details';

        $fields = [
            [
                'name'             => 'total_subjects',
                'label'            => 'Total Subjects',
                'type'             => 'integer',
                'calculated'       => true,
                'calculated_value' => "count({$detailName}.subject_id)",
                'order'            => 0,
            ],
            [
                'name'             => 'total_max_marks',
                'label'            => 'Total Max Marks',
                'type'             => 'currency',
                'calculated'       => true,
                'calculated_value' => "sum({$detailName}.max_marks)",
                'order'            => 1,
            ],
            [
                'name'             => 'total_obtained',
                'label'            => 'Total Marks Obtained',
                'type'             => 'currency',
                'calculated'       => true,
                'calculated_value' => "sum({$detailName}.marks_obtained)",
                'order'            => 2,
            ],
            [
                'name'             => 'overall_percentage',
                'label'            => 'Overall Percentage',
                'type'             => 'currency',
                'calculated'       => true,
                'calculated_value' => "percentage({$detailName}.marks_obtained,{$detailName}.max_marks)",
                'order'            => 3,
            ],
        ];

        foreach ($fields as $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $footer->id, 'name' => $f['name']],
                [
                    'label'            => $f['label'],
                    'type'             => $f['type'],
                    'calculated'       => $f['calculated'],
                    'calculated_value' => $f['calculated_value'],
                    'mandatory'        => false,
                    'display'          => true,
                    'active'           => true,
                    'order'            => $f['order'],
                ]
            );
        }
    }

    public function down(): void
    {
        $footer = AppTable::where('name', 'exam_marks_footer')->first();
        if ($footer) {
            AppField::where('app_table_id', $footer->id)->delete();
            $footer->delete();
        }
    }
};
