<?php

namespace Database\Seeders;

use App\Models\AppTable;
use App\Models\ReceiptTemplate;
use Illuminate\Database\Seeder;

class ReceiptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $feeTable     = AppTable::where('name', 'fee_payments')->value('id');
        $studentTable = AppTable::where('name', 'students')->value('id');

        // ── Fee Payment Receipt ───────────────────────────────────────────────
        ReceiptTemplate::firstOrCreate(['name' => 'Fee Payment Receipt'], [
            'description'  => 'Official fee payment receipt for students',
            'app_table_id' => $feeTable,
            'paper_size'   => 'A5',
            'orientation'  => 'portrait',
            'active'       => true,
            'html_template' => '
<div style="font-family:Arial,sans-serif;padding:16px;border:2px solid #c0392b;border-radius:8px">
  <div style="text-align:center;margin-bottom:12px">
    <h2 style="color:#c0392b;margin:0;font-size:18px">🏫 School Management System</h2>
    <p style="margin:2px 0;font-size:11px;color:#64748b">Fee Payment Receipt</p>
    <hr style="border-color:#c0392b;margin:8px 0">
  </div>
  <table style="width:100%;font-size:12px;border-collapse:collapse">
    <tr>
      <td style="padding:4px 6px;color:#64748b;width:35%">Receipt No</td>
      <td style="padding:4px 6px;font-weight:700">{{receipt_no}}</td>
      <td style="padding:4px 6px;color:#64748b;width:25%">Date</td>
      <td style="padding:4px 6px;font-weight:700">{{payment_date}}</td>
    </tr>
    <tr style="background:#fef9f9">
      <td style="padding:4px 6px;color:#64748b">Student</td>
      <td style="padding:4px 6px;font-weight:700">{{student_id_name}}</td>
      <td style="padding:4px 6px;color:#64748b">Adm. No</td>
      <td style="padding:4px 6px">{{student_id.admission_no}}</td>
    </tr>
    <tr>
      <td style="padding:4px 6px;color:#64748b">Fee Type</td>
      <td style="padding:4px 6px">{{fee_structure_id_name}}</td>
      <td style="padding:4px 6px;color:#64748b">Mode</td>
      <td style="padding:4px 6px">{{payment_mode}}</td>
    </tr>
    <tr style="background:#fef9f9">
      <td style="padding:4px 6px;color:#64748b">Discount</td>
      <td style="padding:4px 6px">₹ {{discount}}</td>
      <td style="padding:4px 6px;color:#64748b">Fine</td>
      <td style="padding:4px 6px">₹ {{fine}}</td>
    </tr>
    <tr style="background:#dcfce7">
      <td style="padding:6px;color:#15803d;font-weight:700;font-size:13px" colspan="2">Amount Paid</td>
      <td style="padding:6px;font-weight:700;font-size:16px;color:#15803d" colspan="2">₹ {{amount_paid}}</td>
    </tr>
  </table>
  <div style="margin-top:10px;font-size:11px;color:#64748b">Remarks: {{remarks}}</div>
  <div style="margin-top:20px;display:flex;justify-content:space-between;font-size:11px;color:#94a3b8">
    <span>Printed: {{_datetime}}</span>
    <div style="text-align:center">
      <div style="border-top:1px solid #334155;padding-top:4px;width:100px">Signature</div>
    </div>
  </div>
</div>',
        ]);

        // ── Admission Acknowledgement ─────────────────────────────────────────
        ReceiptTemplate::firstOrCreate(['name' => 'Admission Acknowledgement'], [
            'description'  => 'Acknowledgement letter for new student admission',
            'app_table_id' => $studentTable,
            'paper_size'   => 'A4',
            'orientation'  => 'portrait',
            'active'       => true,
            'html_template' => '
<div style="font-family:Arial,sans-serif;padding:24px;max-width:600px;margin:0 auto">
  <div style="text-align:center;border-bottom:3px double #c0392b;padding-bottom:16px;margin-bottom:20px">
    <h1 style="color:#c0392b;margin:0;font-size:22px">🏫 School Management System</h1>
    <p style="margin:4px 0;color:#64748b">Admission Acknowledgement</p>
  </div>

  <p style="font-size:13px;color:#334155;margin-bottom:16px">
    This is to acknowledge that the following student has been successfully admitted to our institution.
  </p>

  <table style="width:100%;font-size:13px;border-collapse:collapse;border:1px solid #e2e8f0">
    <tr style="background:#c0392b;color:#fff">
      <th colspan="2" style="padding:8px 12px;text-align:left">Student Information</th>
    </tr>
    <tr>
      <td style="padding:7px 12px;color:#64748b;border-bottom:1px solid #f1f5f9;width:40%">Admission No</td>
      <td style="padding:7px 12px;font-weight:700;border-bottom:1px solid #f1f5f9">{{admission_no}}</td>
    </tr>
    <tr style="background:#f8fafc">
      <td style="padding:7px 12px;color:#64748b;border-bottom:1px solid #f1f5f9">Full Name</td>
      <td style="padding:7px 12px;font-weight:700;border-bottom:1px solid #f1f5f9">{{first_name}} {{last_name}}</td>
    </tr>
    <tr>
      <td style="padding:7px 12px;color:#64748b;border-bottom:1px solid #f1f5f9">Date of Birth</td>
      <td style="padding:7px 12px;border-bottom:1px solid #f1f5f9">{{date_of_birth}}</td>
    </tr>
    <tr style="background:#f8fafc">
      <td style="padding:7px 12px;color:#64748b;border-bottom:1px solid #f1f5f9">Gender</td>
      <td style="padding:7px 12px;border-bottom:1px solid #f1f5f9">{{gender}}</td>
    </tr>
    <tr>
      <td style="padding:7px 12px;color:#64748b;border-bottom:1px solid #f1f5f9">Admission Date</td>
      <td style="padding:7px 12px;font-weight:600;border-bottom:1px solid #f1f5f9">{{admission_date}}</td>
    </tr>
    <tr style="background:#f8fafc">
      <td style="padding:7px 12px;color:#64748b;border-bottom:1px solid #f1f5f9">Phone</td>
      <td style="padding:7px 12px;border-bottom:1px solid #f1f5f9">{{phone}}</td>
    </tr>
    <tr>
      <td style="padding:7px 12px;color:#64748b">Address</td>
      <td style="padding:7px 12px">{{address}}</td>
    </tr>
  </table>

  <p style="font-size:12px;color:#64748b;margin-top:16px;font-style:italic">
    Please keep this acknowledgement for your records. For any queries, contact the school office.
  </p>

  <div style="margin-top:32px;display:flex;justify-content:space-between;font-size:12px">
    <div>
      <div style="color:#64748b">Date: {{_date}}</div>
    </div>
    <div style="text-align:center">
      <div style="border-top:1px solid #334155;padding-top:4px;width:140px">Principal Signature</div>
    </div>
  </div>
</div>',
        ]);

        // ── Student Progress Report ───────────────────────────────────────────
        ReceiptTemplate::updateOrCreate(['name' => 'Student Progress Report'], [
            'description'  => 'Exam progress report with subject-wise marks for parent signature',
            'app_table_id' => null,
            'query_mode'   => 'sql',
            'paper_size'   => 'A4',
            'orientation'  => 'portrait',
            'active'       => true,
            'params'       => [
                ['name' => 'student_id', 'label' => 'Student',  'type' => 'reference', 'table' => 'students'],
                ['name' => 'exam_id',    'label' => 'Exam',     'type' => 'reference', 'table' => 'exams'],
            ],
            'sql_query' => "
                SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.first_name'))     AS first_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.last_name'))      AS last_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.admission_no'))   AS admission_no,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.date_of_birth'))  AS date_of_birth,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.gender'))         AS gender,
                    JSON_UNQUOTE(JSON_EXTRACT(e.data,'$.name'))           AS exam_name,
                    JSON_UNQUOTE(JSON_EXTRACT(e.data,'$.academic_year'))  AS academic_year,
                    JSON_UNQUOTE(JSON_EXTRACT(e.data,'$.start_date'))     AS exam_start,
                    JSON_UNQUOTE(JSON_EXTRACT(e.data,'$.end_date'))       AS exam_end,
                    JSON_UNQUOTE(JSON_EXTRACT(sub.data,'$.name'))         AS subject_name,
                    JSON_UNQUOTE(JSON_EXTRACT(sub.data,'$.code'))         AS subject_code,
                    JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.written_date'))   AS written_date,
                    JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.max_marks'))      AS max_marks,
                    JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.marks_obtained')) AS marks_obtained,
                    JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.percentage'))     AS percentage,
                    JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.grade'))          AS grade,
                    JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.remarks'))        AS remarks
                FROM app_records m
                JOIN app_records s   ON s.id   = JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.student_id'))
                JOIN app_records e   ON e.id   = JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.exam_id'))
                JOIN app_records sub ON sub.id = JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.subject_id'))
                WHERE m.app_table_id = (SELECT id FROM app_tables WHERE name='marks' LIMIT 1)
                AND JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.student_id')) = :student_id
                AND JSON_UNQUOTE(JSON_EXTRACT(m.data,'$.exam_id'))    = :exam_id
                ORDER BY sub.id
            ",
            'html_template' => '
<div style="font-family:Arial,sans-serif;max-width:680px;margin:0 auto;padding:24px;color:#1e293b">

  <!-- School Header -->
  <div style="text-align:center;border-bottom:3px double #c0392b;padding-bottom:14px;margin-bottom:18px">
    <h1 style="color:#c0392b;margin:0;font-size:20px;letter-spacing:1px">🏫 SCHOOL MANAGEMENT SYSTEM</h1>
    <p style="margin:3px 0;font-size:12px;color:#64748b">Academic Year: {{academic_year}}</p>
    <h2 style="margin:6px 0 0;font-size:15px;color:#334155;text-transform:uppercase;letter-spacing:2px">Student Progress Report</h2>
  </div>

  <!-- Student Info -->
  <table style="width:100%;font-size:12px;border-collapse:collapse;margin-bottom:16px;border:1px solid #e2e8f0">
    <tr style="background:#c0392b;color:#fff">
      <th colspan="4" style="padding:7px 12px;text-align:left;font-size:13px">Student Information</th>
    </tr>
    <tr>
      <td style="padding:6px 10px;color:#64748b;width:22%">Name</td>
      <td style="padding:6px 10px;font-weight:700;width:28%">{{first_name}} {{last_name}}</td>
      <td style="padding:6px 10px;color:#64748b;width:22%">Admission No</td>
      <td style="padding:6px 10px;font-weight:700">{{admission_no}}</td>
    </tr>
    <tr style="background:#f8fafc">
      <td style="padding:6px 10px;color:#64748b">Date of Birth</td>
      <td style="padding:6px 10px">{{date_of_birth}}</td>
      <td style="padding:6px 10px;color:#64748b">Gender</td>
      <td style="padding:6px 10px;text-transform:capitalize">{{gender}}</td>
    </tr>
    <tr>
      <td style="padding:6px 10px;color:#64748b">Exam</td>
      <td style="padding:6px 10px;font-weight:600">{{exam_name}}</td>
      <td style="padding:6px 10px;color:#64748b">Period</td>
      <td style="padding:6px 10px">{{exam_start}} to {{exam_end}}</td>
    </tr>
  </table>

  <!-- Marks Table -->
  <table style="width:100%;font-size:12px;border-collapse:collapse;margin-bottom:16px">
    <thead>
      <tr style="background:#1e293b;color:#fff">
        <th style="padding:8px 10px;text-align:left">#</th>
        <th style="padding:8px 10px;text-align:left">Subject</th>
        <th style="padding:8px 10px;text-align:left">Code</th>
        <th style="padding:8px 10px;text-align:center">Date</th>
        <th style="padding:8px 10px;text-align:center">Max</th>
        <th style="padding:8px 10px;text-align:center">Obtained</th>
        <th style="padding:8px 10px;text-align:center">%</th>
        <th style="padding:8px 10px;text-align:center">Grade</th>
        <th style="padding:8px 10px;text-align:left">Remarks</th>
      </tr>
    </thead>
    <tbody>
      {{#each _rows}}
      <tr style="border-bottom:1px solid #e2e8f0">
        <td style="padding:6px 10px;color:#94a3b8">{{_index}}</td>
        <td style="padding:6px 10px;font-weight:500">{{subject_name}}</td>
        <td style="padding:6px 10px;color:#64748b">{{subject_code}}</td>
        <td style="padding:6px 10px;text-align:center">{{written_date}}</td>
        <td style="padding:6px 10px;text-align:center">{{max_marks}}</td>
        <td style="padding:6px 10px;text-align:center;font-weight:600">{{marks_obtained}}</td>
        <td style="padding:6px 10px;text-align:center">{{percentage}}%</td>
        <td style="padding:6px 10px;text-align:center;font-weight:700">{{grade}}</td>
        <td style="padding:6px 10px;font-size:11px;color:#64748b">{{remarks}}</td>
      </tr>
      {{/each}}
    </tbody>
  </table>

  <!-- Grade Legend -->
  <div style="font-size:10px;color:#64748b;margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap">
    <span><b>A+</b> ≥90%</span>
    <span><b>A</b> ≥80%</span>
    <span><b>B+</b> ≥70%</span>
    <span><b>B</b> ≥60%</span>
    <span><b>C</b> ≥50%</span>
    <span><b>D</b> ≥40%</span>
    <span><b>F</b> &lt;40%</span>
  </div>

  <!-- Teacher Remarks -->
  <div style="border:1px solid #e2e8f0;border-radius:6px;padding:10px 14px;margin-bottom:20px;min-height:50px">
    <div style="font-size:11px;font-weight:700;color:#475569;margin-bottom:4px">Class Teacher Remarks:</div>
    <div style="font-size:12px;color:#94a3b8;font-style:italic">_______________________________________________</div>
  </div>

  <!-- Signature Section -->
  <div style="display:flex;justify-content:space-between;margin-top:30px;font-size:12px">
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Class Teacher</div>
    </div>
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Principal</div>
    </div>
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Parent / Guardian Signature</div>
      <div style="font-size:10px;color:#94a3b8;margin-top:4px">Date: ___________</div>
    </div>
  </div>

  <!-- Footer -->
  <div style="text-align:center;margin-top:20px;font-size:10px;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:8px">
    Generated on {{_datetime}} &nbsp;|&nbsp; This is a computer-generated report.
    <br>Please return the signed copy to the school office within 7 days.
  </div>

</div>',
        ]);

        // ── Transfer Certificate ──────────────────────────────────────────────
        ReceiptTemplate::updateOrCreate(['name' => 'Transfer Certificate'], [
            'description'  => 'Official Transfer Certificate issued to students leaving the school',
            'app_table_id' => $studentTable,
            'query_mode'   => 'sql',
            'paper_size'   => 'A4',
            'orientation'  => 'portrait',
            'active'       => true,
            'params'       => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'reference', 'table' => 'students'],
            ],
            'sql_query' => "
                SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.first_name'))     AS first_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.last_name'))      AS last_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.admission_no'))   AS admission_no,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.date_of_birth'))  AS date_of_birth,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.gender'))         AS gender,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.admission_date')) AS admission_date,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.address'))        AS address,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.phone'))          AS phone,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.father_name'))    AS father_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.mother_name'))    AS mother_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.religion'))       AS religion,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.caste'))          AS caste,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.nationality'))    AS nationality,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.blood_group'))    AS blood_group,
                    JSON_UNQUOTE(JSON_EXTRACT(c.data,'$.name'))           AS class_name
                FROM app_records s
                LEFT JOIN app_records c ON c.id = JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.class_id'))
                WHERE s.app_table_id = (SELECT id FROM app_tables WHERE name='students' LIMIT 1)
                AND s.id = :student_id
                LIMIT 1
            ",
            'html_template' => '
<div style="font-family:\'Times New Roman\',serif;max-width:700px;margin:0 auto;padding:32px;color:#1e293b;border:3px double #1e3a5f">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:20px">
    <h1 style="color:#1e3a5f;margin:0;font-size:22px;letter-spacing:2px">🏫 SCHOOL MANAGEMENT SYSTEM</h1>
    <p style="margin:4px 0;font-size:12px;color:#64748b">Affiliated to State Board of Education</p>
    <div style="margin:10px auto;width:80px;border-top:2px solid #1e3a5f"></div>
    <h2 style="margin:6px 0;font-size:18px;text-decoration:underline;letter-spacing:3px;color:#1e3a5f">TRANSFER CERTIFICATE</h2>
    <p style="font-size:11px;color:#64748b;margin:4px 0">TC No: __________ &nbsp;&nbsp; Date: {{_date}}</p>
  </div>

  <!-- Body -->
  <p style="font-size:13px;line-height:2;margin:0">
    This is to certify that <strong>{{first_name}} {{last_name}}</strong>,
    son/daughter of <strong>{{father_name}}</strong> and <strong>{{mother_name}}</strong>,
    was a bonafide student of this institution.
  </p>

  <table style="width:100%;font-size:13px;border-collapse:collapse;margin:16px 0;line-height:2">
    <tr>
      <td style="width:45%;color:#475569;padding:3px 0">Admission Number</td>
      <td style="width:5%">:</td>
      <td style="font-weight:600">{{admission_no}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Date of Birth</td>
      <td>:</td>
      <td>{{date_of_birth}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Gender</td>
      <td>:</td>
      <td style="text-transform:capitalize">{{gender}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Nationality</td>
      <td>:</td>
      <td>{{nationality}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Religion</td>
      <td>:</td>
      <td>{{religion}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Caste</td>
      <td>:</td>
      <td>{{caste}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Blood Group</td>
      <td>:</td>
      <td>{{blood_group}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Class Last Studied</td>
      <td>:</td>
      <td style="font-weight:600">{{class_name}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Date of Admission</td>
      <td>:</td>
      <td>{{admission_date}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Date of Leaving</td>
      <td>:</td>
      <td>{{_date}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Reason for Leaving</td>
      <td>:</td>
      <td>___________________________</td>
    </tr>
  </table>

  <p style="font-size:13px;line-height:1.8;margin:12px 0">
    The student\'s conduct and character during the period of study was
    <strong>_______________</strong>. He/She has cleared all dues to the school.
  </p>

  <!-- Remarks -->
  <div style="border:1px solid #cbd5e1;border-radius:4px;padding:10px 14px;margin:14px 0;min-height:44px">
    <span style="font-size:11px;color:#64748b;font-weight:700">Remarks: </span>
    <span style="font-size:12px;color:#94a3b8;font-style:italic">_______________________________________________</span>
  </div>

  <!-- Signatures -->
  <div style="display:flex;justify-content:space-between;margin-top:40px;font-size:12px;font-family:Arial,sans-serif">
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Class Teacher</div>
    </div>
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Office Seal &amp; Signature</div>
    </div>
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Principal</div>
    </div>
  </div>

  <!-- Footer -->
  <div style="text-align:center;margin-top:24px;font-size:10px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:8px;font-family:Arial,sans-serif">
    Generated on {{_datetime}} &nbsp;|&nbsp; This is a computer-generated certificate.
  </div>

</div>',
        ]);

        // ── Bonafide Certificate ──────────────────────────────────────────────
        ReceiptTemplate::updateOrCreate(['name' => 'Bonafide Certificate'], [
            'description'  => 'Bonafide certificate confirming student enrollment',
            'app_table_id' => $studentTable,
            'query_mode'   => 'sql',
            'paper_size'   => 'A4',
            'orientation'  => 'portrait',
            'active'       => true,
            'params'       => [
                ['name' => 'student_id', 'label' => 'Student', 'type' => 'reference', 'table' => 'students'],
            ],
            'sql_query' => "
                SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.first_name'))     AS first_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.last_name'))      AS last_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.admission_no'))   AS admission_no,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.date_of_birth'))  AS date_of_birth,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.gender'))         AS gender,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.admission_date')) AS admission_date,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.father_name'))    AS father_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.mother_name'))    AS mother_name,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.nationality'))    AS nationality,
                    JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.religion'))       AS religion,
                    JSON_UNQUOTE(JSON_EXTRACT(c.data,'$.name'))           AS class_name,
                    JSON_UNQUOTE(JSON_EXTRACT(sec.data,'$.name'))         AS section_name
                FROM app_records s
                LEFT JOIN app_records c   ON c.id   = JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.class_id'))
                LEFT JOIN app_records sec ON sec.id = JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.section_id'))
                WHERE s.app_table_id = (SELECT id FROM app_tables WHERE name='students' LIMIT 1)
                AND s.id = :student_id
                LIMIT 1
            ",
            'html_template' => '
<div style="font-family:\'Times New Roman\',serif;max-width:700px;margin:0 auto;padding:32px;color:#1e293b;border:3px double #155724">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:24px">
    <h1 style="color:#155724;margin:0;font-size:22px;letter-spacing:2px">🏫 SCHOOL MANAGEMENT SYSTEM</h1>
    <p style="margin:4px 0;font-size:12px;color:#64748b">Affiliated to State Board of Education</p>
    <div style="margin:10px auto;width:80px;border-top:2px solid #155724"></div>
    <h2 style="margin:6px 0;font-size:18px;text-decoration:underline;letter-spacing:3px;color:#155724">BONAFIDE CERTIFICATE</h2>
    <p style="font-size:11px;color:#64748b;margin:4px 0">Ref No: __________ &nbsp;&nbsp; Date: {{_date}}</p>
  </div>

  <!-- Body -->
  <p style="font-size:14px;line-height:2;text-align:justify;margin:0 0 16px">
    This is to certify that <strong>{{first_name}} {{last_name}}</strong>,
    son/daughter of <strong>{{father_name}}</strong> and <strong>{{mother_name}}</strong>,
    bearing Admission No. <strong>{{admission_no}}</strong>, is a bonafide student of this institution,
    currently studying in Class <strong>{{class_name}}{{#if section_name}} - {{section_name}}{{/if}}</strong>
    for the current academic year.
  </p>

  <table style="width:100%;font-size:13px;border-collapse:collapse;margin:16px 0;line-height:2.2">
    <tr>
      <td style="width:45%;color:#475569;padding:3px 0">Full Name</td>
      <td style="width:5%">:</td>
      <td style="font-weight:600">{{first_name}} {{last_name}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Admission Number</td>
      <td>:</td>
      <td style="font-weight:600">{{admission_no}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Date of Birth</td>
      <td>:</td>
      <td>{{date_of_birth}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Gender</td>
      <td>:</td>
      <td style="text-transform:capitalize">{{gender}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Nationality</td>
      <td>:</td>
      <td>{{nationality}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Religion</td>
      <td>:</td>
      <td>{{religion}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Class &amp; Section</td>
      <td>:</td>
      <td style="font-weight:600">{{class_name}} {{section_name}}</td>
    </tr>
    <tr>
      <td style="color:#475569;padding:3px 0">Date of Admission</td>
      <td>:</td>
      <td>{{admission_date}}</td>
    </tr>
  </table>

  <p style="font-size:13px;line-height:1.8;margin:16px 0">
    This certificate is issued on request of the student/parent for the purpose of
    <strong>_______________________________________________</strong>.
  </p>

  <!-- Signatures -->
  <div style="display:flex;justify-content:space-between;margin-top:48px;font-size:12px;font-family:Arial,sans-serif">
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Class Teacher</div>
    </div>
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Office Seal &amp; Signature</div>
    </div>
    <div style="text-align:center;width:30%">
      <div style="border-top:1px solid #334155;padding-top:6px;margin-top:30px">Principal</div>
    </div>
  </div>

  <!-- Footer -->
  <div style="text-align:center;margin-top:24px;font-size:10px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:8px;font-family:Arial,sans-serif">
    Generated on {{_datetime}} &nbsp;|&nbsp; This is a computer-generated certificate.
  </div>

</div>',
        ]);

        $this->command->info('Receipt templates seeded.');
    }
}
