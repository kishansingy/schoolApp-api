<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    // ── Helper ────────────────────────────────────────────────────────────────

    private function makeTable(string $name, string $label, string $type = 'standard', ?int $parentId = null): AppTable
    {
        return AppTable::firstOrCreate(['name' => $name], [
            'label'       => $label,
            'table_type'  => $type,
            'can_read'    => true,
            'can_create'  => true,
            'can_update'  => true,
            'can_delete'  => true,
        ]);
    }

    private function addFields(AppTable $table, array $fields): void
    {
        foreach ($fields as $i => $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $table->id, 'name' => $f['name']],
                array_merge([
                    'label'     => $f['label'],
                    'type'      => $f['type'] ?? 'string',
                    'mandatory' => $f['mandatory'] ?? false,
                    'display'   => $f['display']   ?? false,
                    'active'    => true,
                    'order'     => $i,
                    'choices'   => $f['choices'] ?? null,
                    'default_value' => $f['default'] ?? null,
                    'reference_table_id' => $f['ref_table_id'] ?? null,
                ], $f['extra'] ?? [])
            );
        }
    }

    public function run(): void
    {
        $this->seedStudents();
        $this->seedParents();
        $this->seedStaff();
        $this->seedClasses();
        $this->seedSubjects();
        $this->seedExams();
        $this->seedAttendance();
        $this->seedFee();
        $this->seedLibrary();
        $this->seedTransport();
        $this->seedHostel();
        $this->seedNotices();
        $this->seedTimetable();

        $this->command->info('School tables & fields seeded successfully.');
    }

    // ── Students ──────────────────────────────────────────────────────────────

    private function seedStudents(): void
    {
        $t = $this->makeTable('students', 'Students', 'header');

        // Enable auto-numbering for admission_no
        $t->auto_number         = true;
        $t->auto_number_prefix  = 'ADM-';
        $t->auto_number_suffix  = '';
        $t->auto_number_base    = 1000;
        $t->auto_number_padding = 4;
        $t->auto_number_field   = 'admission_no';
        $t->save();
        $this->addFields($t, [
            ['name' => 'admission_no',   'label' => 'Admission No',   'type' => 'string',    'mandatory' => true,  'display' => true, 'extra' => ['readonly' => true]],
            ['name' => 'first_name',     'label' => 'First Name',     'type' => 'string',    'mandatory' => true,  'display' => true],
            ['name' => 'last_name',      'label' => 'Last Name',      'type' => 'string',    'mandatory' => true,  'display' => true],
            ['name' => 'date_of_birth',  'label' => 'Date of Birth',  'type' => 'date'],
            ['name' => 'gender',         'label' => 'Gender',         'type' => 'choice',
                'choices' => [['label'=>'Male','value'=>'male'],['label'=>'Female','value'=>'female'],['label'=>'Other','value'=>'other']]],
            ['name' => 'blood_group',    'label' => 'Blood Group',    'type' => 'string'],
            ['name' => 'religion',       'label' => 'Religion',       'type' => 'string'],
            ['name' => 'nationality',    'label' => 'Nationality',    'type' => 'string',    'default' => 'Indian'],
            ['name' => 'phone',          'label' => 'Phone',          'type' => 'phone'],
            ['name' => 'email',          'label' => 'Email',          'type' => 'email'],
            ['name' => 'address',        'label' => 'Address',        'type' => 'text'],
            ['name' => 'admission_date', 'label' => 'Admission Date', 'type' => 'date'],
            ['name' => 'status',         'label' => 'Status',         'type' => 'choice',    'default' => 'active',
                'choices' => [['label'=>'Active','value'=>'active'],['label'=>'Inactive','value'=>'inactive'],
                              ['label'=>'Transferred','value'=>'transferred'],['label'=>'Graduated','value'=>'graduated']]],
            ['name' => 'photo',          'label' => 'Photo',          'type' => 'image'],
        ]);
    }

    // ── Parents ───────────────────────────────────────────────────────────────

    private function seedParents(): void
    {
        $t = $this->makeTable('parents', 'Parents');
        $this->addFields($t, [
            ['name' => 'first_name',  'label' => 'First Name',  'type' => 'string', 'mandatory' => true, 'display' => true],
            ['name' => 'last_name',   'label' => 'Last Name',   'type' => 'string', 'mandatory' => true, 'display' => true],
            ['name' => 'relation',    'label' => 'Relation',    'type' => 'choice', 'mandatory' => true,
                'choices' => [['label'=>'Father','value'=>'father'],['label'=>'Mother','value'=>'mother'],['label'=>'Guardian','value'=>'guardian']]],
            ['name' => 'phone',       'label' => 'Phone',       'type' => 'phone',  'mandatory' => true, 'display' => true],
            ['name' => 'email',       'label' => 'Email',       'type' => 'email'],
            ['name' => 'occupation',  'label' => 'Occupation',  'type' => 'string'],
            ['name' => 'address',     'label' => 'Address',     'type' => 'text'],
            ['name' => 'national_id', 'label' => 'National ID', 'type' => 'string'],
        ]);

        // Student-Parent link table
        $students = AppTable::where('name', 'students')->first();
        $link = $this->makeTable('student_parent_link', 'Student-Parent Link', 'detail');
        $this->addFields($link, [
            ['name' => 'student_id',  'label' => 'Student',    'type' => 'reference', 'mandatory' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'parent_id',   'label' => 'Parent',     'type' => 'reference', 'mandatory' => true,
                'ref_table_id' => $t->id],
            ['name' => 'is_primary',  'label' => 'Is Primary', 'type' => 'boolean',   'default' => 'false'],
        ]);
    }

    // ── Staff ─────────────────────────────────────────────────────────────────

    private function seedStaff(): void
    {
        $t = $this->makeTable('staff', 'Staff');
        $this->addFields($t, [
            ['name' => 'staff_no',      'label' => 'Staff No',       'type' => 'string',   'mandatory' => true, 'display' => true],
            ['name' => 'first_name',    'label' => 'First Name',     'type' => 'string',   'mandatory' => true, 'display' => true],
            ['name' => 'last_name',     'label' => 'Last Name',      'type' => 'string',   'mandatory' => true, 'display' => true],
            ['name' => 'staff_type',    'label' => 'Staff Type',     'type' => 'choice',   'mandatory' => true,
                'choices' => [['label'=>'Teaching','value'=>'teaching'],['label'=>'Non-Teaching','value'=>'non_teaching']]],
            ['name' => 'designation',   'label' => 'Designation',    'type' => 'string'],
            ['name' => 'department',    'label' => 'Department',     'type' => 'string'],
            ['name' => 'qualification', 'label' => 'Qualification',  'type' => 'string'],
            ['name' => 'date_of_birth', 'label' => 'Date of Birth',  'type' => 'date'],
            ['name' => 'gender',        'label' => 'Gender',         'type' => 'choice',
                'choices' => [['label'=>'Male','value'=>'male'],['label'=>'Female','value'=>'female'],['label'=>'Other','value'=>'other']]],
            ['name' => 'phone',         'label' => 'Phone',          'type' => 'phone',    'mandatory' => true],
            ['name' => 'email',         'label' => 'Email',          'type' => 'email'],
            ['name' => 'address',       'label' => 'Address',        'type' => 'text'],
            ['name' => 'joining_date',  'label' => 'Joining Date',   'type' => 'date'],
            ['name' => 'salary',        'label' => 'Salary',         'type' => 'currency'],
            ['name' => 'status',        'label' => 'Status',         'type' => 'choice',   'default' => 'active',
                'choices' => [['label'=>'Active','value'=>'active'],['label'=>'Inactive','value'=>'inactive'],['label'=>'Resigned','value'=>'resigned']]],
        ]);
    }

    // ── Classes & Sections ────────────────────────────────────────────────────

    private function seedClasses(): void
    {
        $t = $this->makeTable('classes', 'Classes', 'header');
        $this->addFields($t, [
            ['name' => 'name',  'label' => 'Class Name', 'type' => 'string',  'mandatory' => true, 'display' => true],
            ['name' => 'order', 'label' => 'Order',      'type' => 'integer', 'default' => '0'],
        ]);

        $staff = AppTable::where('name', 'staff')->first();

        // Sections is a detail of Classes
        $s = $this->makeTable('sections', 'Sections', 'detail');
        // Set detail_of_table_id to link sections → classes
        $s->detail_of_table_id = $t->id;
        $s->save();

        $this->addFields($s, [
            ['name' => 'class_id',         'label' => 'Class',         'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $t->id],
            ['name' => 'name',             'label' => 'Section Name',  'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'class_teacher_id', 'label' => 'Class Teacher', 'type' => 'reference',
                'ref_table_id' => $staff?->id],
            ['name' => 'capacity',         'label' => 'Capacity',      'type' => 'integer',   'default' => '40'],
        ]);

        // Class-Section assignment table (which student belongs to which class+section)
        $students = AppTable::where('name', 'students')->first();
        $cs = $this->makeTable('class_sections', 'Class-Section Assignments', 'detail');
        $cs->detail_of_table_id = $t->id;
        $cs->save();

        $this->addFields($cs, [
            ['name' => 'student_id',  'label' => 'Student',         'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'class_id',    'label' => 'Class',           'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $t->id],
            ['name' => 'section_id',  'label' => 'Section',         'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $s->id],
            ['name' => 'roll_no',     'label' => 'Roll No',         'type' => 'string'],
            ['name' => 'academic_year','label' => 'Academic Year',  'type' => 'string',    'mandatory' => true],
            ['name' => 'status',      'label' => 'Status',          'type' => 'choice',    'default' => 'active',
                'choices' => [['label'=>'Active','value'=>'active'],['label'=>'Inactive','value'=>'inactive']]],
        ]);
    }

    // ── Subjects ──────────────────────────────────────────────────────────────

    private function seedSubjects(): void
    {
        $classes = AppTable::where('name', 'classes')->first();
        $staff   = AppTable::where('name', 'staff')->first();
        $t = $this->makeTable('subjects', 'Subjects');
        $this->addFields($t, [
            ['name' => 'name',       'label' => 'Subject Name', 'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'code',       'label' => 'Subject Code', 'type' => 'string',    'display' => true],
            ['name' => 'type',       'label' => 'Type',         'type' => 'choice',    'default' => 'theory',
                'choices' => [['label'=>'Theory','value'=>'theory'],['label'=>'Practical','value'=>'practical'],['label'=>'Both','value'=>'both']]],
            ['name' => 'class_id',   'label' => 'Class',        'type' => 'reference', 'mandatory' => true,
                'ref_table_id' => $classes?->id],
            ['name' => 'teacher_id', 'label' => 'Teacher',      'type' => 'reference',
                'ref_table_id' => $staff?->id],
        ]);
    }

    // ── Exams & Marks ─────────────────────────────────────────────────────────

    private function seedExams(): void
    {
        $t = $this->makeTable('exams', 'Exams', 'header');
        $this->addFields($t, [
            ['name' => 'name',          'label' => 'Exam Name',     'type' => 'string', 'mandatory' => true, 'display' => true],
            ['name' => 'academic_year', 'label' => 'Academic Year', 'type' => 'string', 'mandatory' => true, 'display' => true],
            ['name' => 'start_date',    'label' => 'Start Date',    'type' => 'date'],
            ['name' => 'end_date',      'label' => 'End Date',      'type' => 'date'],
            ['name' => 'status',        'label' => 'Status',        'type' => 'choice', 'default' => 'upcoming',
                'choices' => [['label'=>'Upcoming','value'=>'upcoming'],['label'=>'Ongoing','value'=>'ongoing'],['label'=>'Completed','value'=>'completed']]],
        ]);

        $students = AppTable::where('name', 'students')->first();
        $subjects = AppTable::where('name', 'subjects')->first();
        $classes  = AppTable::where('name', 'classes')->first();
        $sections = AppTable::where('name', 'sections')->first();
        $marks = $this->makeTable('marks', 'Marks / Results', 'standard');
        $this->addFields($marks, [
            ['name' => 'student_id',     'label' => 'Student',        'type' => 'reference', 'mandatory' => true,  'display' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'exam_id',        'label' => 'Exam',           'type' => 'reference', 'mandatory' => true,  'display' => true,
                'ref_table_id' => $t->id],
            ['name' => 'subject_id',     'label' => 'Subject',        'type' => 'reference', 'mandatory' => true,  'display' => true,
                'ref_table_id' => $subjects?->id],
            ['name' => 'class_id',       'label' => 'Class',          'type' => 'reference', 'mandatory' => false, 'display' => false,
                'ref_table_id' => $classes?->id],
            ['name' => 'section_id',     'label' => 'Section',        'type' => 'reference', 'mandatory' => false, 'display' => false,
                'ref_table_id' => $sections?->id],
            ['name' => 'written_date',   'label' => 'Written Date',   'type' => 'date',      'mandatory' => false, 'display' => false],
            ['name' => 'max_marks',      'label' => 'Max Marks',      'type' => 'currency',  'display' => true,    'default' => '100'],
            ['name' => 'marks_obtained', 'label' => 'Marks Obtained', 'type' => 'currency',  'mandatory' => true,  'display' => true],
            ['name' => 'percentage',     'label' => 'Percentage',     'type' => 'currency',  'display' => true],
            ['name' => 'grade',          'label' => 'Grade',          'type' => 'string',    'display' => true],
            ['name' => 'remarks',        'label' => 'Remarks',        'type' => 'text'],
        ]);
    }

    // ── Attendance ────────────────────────────────────────────────────────────

    private function seedAttendance(): void
    {
        $students = AppTable::where('name', 'students')->first();
        $staff    = AppTable::where('name', 'staff')->first();

        $sa = $this->makeTable('student_attendance', 'Student Attendance');
        $this->addFields($sa, [
            ['name' => 'student_id', 'label' => 'Student', 'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'date',       'label' => 'Date',    'type' => 'date',      'mandatory' => true, 'display' => true],
            ['name' => 'status',     'label' => 'Status',  'type' => 'choice',    'mandatory' => true, 'default' => 'present',
                'choices' => [['label'=>'Present','value'=>'present'],['label'=>'Absent','value'=>'absent'],
                              ['label'=>'Late','value'=>'late'],['label'=>'Half Day','value'=>'half_day'],['label'=>'Holiday','value'=>'holiday']]],
            ['name' => 'remarks',    'label' => 'Remarks', 'type' => 'text'],
        ]);

        $stf = $this->makeTable('staff_attendance', 'Staff Attendance');
        $this->addFields($stf, [
            ['name' => 'staff_id', 'label' => 'Staff',   'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $staff?->id],
            ['name' => 'date',     'label' => 'Date',    'type' => 'date',      'mandatory' => true, 'display' => true],
            ['name' => 'status',   'label' => 'Status',  'type' => 'choice',    'mandatory' => true, 'default' => 'present',
                'choices' => [['label'=>'Present','value'=>'present'],['label'=>'Absent','value'=>'absent'],
                              ['label'=>'Late','value'=>'late'],['label'=>'Half Day','value'=>'half_day'],['label'=>'Leave','value'=>'leave']]],
            ['name' => 'remarks',  'label' => 'Remarks', 'type' => 'text'],
        ]);
    }

    // ── Fee ───────────────────────────────────────────────────────────────────

    private function seedFee(): void
    {
        $classes  = AppTable::where('name', 'classes')->first();
        $students = AppTable::where('name', 'students')->first();

        $fs = $this->makeTable('fee_structures', 'Fee Structures', 'header');
        $this->addFields($fs, [
            ['name' => 'class_id',      'label' => 'Class',          'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $classes?->id],
            ['name' => 'fee_type',      'label' => 'Fee Type',       'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'amount',        'label' => 'Amount',         'type' => 'currency',  'mandatory' => true],
            ['name' => 'frequency',     'label' => 'Frequency',      'type' => 'choice',    'default' => 'monthly',
                'choices' => [['label'=>'Monthly','value'=>'monthly'],['label'=>'Quarterly','value'=>'quarterly'],
                              ['label'=>'Annually','value'=>'annually'],['label'=>'One Time','value'=>'one_time']]],
            ['name' => 'academic_year', 'label' => 'Academic Year',  'type' => 'string',    'mandatory' => true],
        ]);

        $fp = $this->makeTable('fee_payments', 'Fee Payments', 'detail');
        $this->addFields($fp, [
            ['name' => 'student_id',       'label' => 'Student',       'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'fee_structure_id', 'label' => 'Fee Type',      'type' => 'reference', 'mandatory' => true,
                'ref_table_id' => $fs->id],
            ['name' => 'amount_paid',      'label' => 'Amount Paid',   'type' => 'currency',  'mandatory' => true, 'display' => true],
            ['name' => 'discount',         'label' => 'Discount',      'type' => 'currency',  'default' => '0'],
            ['name' => 'fine',             'label' => 'Fine',          'type' => 'currency',  'default' => '0'],
            ['name' => 'payment_date',     'label' => 'Payment Date',  'type' => 'date',      'mandatory' => true, 'display' => true],
            ['name' => 'receipt_no',       'label' => 'Receipt No',    'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'payment_mode',     'label' => 'Payment Mode',  'type' => 'choice',    'default' => 'cash',
                'choices' => [['label'=>'Cash','value'=>'cash'],['label'=>'Cheque','value'=>'cheque'],
                              ['label'=>'Online','value'=>'online'],['label'=>'Bank Transfer','value'=>'bank_transfer']]],
            ['name' => 'status',           'label' => 'Status',        'type' => 'choice',    'default' => 'paid',
                'choices' => [['label'=>'Paid','value'=>'paid'],['label'=>'Partial','value'=>'partial'],['label'=>'Pending','value'=>'pending']]],
            ['name' => 'remarks',          'label' => 'Remarks',       'type' => 'text'],
        ]);
    }

    // ── Library ───────────────────────────────────────────────────────────────

    private function seedLibrary(): void
    {
        $students = AppTable::where('name', 'students')->first();
        $staff    = AppTable::where('name', 'staff')->first();

        $books = $this->makeTable('books', 'Books', 'header');
        $this->addFields($books, [
            ['name' => 'title',            'label' => 'Title',             'type' => 'string',  'mandatory' => true, 'display' => true],
            ['name' => 'author',           'label' => 'Author',            'type' => 'string',  'display' => true],
            ['name' => 'isbn',             'label' => 'ISBN',              'type' => 'string'],
            ['name' => 'publisher',        'label' => 'Publisher',         'type' => 'string'],
            ['name' => 'publish_year',     'label' => 'Publish Year',      'type' => 'integer'],
            ['name' => 'category',         'label' => 'Category',          'type' => 'string'],
            ['name' => 'total_copies',     'label' => 'Total Copies',      'type' => 'integer', 'default' => '1'],
            ['name' => 'available_copies', 'label' => 'Available Copies',  'type' => 'integer', 'default' => '1'],
        ]);

        $issues = $this->makeTable('book_issues', 'Book Issues', 'detail');
        $this->addFields($issues, [
            ['name' => 'book_id',      'label' => 'Book',        'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $books->id],
            ['name' => 'student_id',   'label' => 'Student',     'type' => 'reference',
                'ref_table_id' => $students?->id],
            ['name' => 'staff_id',     'label' => 'Staff',       'type' => 'reference',
                'ref_table_id' => $staff?->id],
            ['name' => 'issue_date',   'label' => 'Issue Date',  'type' => 'date',      'mandatory' => true, 'display' => true],
            ['name' => 'due_date',     'label' => 'Due Date',    'type' => 'date',      'mandatory' => true, 'display' => true],
            ['name' => 'return_date',  'label' => 'Return Date', 'type' => 'date'],
            ['name' => 'fine_amount',  'label' => 'Fine Amount', 'type' => 'currency',  'default' => '0'],
            ['name' => 'status',       'label' => 'Status',      'type' => 'choice',    'default' => 'issued',
                'choices' => [['label'=>'Issued','value'=>'issued'],['label'=>'Returned','value'=>'returned'],['label'=>'Overdue','value'=>'overdue']]],
        ]);
    }

    // ── Transport ─────────────────────────────────────────────────────────────

    private function seedTransport(): void
    {
        $students = AppTable::where('name', 'students')->first();
        $staff    = AppTable::where('name', 'staff')->first();

        $routes = $this->makeTable('transport_routes', 'Transport Routes');
        $this->addFields($routes, [
            ['name' => 'route_name',      'label' => 'Route Name',    'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'route_no',        'label' => 'Route No',      'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'stops',           'label' => 'Stops',         'type' => 'text'],
            ['name' => 'driver_staff_id', 'label' => 'Driver',        'type' => 'reference',
                'ref_table_id' => $staff?->id],
            ['name' => 'vehicle_no',      'label' => 'Vehicle No',    'type' => 'string'],
            ['name' => 'vehicle_type',    'label' => 'Vehicle Type',  'type' => 'string'],
            ['name' => 'capacity',        'label' => 'Capacity',      'type' => 'integer',   'default' => '40'],
            ['name' => 'monthly_fee',     'label' => 'Monthly Fee',   'type' => 'currency',  'default' => '0'],
        ]);

        $st = $this->makeTable('student_transport', 'Student Transport');
        $this->addFields($st, [
            ['name' => 'student_id',  'label' => 'Student',      'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'route_id',    'label' => 'Route',        'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $routes->id],
            ['name' => 'pickup_stop', 'label' => 'Pickup Stop',  'type' => 'string'],
            ['name' => 'from_date',   'label' => 'From Date',    'type' => 'date'],
            ['name' => 'to_date',     'label' => 'To Date',      'type' => 'date'],
            ['name' => 'status',      'label' => 'Status',       'type' => 'choice',    'default' => 'active',
                'choices' => [['label'=>'Active','value'=>'active'],['label'=>'Inactive','value'=>'inactive']]],
        ]);
    }

    // ── Hostel ────────────────────────────────────────────────────────────────

    private function seedHostel(): void
    {
        $students = AppTable::where('name', 'students')->first();
        $staff    = AppTable::where('name', 'staff')->first();

        $hostels = $this->makeTable('hostels', 'Hostels');
        $this->addFields($hostels, [
            ['name' => 'name',             'label' => 'Hostel Name',  'type' => 'string',    'mandatory' => true, 'display' => true],
            ['name' => 'type',             'label' => 'Type',         'type' => 'choice',    'mandatory' => true,
                'choices' => [['label'=>'Boys','value'=>'boys'],['label'=>'Girls','value'=>'girls'],['label'=>'Mixed','value'=>'mixed']]],
            ['name' => 'capacity',         'label' => 'Capacity',     'type' => 'integer',   'default' => '100'],
            ['name' => 'warden_staff_id',  'label' => 'Warden',       'type' => 'reference',
                'ref_table_id' => $staff?->id],
            ['name' => 'monthly_fee',      'label' => 'Monthly Fee',  'type' => 'currency',  'default' => '0'],
        ]);

        $allot = $this->makeTable('hostel_allotments', 'Hostel Allotments');
        $this->addFields($allot, [
            ['name' => 'student_id', 'label' => 'Student',    'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $students?->id],
            ['name' => 'hostel_id',  'label' => 'Hostel',     'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $hostels->id],
            ['name' => 'room_no',    'label' => 'Room No',    'type' => 'string'],
            ['name' => 'from_date',  'label' => 'From Date',  'type' => 'date'],
            ['name' => 'to_date',    'label' => 'To Date',    'type' => 'date'],
            ['name' => 'status',     'label' => 'Status',     'type' => 'choice',    'default' => 'active',
                'choices' => [['label'=>'Active','value'=>'active'],['label'=>'Vacated','value'=>'vacated']]],
        ]);
    }

    // ── Notices ───────────────────────────────────────────────────────────────

    private function seedNotices(): void
    {
        $t = $this->makeTable('notices', 'Notices & Announcements');
        $this->addFields($t, [
            ['name' => 'title',        'label' => 'Title',        'type' => 'string', 'mandatory' => true, 'display' => true],
            ['name' => 'content',      'label' => 'Content',      'type' => 'html',   'mandatory' => true],
            ['name' => 'audience',     'label' => 'Audience',     'type' => 'choice', 'default' => 'all',
                'choices' => [['label'=>'All','value'=>'all'],['label'=>'Students','value'=>'students'],
                              ['label'=>'Parents','value'=>'parents'],['label'=>'Staff','value'=>'staff'],['label'=>'Teachers','value'=>'teachers']]],
            ['name' => 'publish_date', 'label' => 'Publish Date', 'type' => 'date'],
            ['name' => 'expiry_date',  'label' => 'Expiry Date',  'type' => 'date'],
        ]);
    }

    // ── Timetable ─────────────────────────────────────────────────────────────

    private function seedTimetable(): void
    {
        $sections = AppTable::where('name', 'sections')->first();
        $subjects = AppTable::where('name', 'subjects')->first();
        $staff    = AppTable::where('name', 'staff')->first();

        $t = $this->makeTable('timetable', 'Timetable');
        $this->addFields($t, [
            ['name' => 'section_id', 'label' => 'Section',     'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $sections?->id],
            ['name' => 'subject_id', 'label' => 'Subject',     'type' => 'reference', 'mandatory' => true, 'display' => true,
                'ref_table_id' => $subjects?->id],
            ['name' => 'staff_id',   'label' => 'Teacher',     'type' => 'reference', 'mandatory' => true,
                'ref_table_id' => $staff?->id],
            ['name' => 'day',        'label' => 'Day',         'type' => 'choice',    'mandatory' => true,
                'choices' => [['label'=>'Monday','value'=>'monday'],['label'=>'Tuesday','value'=>'tuesday'],
                              ['label'=>'Wednesday','value'=>'wednesday'],['label'=>'Thursday','value'=>'thursday'],
                              ['label'=>'Friday','value'=>'friday'],['label'=>'Saturday','value'=>'saturday']]],
            ['name' => 'start_time', 'label' => 'Start Time',  'type' => 'string',    'mandatory' => true],
            ['name' => 'end_time',   'label' => 'End Time',    'type' => 'string',    'mandatory' => true],
            ['name' => 'room',       'label' => 'Room',        'type' => 'string'],
        ]);
    }
}
