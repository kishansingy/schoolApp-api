<?php

namespace Database\Seeders;

use App\Models\ReportDefinition;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [

            // ── ATTENDANCE ────────────────────────────────────────────────────
            [
                'name'        => 'Daily Attendance Summary',
                'category'    => 'Attendance',
                'icon'        => '✅',
                'description' => 'Total present, absent, late per day with percentage',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.date')) AS date,
                        SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(data,'$.status'))='present' THEN 1 ELSE 0 END) AS present,
                        SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(data,'$.status'))='absent'  THEN 1 ELSE 0 END) AS absent,
                        SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(data,'$.status'))='late'    THEN 1 ELSE 0 END) AS late,
                        COUNT(*) AS total,
                        ROUND(SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) IN ('present','late') THEN 1 ELSE 0 END)*100.0/COUNT(*),1) AS percentage
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='student_attendance' LIMIT 1)
                    AND (:date_from IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.date')) >= :date_from)
                    AND (:date_to   IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.date')) <= :date_to)
                    GROUP BY date ORDER BY date DESC
                ",
                'filters' => [
                    ['name' => 'date_from', 'label' => 'From Date', 'type' => 'date'],
                    ['name' => 'date_to',   'label' => 'To Date',   'type' => 'date'],
                ],
                'columns' => [
                    ['key' => 'date',       'label' => 'Date'],
                    ['key' => 'present',    'label' => 'Present'],
                    ['key' => 'absent',     'label' => 'Absent'],
                    ['key' => 'late',       'label' => 'Late'],
                    ['key' => 'total',      'label' => 'Total'],
                    ['key' => 'percentage', 'label' => 'Attendance %'],
                ],
                'order' => 1,
            ],

            [
                'name'        => 'Student Attendance by Class',
                'category'    => 'Attendance',
                'icon'        => '🏫',
                'description' => 'Attendance percentage grouped by class for a date range',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.date')) AS date,
                        JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.status')) AS status,
                        COUNT(*) AS count
                    FROM app_records a
                    WHERE a.app_table_id = (SELECT id FROM app_tables WHERE name='student_attendance' LIMIT 1)
                    AND (:date_from IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.date')) >= :date_from)
                    AND (:date_to   IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.date')) <= :date_to)
                    GROUP BY date, status ORDER BY date DESC, status
                ",
                'filters' => [
                    ['name' => 'date_from', 'label' => 'From Date', 'type' => 'date'],
                    ['name' => 'date_to',   'label' => 'To Date',   'type' => 'date'],
                ],
                'columns' => [
                    ['key' => 'date',   'label' => 'Date'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'count',  'label' => 'Count'],
                ],
                'order' => 2,
            ],

            [
                'name'        => 'Absentee List',
                'category'    => 'Attendance',
                'icon'        => '❌',
                'description' => 'Students who were absent on a specific date',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.date'))       AS date,
                        JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.first_name')) AS first_name,
                        JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.last_name'))  AS last_name,
                        JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.admission_no')) AS admission_no,
                        JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.remarks'))    AS remarks
                    FROM app_records a
                    JOIN app_records s ON s.id = JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.student_id'))
                    WHERE a.app_table_id = (SELECT id FROM app_tables WHERE name='student_attendance' LIMIT 1)
                    AND JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.status')) = 'absent'
                    AND (:date IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(a.data,'$.date')) = :date)
                    ORDER BY last_name, first_name
                ",
                'filters' => [
                    ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                ],
                'columns' => [
                    ['key' => 'date',         'label' => 'Date'],
                    ['key' => 'admission_no', 'label' => 'Admission No'],
                    ['key' => 'first_name',   'label' => 'First Name'],
                    ['key' => 'last_name',    'label' => 'Last Name'],
                    ['key' => 'remarks',      'label' => 'Remarks'],
                ],
                'order' => 3,
            ],

            // ── MARKS ─────────────────────────────────────────────────────────
            [
                'name'        => 'Marks Grade-wise Summary',
                'category'    => 'Marks',
                'icon'        => '📊',
                'description' => 'Count of students per grade for an exam',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.grade')) AS grade,
                        COUNT(*) AS students,
                        ROUND(AVG(JSON_EXTRACT(data,'$.marks_obtained')),1) AS avg_marks,
                        MIN(JSON_EXTRACT(data,'$.marks_obtained')) AS min_marks,
                        MAX(JSON_EXTRACT(data,'$.marks_obtained')) AS max_marks
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='marks' LIMIT 1)
                    AND (:exam_id IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.exam_id')) = :exam_id)
                    GROUP BY grade ORDER BY FIELD(grade,'A+','A','B+','B','C','D','F')
                ",
                'filters' => [
                    ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'reference', 'table' => 'exams'],
                ],
                'columns' => [
                    ['key' => 'grade',     'label' => 'Grade'],
                    ['key' => 'students',  'label' => 'Students'],
                    ['key' => 'avg_marks', 'label' => 'Avg Marks'],
                    ['key' => 'min_marks', 'label' => 'Min Marks'],
                    ['key' => 'max_marks', 'label' => 'Max Marks'],
                ],
                'order' => 1,
            ],

            [
                'name'        => 'Subject-wise Performance',
                'category'    => 'Marks',
                'icon'        => '📚',
                'description' => 'Average marks per subject for an exam',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.subject_id')) AS subject_id,
                        COUNT(*) AS students,
                        ROUND(AVG(JSON_EXTRACT(m.data,'$.marks_obtained')),1) AS avg_marks,
                        ROUND(AVG(JSON_EXTRACT(m.data,'$.percentage')),1) AS avg_pct,
                        SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.grade'))='F' THEN 1 ELSE 0 END) AS failed
                    FROM app_records m
                    WHERE m.app_table_id = (SELECT id FROM app_tables WHERE name='marks' LIMIT 1)
                    AND (:exam_id IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.exam_id')) = :exam_id)
                    GROUP BY subject_id ORDER BY avg_marks DESC
                ",
                'filters' => [
                    ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'reference', 'table' => 'exams'],
                ],
                'columns' => [
                    ['key' => 'subject_id', 'label' => 'Subject ID'],
                    ['key' => 'students',   'label' => 'Students'],
                    ['key' => 'avg_marks',  'label' => 'Avg Marks'],
                    ['key' => 'avg_pct',    'label' => 'Avg %'],
                    ['key' => 'failed',     'label' => 'Failed'],
                ],
                'order' => 2,
            ],

            [
                'name'        => 'Top Performers',
                'category'    => 'Marks',
                'icon'        => '🏆',
                'description' => 'Top 20 students by total marks for an exam',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) AS student_id,
                        SUM(JSON_EXTRACT(data,'$.marks_obtained')) AS total_obtained,
                        SUM(JSON_EXTRACT(data,'$.max_marks'))      AS total_max,
                        ROUND(SUM(JSON_EXTRACT(data,'$.marks_obtained'))*100/SUM(JSON_EXTRACT(data,'$.max_marks')),1) AS percentage
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='marks' LIMIT 1)
                    AND (:exam_id IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.exam_id')) = :exam_id)
                    GROUP BY student_id ORDER BY percentage DESC LIMIT 20
                ",
                'filters' => [
                    ['name' => 'exam_id', 'label' => 'Exam', 'type' => 'reference', 'table' => 'exams'],
                ],
                'columns' => [
                    ['key' => 'student_id',      'label' => 'Student ID'],
                    ['key' => 'total_obtained',   'label' => 'Marks Obtained'],
                    ['key' => 'total_max',        'label' => 'Max Marks'],
                    ['key' => 'percentage',       'label' => 'Percentage %'],
                ],
                'order' => 3,
            ],

            // ── FEES ──────────────────────────────────────────────────────────
            [
                'name'        => 'Fee Collection Summary',
                'category'    => 'Fees',
                'icon'        => '💰',
                'description' => 'Total fees collected by payment mode and status',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_mode')) AS payment_mode,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.status'))       AS status,
                        COUNT(*) AS transactions,
                        SUM(JSON_EXTRACT(data,'$.amount_paid')) AS total_amount
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='fee_payments' LIMIT 1)
                    AND (:date_from IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date')) >= :date_from)
                    AND (:date_to   IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date')) <= :date_to)
                    GROUP BY payment_mode, status ORDER BY total_amount DESC
                ",
                'filters' => [
                    ['name' => 'date_from', 'label' => 'From Date', 'type' => 'date'],
                    ['name' => 'date_to',   'label' => 'To Date',   'type' => 'date'],
                ],
                'columns' => [
                    ['key' => 'payment_mode',  'label' => 'Payment Mode'],
                    ['key' => 'status',        'label' => 'Status'],
                    ['key' => 'transactions',  'label' => 'Transactions'],
                    ['key' => 'total_amount',  'label' => 'Total Amount (₹)'],
                ],
                'order' => 1,
            ],

            [
                'name'        => 'Due / Pending Payments',
                'category'    => 'Fees',
                'icon'        => '⚠️',
                'description' => 'Students with pending or partial fee payments',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id'))    AS student_id,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.receipt_no'))    AS receipt_no,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date'))  AS payment_date,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.status'))        AS status,
                        JSON_EXTRACT(data,'$.amount_paid')                 AS amount_paid,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_mode'))  AS payment_mode
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='fee_payments' LIMIT 1)
                    AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) IN ('pending','partial')
                    ORDER BY payment_date ASC
                ",
                'filters' => [],
                'columns' => [
                    ['key' => 'student_id',   'label' => 'Student ID'],
                    ['key' => 'receipt_no',   'label' => 'Receipt No'],
                    ['key' => 'payment_date', 'label' => 'Date'],
                    ['key' => 'amount_paid',  'label' => 'Amount (₹)'],
                    ['key' => 'status',       'label' => 'Status'],
                ],
                'order' => 2,
            ],

            [
                'name'        => 'Monthly Fee Collection',
                'category'    => 'Fees',
                'icon'        => '📅',
                'description' => 'Total fees collected per month',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date')),'%Y-%m') AS month,
                        COUNT(*) AS transactions,
                        SUM(JSON_EXTRACT(data,'$.amount_paid')) AS total_collected
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='fee_payments' LIMIT 1)
                    AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) = 'paid'
                    GROUP BY month ORDER BY month DESC
                ",
                'filters' => [],
                'columns' => [
                    ['key' => 'month',           'label' => 'Month'],
                    ['key' => 'transactions',     'label' => 'Transactions'],
                    ['key' => 'total_collected',  'label' => 'Total Collected (₹)'],
                ],
                'order' => 3,
            ],

            // ── STUDENTS ──────────────────────────────────────────────────────
            [
                'name'        => 'Student Strength by Class',
                'category'    => 'Students',
                'icon'        => '🎓',
                'description' => 'Number of active students per class',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.class_id'))  AS class_id,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.gender'))    AS gender,
                        COUNT(*) AS students
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='students' LIMIT 1)
                    AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) = 'active'
                    GROUP BY class_id, gender ORDER BY class_id, gender
                ",
                'filters' => [],
                'columns' => [
                    ['key' => 'class_id',  'label' => 'Class ID'],
                    ['key' => 'gender',    'label' => 'Gender'],
                    ['key' => 'students',  'label' => 'Students'],
                ],
                'order' => 1,
            ],

            [
                'name'        => 'New Admissions',
                'category'    => 'Students',
                'icon'        => '📋',
                'description' => 'Students admitted in a date range',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.admission_no'))   AS admission_no,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.first_name'))     AS first_name,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.last_name'))      AS last_name,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.admission_date')) AS admission_date,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.class_id'))       AS class_id,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.gender'))         AS gender
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='students' LIMIT 1)
                    AND (:date_from IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.admission_date')) >= :date_from)
                    AND (:date_to   IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(data,'$.admission_date')) <= :date_to)
                    ORDER BY admission_date DESC
                ",
                'filters' => [
                    ['name' => 'date_from', 'label' => 'From Date', 'type' => 'date'],
                    ['name' => 'date_to',   'label' => 'To Date',   'type' => 'date'],
                ],
                'columns' => [
                    ['key' => 'admission_no',   'label' => 'Admission No'],
                    ['key' => 'first_name',     'label' => 'First Name'],
                    ['key' => 'last_name',      'label' => 'Last Name'],
                    ['key' => 'admission_date', 'label' => 'Admission Date'],
                    ['key' => 'class_id',       'label' => 'Class'],
                    ['key' => 'gender',         'label' => 'Gender'],
                ],
                'order' => 2,
            ],

            // ── LIBRARY ───────────────────────────────────────────────────────
            [
                'name'        => 'Overdue Books',
                'category'    => 'Library',
                'icon'        => '📖',
                'description' => 'Books not returned past due date',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.book_id'))    AS book_id,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) AS student_id,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.issue_date')) AS issue_date,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.due_date'))   AS due_date,
                        JSON_EXTRACT(data,'$.fine_amount')              AS fine_amount,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.status'))     AS status
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='book_issues' LIMIT 1)
                    AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) IN ('overdue','issued')
                    AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.due_date')) < CURDATE()
                    ORDER BY due_date ASC
                ",
                'filters' => [],
                'columns' => [
                    ['key' => 'book_id',     'label' => 'Book ID'],
                    ['key' => 'student_id',  'label' => 'Student ID'],
                    ['key' => 'issue_date',  'label' => 'Issue Date'],
                    ['key' => 'due_date',    'label' => 'Due Date'],
                    ['key' => 'fine_amount', 'label' => 'Fine (₹)'],
                ],
                'order' => 1,
            ],

            // ── TRANSPORT ─────────────────────────────────────────────────────
            [
                'name'        => 'Students by Route',
                'category'    => 'Transport',
                'icon'        => '🚌',
                'description' => 'Number of students per transport route',
                'query_type'  => 'sql',
                'sql_query'   => "
                    SELECT
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.route_id'))    AS route_id,
                        JSON_UNQUOTE(JSON_EXTRACT(data,'$.pickup_stop')) AS pickup_stop,
                        COUNT(*) AS students
                    FROM app_records
                    WHERE app_table_id = (SELECT id FROM app_tables WHERE name='student_transport' LIMIT 1)
                    AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) = 'active'
                    GROUP BY route_id, pickup_stop ORDER BY route_id, students DESC
                ",
                'filters' => [],
                'columns' => [
                    ['key' => 'route_id',    'label' => 'Route ID'],
                    ['key' => 'pickup_stop', 'label' => 'Pickup Stop'],
                    ['key' => 'students',    'label' => 'Students'],
                ],
                'order' => 1,
            ],
        ];

        foreach ($reports as $r) {
            ReportDefinition::firstOrCreate(
                ['name' => $r['name']],
                $r
            );
        }

        $this->command->info('Reports seeded: ' . count($reports) . ' reports.');
    }
}
