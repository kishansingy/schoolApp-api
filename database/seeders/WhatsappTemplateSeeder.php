<?php

namespace Database\Seeders;

use App\Models\WhatsappTemplate;
use Illuminate\Database\Seeder;

class WhatsappTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'               => 'Fee Payment Reminder',
                'category'           => 'payment_reminder',
                'meta_template_name' => 'payment_reminder_3',   // Active Meta template
                'meta_lang_code'     => 'en_US',
                'meta_params_map'    => ['1' => 'student_name', '2' => 'amount', '3' => 'due_date'],
                'body'               => "Dear Parent/Guardian,\n\nThis is a reminder that the fee payment of ₹{{2}} for *{{1}}* is due by *{{3}}*.\n\nPlease make the payment at the earliest to avoid any late fine.\n\nRegards,\nSchool Administration",
            ],
            [
                'name'               => 'Hello World (Sandbox Opener)',
                'category'           => 'general',
                'meta_template_name' => 'hello_world',       // Active Meta template
                'meta_lang_code'     => 'en_US',
                'meta_params_map'    => [],
                'body'               => "Hello! This is a test message from the school WhatsApp system.",
            ],
            [
                'name'     => 'Fee Payment Received',
                'category' => 'fee_receipt',
                'body'     => "Dear {{name}},\n\n✅ We have received your fee payment of *₹{{amount}}* on *{{date}}*.\n\nReceipt No: *{{receipt_no}}*\n\nThank you for the timely payment.\n\nRegards,\nSchool Administration",
            ],
            [
                'name'     => 'Attendance Warning',
                'category' => 'attendance_warning',
                'body'     => "Dear Parent/Guardian,\n\n⚠️ This is to inform you that the attendance of *{{name}}* has dropped to *{{attendance_pct}}%*.\n\nMinimum 75% attendance is required. Please ensure regular attendance.\n\nFor details, contact the class teacher.\n\nRegards,\nSchool Administration",
            ],
            [
                'name'     => 'Exam Result Notification',
                'category' => 'exam_result',
                'body'     => "Dear Parent/Guardian,\n\n📊 The results for *{{exam_name}}* have been published.\n\n*{{name}}* has scored *{{marks}}* out of *{{max_marks}}* ({{percentage}}%).\n\nGrade: *{{grade}}*\n\nRegards,\nSchool Administration",
            ],
            [
                'name'     => 'Absent Today',
                'category' => 'attendance_warning',
                'body'     => "Dear Parent/Guardian,\n\n📋 *{{name}}* was marked *absent* today ({{date}}).\n\nIf this is an error or you have submitted a leave application, please ignore this message.\n\nRegards,\nSchool Administration",
            ],
            [
                'name'     => 'General Announcement',
                'category' => 'general',
                'body'     => "Dear {{name}},\n\n📢 *{{title}}*\n\n{{message}}\n\nRegards,\nSchool Administration",
            ],
            [
                'name'     => 'Salary Slip',
                'category' => 'salary_slip',
                'body'     => "Dear {{name}},\n\n💼 Your salary for *{{month}}* has been processed.\n\nBasic: ₹{{basic}}\nAllowances: ₹{{allowances}}\nDeductions: ₹{{deductions}}\n*Net Salary: ₹{{net}}*\n\nPayment Mode: {{mode}}\n\nRegards,\nSchool Administration",
            ],
            [
                'name'     => 'Event Reminder',
                'category' => 'general',
                'body'     => "Dear {{name}},\n\n🎉 Reminder: *{{event_name}}* is scheduled on *{{date}}* at *{{time}}*.\n\nVenue: {{venue}}\n\nYour presence is requested.\n\nRegards,\nSchool Administration",
            ],
        ];

        foreach ($templates as $t) {
            WhatsappTemplate::updateOrCreate(['name' => $t['name']], $t);
        }

        $this->command->info('WhatsApp templates seeded: ' . count($templates));
    }
}
