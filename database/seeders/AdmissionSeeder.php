<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class AdmissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Ensure target (students) table exists ──────────────────────────
        $studentsTable = AppTable::where('name', 'students')->first();
        if (!$studentsTable) {
            $this->command->warn('Students table not found. Run StudentFieldsSeeder first.');
            return;
        }

        // ── 2. Create admissions table ────────────────────────────────────────
        $table = AppTable::firstOrCreate(['name' => 'admissions'], [
            'label'               => 'Admissions',
            'table_type'          => 'standard',
            'auto_number'         => true,
            'auto_number_prefix'  => 'ENQ-',
            'auto_number_base'    => 1000,
            'auto_number_padding' => 4,
            'auto_number_field'   => 'enquiry_no',
            // Promotion config
            'promote_to_table_id'  => $studentsTable->id,
            'promote_label'        => 'Confirm Admission → Move to Students',
            'promote_status_field' => 'status',
            'promote_status_value' => 'confirmed',
            // Field map: explicit renames + wildcard for the rest
            'promote_field_map'    => [
                // rename enquiry_no → skip (students use admission_no, auto-generated)
                ['from' => 'enquiry_no',    'to' => '_skip'],
                ['from' => 'enquiry_date',  'to' => 'admission_date'],
                // all other fields copy as-is (wildcard)
                ['from' => '*',             'to' => '*'],
            ],
        ]);

        // Update promotion config if table already existed
        $table->update([
            'promote_to_table_id'  => $studentsTable->id,
            'promote_label'        => 'Confirm Admission → Move to Students',
            'promote_status_field' => 'status',
            'promote_status_value' => 'confirmed',
            'promote_field_map'    => [
                ['from' => 'enquiry_no',   'to' => '_skip'],
                ['from' => 'enquiry_date', 'to' => 'admission_date'],
                ['from' => 'status',       'to' => '_skip'],   // admission status not copied
                ['from' => '_promoted_id', 'to' => '_skip'],   // internal field
                ['from' => 'parent_phone', 'to' => '_skip'],   // admission-only fields
                ['from' => 'parent_email', 'to' => '_skip'],
                ['from' => 'prev_school',  'to' => '_skip'],
                ['from' => 'prev_class',   'to' => '_skip'],
                ['from' => 'prev_percent', 'to' => '_skip'],
                ['from' => 'academic_year','to' => '_skip'],
                ['from' => '*',            'to' => '*'],        // copy rest as-is
            ],
        ]);

        // ── 3. Create/update fields ───────────────────────────────────────────
        // Helper: convert simple value list to choices format [{label, value}]
        $choices = fn(array $vals) => array_map(fn($v) => ['label' => ucfirst(str_replace('_', ' ', $v)), 'value' => $v], $vals);

        $fields = [
            // Identity
            ['name' => 'enquiry_no',    'label' => 'Enquiry No',        'type' => 'auto_number', 'order' => 1,  'readonly' => true,
             'auto_number_prefix' => 'ENQ-', 'auto_number_base' => 1000, 'auto_number_padding' => 4],
            ['name' => 'enquiry_date',  'label' => 'Enquiry Date',      'type' => 'date',      'order' => 2,  'mandatory' => true],
            ['name' => 'status',        'label' => 'Status',            'type' => 'select',    'order' => 3,
             'choices' => $choices(['enquiry','applied','under_review','confirmed','rejected']),
             'default_value' => 'enquiry'],

            // Student details
            ['name' => 'first_name',    'label' => 'First Name',        'type' => 'string',    'order' => 4,  'mandatory' => true, 'display' => true],
            ['name' => 'last_name',     'label' => 'Last Name',         'type' => 'string',    'order' => 5,  'mandatory' => true, 'display' => true],
            ['name' => 'date_of_birth', 'label' => 'Date of Birth',     'type' => 'date',      'order' => 6],
            ['name' => 'gender',        'label' => 'Gender',            'type' => 'select',    'order' => 7,
             'choices' => $choices(['male','female','other'])],
            ['name' => 'blood_group',   'label' => 'Blood Group',       'type' => 'select',    'order' => 8,
             'choices' => $choices(['A+','A-','B+','B-','O+','O-','AB+','AB-'])],
            ['name' => 'nationality',   'label' => 'Nationality',       'type' => 'string',    'order' => 9,  'default_value' => 'Indian'],
            ['name' => 'religion',      'label' => 'Religion',          'type' => 'string',    'order' => 10],
            ['name' => 'caste',         'label' => 'Caste',             'type' => 'string',    'order' => 11],
            ['name' => 'category',      'label' => 'Category',          'type' => 'select',    'order' => 12,
             'choices' => $choices(['general','obc','sc','st','ews'])],

            // Class applied for
            ['name' => 'class_id',      'label' => 'Class Applied For', 'type' => 'reference', 'order' => 13,
             'reference_table_id' => AppTable::where('name','classes')->value('id')],
            ['name' => 'section_id',    'label' => 'Section',           'type' => 'reference', 'order' => 14,
             'reference_table_id' => AppTable::where('name','sections')->value('id')],
            ['name' => 'academic_year', 'label' => 'Academic Year',     'type' => 'string',    'order' => 15],

            // Contact
            ['name' => 'phone',         'label' => 'Phone',             'type' => 'string',    'order' => 16],
            ['name' => 'email',         'label' => 'Email',             'type' => 'email',     'order' => 17],
            ['name' => 'whatsapp',      'label' => 'WhatsApp',          'type' => 'string',    'order' => 18],
            ['name' => 'address',       'label' => 'Address',           'type' => 'textarea',  'order' => 19],
            ['name' => 'city',          'label' => 'City',              'type' => 'string',    'order' => 20],
            ['name' => 'state',         'label' => 'State',             'type' => 'string',    'order' => 21],
            ['name' => 'pincode',       'label' => 'Pincode',           'type' => 'string',    'order' => 22],

            // Parent info
            ['name' => 'father_name',   'label' => "Father's Name",     'type' => 'string',    'order' => 23],
            ['name' => 'mother_name',   'label' => "Mother's Name",     'type' => 'string',    'order' => 24],
            ['name' => 'parent_phone',  'label' => 'Parent Phone',      'type' => 'string',    'order' => 25],
            ['name' => 'parent_email',  'label' => 'Parent Email',      'type' => 'email',     'order' => 26],

            // Previous school
            ['name' => 'prev_school',   'label' => 'Previous School',   'type' => 'string',    'order' => 27],
            ['name' => 'prev_class',    'label' => 'Previous Class',    'type' => 'string',    'order' => 28],
            ['name' => 'prev_percent',  'label' => 'Previous %',        'type' => 'decimal',   'order' => 29],

            // Internal
            ['name' => 'remarks',       'label' => 'Remarks',           'type' => 'textarea',  'order' => 30],
            ['name' => '_promoted_id',  'label' => 'Student Record ID', 'type' => 'integer',   'order' => 31, 'readonly' => true],
        ];

        foreach ($fields as $f) {
            AppField::updateOrCreate(
                ['app_table_id' => $table->id, 'name' => $f['name']],
                array_merge(['app_table_id' => $table->id], $f)
            );
        }

        $this->command->info('Admissions table seeded with promotion config → students.');
    }
}
