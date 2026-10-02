<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

/**
 * Adds missing fields to existing school tables.
 * Safe to run multiple times — uses firstOrCreate.
 */
class StudentFieldsSeeder extends Seeder
{
    private function field(AppTable $table, string $name, string $label, string $type, array $opts = []): void
    {
        $order = AppField::where('app_table_id', $table->id)->max('order') + 1;

        AppField::firstOrCreate(
            ['app_table_id' => $table->id, 'name' => $name],
            array_merge([
                'label'              => $label,
                'type'               => $type,
                'mandatory'          => false,
                'display'            => false,
                'active'             => true,
                'order'              => $order,
                'choices'            => null,
                'default_value'      => null,
                'reference_table_id' => null,
            ], $opts)
        );
    }

    public function run(): void
    {
        $students = AppTable::where('name', 'students')->firstOrFail();
        $classes  = AppTable::where('name', 'classes')->first();
        $sections = AppTable::where('name', 'sections')->first();
        $parents  = AppTable::where('name', 'parents')->first();

        // ── Extra student personal fields ─────────────────────────────────────
        $this->field($students, 'whatsapp',    'WhatsApp Number', 'phone');
        $this->field($students, 'city',        'City',            'string');
        $this->field($students, 'state',       'State',           'string');
        $this->field($students, 'pincode',     'Pincode',         'string', ['max_length' => 10]);
        $this->field($students, 'category',    'Category',        'choice', [
            'choices' => [
                ['label' => 'General',  'value' => 'general'],
                ['label' => 'OBC',      'value' => 'obc'],
                ['label' => 'SC',       'value' => 'sc'],
                ['label' => 'ST',       'value' => 'st'],
                ['label' => 'Other',    'value' => 'other'],
            ],
        ]);
        $this->field($students, 'mother_tongue', 'Mother Tongue', 'string');
        $this->field($students, 'aadhar_no',   'Aadhar No',       'string', ['max_length' => 12]);

        // ── Class & Section assignment (reference fields on student) ──────────
        // These are convenience reference fields directly on the student record
        // (in addition to the class_sections detail table for history)
        if ($classes) {
            $this->field($students, 'class_id', 'Class', 'reference', [
                'reference_table_id' => $classes->id,
                'mandatory'          => false,
                'display'            => true,
            ]);
        }
        if ($sections) {
            $this->field($students, 'section_id', 'Section', 'reference', [
                'reference_table_id' => $sections->id,
                'display'            => true,
            ]);
        }

        // ── Parent / Guardian quick-reference on student ──────────────────────
        if ($parents) {
            $this->field($students, 'father_id', 'Father / Guardian', 'reference', [
                'reference_table_id' => $parents->id,
                'display'            => true,
            ]);
            $this->field($students, 'mother_id', 'Mother', 'reference', [
                'reference_table_id' => $parents->id,
            ]);
        }

        // ── Extra parent fields ───────────────────────────────────────────────
        if ($parents) {
            $this->field($parents, 'whatsapp',   'WhatsApp Number', 'phone');
            $this->field($parents, 'city',       'City',            'string');
            $this->field($parents, 'state',      'State',           'string');
            $this->field($parents, 'pincode',    'Pincode',         'string', ['max_length' => 10]);
            $this->field($parents, 'aadhar_no',  'Aadhar No',       'string', ['max_length' => 12]);
            $this->field($parents, 'annual_income', 'Annual Income','currency');
        }

        $this->command->info('Student & parent extra fields added.');
    }
}
