<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppTable;
use App\Models\FormLayout;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Base "task" table
        $task = AppTable::create(['name' => 'task', 'label' => 'Task']);

        $f1 = AppField::create(['app_table_id' => $task->id, 'name' => 'number',      'label' => 'Number',      'type' => 'string',  'readonly' => true,  'order' => 0]);
        $f2 = AppField::create(['app_table_id' => $task->id, 'name' => 'short_desc',  'label' => 'Short Description', 'type' => 'string', 'mandatory' => true, 'order' => 1]);
        $f3 = AppField::create(['app_table_id' => $task->id, 'name' => 'description', 'label' => 'Description', 'type' => 'text',    'order' => 2]);
        $f4 = AppField::create(['app_table_id' => $task->id, 'name' => 'priority',    'label' => 'Priority',    'type' => 'string',  'default_value' => 'Medium', 'order' => 3]);
        $f5 = AppField::create(['app_table_id' => $task->id, 'name' => 'due_date',    'label' => 'Due Date',    'type' => 'date',    'order' => 4]);
        $f6 = AppField::create(['app_table_id' => $task->id, 'name' => 'active',      'label' => 'Active',      'type' => 'boolean', 'default_value' => 'true', 'order' => 5]);

        // Form layout for task
        $layoutItems = [
            ['section' => 'Header',  'order' => 0, 'field' => $f1, 'sorder' => 0],
            ['section' => 'Header',  'order' => 0, 'field' => $f2, 'sorder' => 1],
            ['section' => 'Details', 'order' => 1, 'field' => $f3, 'sorder' => 0],
            ['section' => 'Details', 'order' => 1, 'field' => $f4, 'sorder' => 1],
            ['section' => 'Details', 'order' => 1, 'field' => $f5, 'sorder' => 2],
            ['section' => 'Details', 'order' => 1, 'field' => $f6, 'sorder' => 3],
        ];

        foreach ($layoutItems as $item) {
            FormLayout::create([
                'app_table_id'  => $task->id,
                'section_name'  => $item['section'],
                'section_order' => $item['order'],
                'app_field_id'  => $item['field']->id,
                'field_order'   => $item['sorder'],
            ]);
        }

        // "Incident" extends "Task"
        $incident = AppTable::create(['name' => 'incident', 'label' => 'Incident', 'parent_id' => $task->id]);

        $i1 = AppField::create(['app_table_id' => $incident->id, 'name' => 'category',  'label' => 'Category',  'type' => 'string', 'order' => 0]);
        $i2 = AppField::create(['app_table_id' => $incident->id, 'name' => 'impact',    'label' => 'Impact',    'type' => 'string', 'default_value' => 'Low', 'order' => 1]);
        $i3 = AppField::create(['app_table_id' => $incident->id, 'name' => 'resolved',  'label' => 'Resolved',  'type' => 'boolean','default_value' => 'false', 'order' => 2]);

        // Form layout for incident (inherits task fields + own)
        $incidentLayout = [
            ['section' => 'Header',   'sorder' => 0, 'field' => $f1, 'forder' => 0],
            ['section' => 'Header',   'sorder' => 0, 'field' => $f2, 'forder' => 1],
            ['section' => 'Details',  'sorder' => 1, 'field' => $f3, 'forder' => 0],
            ['section' => 'Details',  'sorder' => 1, 'field' => $f4, 'forder' => 1],
            ['section' => 'Incident', 'sorder' => 2, 'field' => $i1, 'forder' => 0],
            ['section' => 'Incident', 'sorder' => 2, 'field' => $i2, 'forder' => 1],
            ['section' => 'Incident', 'sorder' => 2, 'field' => $i3, 'forder' => 2],
        ];

        foreach ($incidentLayout as $item) {
            FormLayout::create([
                'app_table_id'  => $incident->id,
                'section_name'  => $item['section'],
                'section_order' => $item['sorder'],
                'app_field_id'  => $item['field']->id,
                'field_order'   => $item['forder'],
            ]);
        }
    }
}
