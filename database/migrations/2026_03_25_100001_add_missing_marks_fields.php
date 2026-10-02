<?php

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $marks = AppTable::where('name', 'marks')->first();
        if (!$marks) return;

        $classes  = AppTable::where('name', 'classes')->first();
        $sections = AppTable::where('name', 'sections')->first();

        $missing = [
            ['name' => 'class_id',      'label' => 'Class',         'type' => 'reference', 'order' => 10,
             'reference_table_id' => $classes?->id],
            ['name' => 'section_id',    'label' => 'Section',       'type' => 'reference', 'order' => 11,
             'reference_table_id' => $sections?->id],
            ['name' => 'written_date',  'label' => 'Written Date',  'type' => 'date',      'order' => 12],
            ['name' => 'percentage',    'label' => 'Percentage',    'type' => 'currency',  'order' => 13],
        ];

        foreach ($missing as $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $marks->id, 'name' => $f['name']],
                array_merge([
                    'label'              => $f['label'],
                    'type'               => $f['type'],
                    'mandatory'          => false,
                    'display'            => false,
                    'active'             => true,
                    'order'              => $f['order'],
                    'reference_table_id' => $f['reference_table_id'] ?? null,
                ])
            );
        }
    }

    public function down(): void
    {
        $marks = AppTable::where('name', 'marks')->first();
        if (!$marks) return;

        AppField::where('app_table_id', $marks->id)
            ->whereIn('name', ['class_id', 'section_id', 'written_date', 'percentage'])
            ->delete();
    }
};
