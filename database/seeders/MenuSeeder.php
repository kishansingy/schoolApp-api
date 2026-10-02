<?php

namespace Database\Seeders;

use App\Models\AppMenu;
use App\Models\AppMenuItem;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    private function makeMenu(string $label, string $icon, int $order): AppMenu
    {
        return AppMenu::firstOrCreate(
            ['label' => $label],
            ['icon' => $icon, 'order' => $order, 'active' => true]
        );
    }

    private function addItem(AppMenu $menu, string $label, string $icon, string $tableName, int $order): void
    {
        $table = AppTable::where('name', $tableName)->first();
        AppMenuItem::firstOrCreate(
            ['menu_id' => $menu->id, 'label' => $label],
            [
                'icon'         => $icon,
                'app_table_id' => $table?->id,
                'order'        => $order,
                'active'       => true,
            ]
        );
    }

    private function addCustomItem(AppMenu $menu, string $label, string $icon, string $url, int $order): void
    {
        AppMenuItem::firstOrCreate(
            ['menu_id' => $menu->id, 'label' => $label],
            [
                'icon'         => $icon,
                'app_table_id' => null,
                'custom_url'   => $url,
                'order'        => $order,
                'active'       => true,
            ]
        );
    }

    public function run(): void
    {
        // ── Students ──────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Students', '🎓', 1);
        $this->addItem($menu, 'All Students',          '🎓', 'students',            0);
        $this->addItem($menu, 'Parents',               '👨‍👩‍👧', 'parents',             1);
        $this->addItem($menu, 'Student-Parent Link',   '🔗', 'student_parent_link', 2);

        // ── Attendance ────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Attendance', '📋', 2);
        $this->addItem($menu, 'Student Attendance', '🎓', 'student_attendance', 0);
        $this->addItem($menu, 'Staff Attendance',   '👨‍🏫', 'staff_attendance',   1);

        // ── Classes ───────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Classes', '🏫', 3);
        $this->addItem($menu, 'Classes',                  '🏫', 'classes',          0);
        $this->addItem($menu, 'Sections',                 '📂', 'sections',         1);
        $this->addItem($menu, 'Class-Section Assignments','📌', 'class_sections',   2);
        $this->addItem($menu, 'Subjects',                 '📚', 'subjects',         3);
        $this->addItem($menu, 'Timetable',                '🗓️', 'timetable',        4);

        // ── Exams ─────────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Exams', '📝', 4);
        $this->addItem($menu, 'Exams',           '📝', 'exams',       0);
        $this->addItem($menu, 'Marks / Results', '📊', 'marks',       1);
        $this->addItem($menu, 'Exam Marks',      '🏆', 'exam_marks',  2);
        $this->addCustomItem($menu, 'Bulk Marks Entry', '📋', '/marks/bulk-entry', 3);
        $this->addCustomItem($menu, 'Online Tests',     '💻', '/online-tests',     4);

        // ── Fees ──────────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Fees', '💰', 5);
        $this->addItem($menu, 'Fee Structures', '🏷️', 'fee_structures', 0);
        $this->addItem($menu, 'Fee Payments',   '💳', 'fee_payments',   1);

        // ── Hostel ────────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Hostel', '🏠', 6);
        $this->addItem($menu, 'Hostels',            '🏠', 'hostels',            0);
        $this->addItem($menu, 'Hostel Allotments',  '🛏️', 'hostel_allotments',  1);

        // ── Library ───────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Library', '📖', 7);
        $this->addItem($menu, 'Books',       '📖', 'books',       0);
        $this->addItem($menu, 'Book Issues', '📤', 'book_issues', 1);

        // ── Staff ─────────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Staff', '👨‍🏫', 8);
        $this->addItem($menu, 'Staff', '👨‍🏫', 'staff', 0);

        // ── Transport ─────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Transport', '🚌', 9);
        $this->addItem($menu, 'Routes',            '🗺️', 'transport_routes',  0);
        $this->addItem($menu, 'Student Transport', '🚌', 'student_transport', 1);

        // ── Notices ───────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Notices', '📢', 10);
        $this->addItem($menu, 'Notices & Announcements', '📢', 'notices', 0);

        // ── Events ────────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Events', '🎉', 11);
        $this->addItem($menu, 'School Events', '🎉', 'events',      0);
        $this->addItem($menu, 'Event Media',   '🖼️', 'event_media', 1);

        // ── Homework ──────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Homework', '📓', 12);
        $this->addItem($menu, 'Homework', '📓', 'homework', 0);

        // ── YouTube Channel ───────────────────────────────────────────────────
        $menu = $this->makeMenu('YouTube Channel', '▶️', 13);
        $this->addItem($menu, 'YouTube Videos', '▶️', 'youtube_videos', 0);

        // ── Remarks & Complaints ──────────────────────────────────────────────
        $menu = $this->makeMenu('Remarks', '💬', 14);
        $this->addItem($menu, 'Remarks & Complaints', '💬', 'remarks', 0);

        // ── Chat ──────────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Chat', '💬', 15);
        $this->addCustomItem($menu, 'Messages', '💬', '/chat', 0);

        // ── WhatsApp ──────────────────────────────────────────────────────────
        $menu = $this->makeMenu('WhatsApp', '💬', 16);
        $this->addCustomItem($menu, 'Send Message',        '📤', '/whatsapp?tab=send',      0);
        $this->addCustomItem($menu, 'Payment Reminders',   '💰', '/whatsapp?tab=payment',   1);
        $this->addCustomItem($menu, 'Attendance Warnings', '📋', '/whatsapp?tab=attendance',2);
        $this->addCustomItem($menu, 'Bulk Broadcast',      '📣', '/whatsapp?tab=bulk',      3);
        $this->addCustomItem($menu, 'Message Log',         '🗒️', '/whatsapp?tab=logs',      4);
        $this->addCustomItem($menu, 'Templates',           '📝', '/whatsapp?tab=templates', 5);

        // ── Accounts ──────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Accounts', '💰', 17);
        $this->addCustomItem($menu, 'Expenses',        '🧾', '/accounts?tab=expenses', 0);
        $this->addCustomItem($menu, 'Salary Payments', '👨‍💼', '/accounts?tab=salaries', 1);
        $this->addCustomItem($menu, 'Fee Collections', '💳', '/accounts?tab=fees',     2);
        $this->addCustomItem($menu, 'Monthly Ledger',  '📒', '/accounts?tab=ledger',   3);

        // ── Live Sessions ─────────────────────────────────────────────────────
        $menu = $this->makeMenu('Live Sessions', '🎥', 19);
        $this->addCustomItem($menu, 'Live Classes',    '🎥', '/live-sessions',        0);
        $this->addCustomItem($menu, 'Manage Sessions', '📅', '/live-sessions/manage', 1);

        // ── Reports ───────────────────────────────────────────────────────────
        $menu = $this->makeMenu('Reports', '📊', 18);
        $this->addCustomItem($menu, 'All Reports', '📊', '/reports', 0);

        $this->command->info('Menus seeded successfully.');
    }
}
