<?php

namespace App\Services;

use App\Contracts\DashboardServiceInterface;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardService implements DashboardServiceInterface
{
    public function forUser(User $user): JsonResponse
    {
        if ($user->hasRole('admin'))   return $this->admin();
        if ($user->hasRole('teacher')) return $this->teacher($user);
        if ($user->hasRole('student')) return $this->student($user);
        if ($user->hasRole('parent'))  return $this->parent($user);

        return response()->json(['role' => 'viewer', 'stats' => []]);
    }

    // ── Admin ─────────────────────────────────────────────────────────────────

    private function admin(): JsonResponse
    {
        $feeTable = AppTable::where('name', 'fee_payments')->value('id');
        $feeMonth = $feeTable ? AppRecord::where('app_table_id', $feeTable)
            ->whereMonth('created_at', now()->month)
            ->get()->sum(fn($r) => (float)($r->data['amount_paid'] ?? 0)) : 0;

        $attTable = AppTable::where('name', 'student_attendance')->value('id');
        $todayAtt = $attTable ? AppRecord::where('app_table_id', $attTable)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.date')) = ?", [now()->toDateString()])
            ->get() : collect();

        return response()->json([
            'role'   => 'admin',
            'stats'  => [
                ['label' => 'Total Students',    'value' => $this->tableCount('students'),                    'icon' => '🎓', 'color' => 'blue'],
                ['label' => 'Total Staff',        'value' => $this->tableCount('staff'),                       'icon' => '👨‍🏫', 'color' => 'green'],
                ['label' => 'Admissions',         'value' => $this->tableCount('admissions'),                  'icon' => '📋', 'color' => 'purple'],
                ['label' => 'Pending Enquiries',  'value' => $this->tableCountWhere('admissions', 'status', 'enquiry'), 'icon' => '⏳', 'color' => 'orange'],
                ['label' => 'Fee This Month (₹)', 'value' => number_format($feeMonth, 0),                     'icon' => '💰', 'color' => 'emerald'],
                ['label' => 'Present Today',      'value' => $todayAtt->filter(fn($r) => ($r->data['status'] ?? '') === 'present')->count(), 'icon' => '✅', 'color' => 'teal'],
                ['label' => 'Absent Today',       'value' => $todayAtt->filter(fn($r) => ($r->data['status'] ?? '') === 'absent')->count(),  'icon' => '❌', 'color' => 'red'],
            ],
            'notices'     => $this->recentRecords('notices', 5),
            'quick_links' => [
                ['label' => 'Manage Students', 'route' => '/tables/1/records',   'icon' => '🎓'],
                ['label' => 'Admissions',       'route' => '/tables/26/records',  'icon' => '📋'],
                ['label' => 'Fee Payments',     'route' => '/tables/fee/records', 'icon' => '💰'],
                ['label' => 'Reports',          'route' => '/reports',            'icon' => '📊'],
                ['label' => 'Receipts',         'route' => '/receipts',           'icon' => '🧾'],
                ['label' => 'Role Manager',     'route' => '/admin/roles',        'icon' => '🔐'],
            ],
        ]);
    }

    // ── Teacher ───────────────────────────────────────────────────────────────

    private function teacher(User $user): JsonResponse
    {
        $staffTable  = AppTable::where('name', 'staff')->first();
        $staffRecord = $staffTable ? AppRecord::where('app_table_id', $staffTable->id)
            ->where('data->email', $user->email)->first() : null;

        $myClasses = [];
        if ($staffRecord) {
            $sectionTable = AppTable::where('name', 'sections')->first();
            if ($sectionTable) {
                $myClasses = AppRecord::where('app_table_id', $sectionTable->id)
                    ->where('data->class_teacher_id', $staffRecord->id)
                    ->get()->map(fn($r) => $r->data)->toArray();
            }
        }

        $attTable    = AppTable::where('name', 'student_attendance')->value('id');
        $todayMarked = $attTable ? AppRecord::where('app_table_id', $attTable)
            ->where('data->date', now()->toDateString())->count() : 0;

        $examTable    = AppTable::where('name', 'exams')->value('id');
        $ongoingExams = $examTable ? AppRecord::where('app_table_id', $examTable)
            ->where('data->status', 'ongoing')->count() : 0;

        return response()->json([
            'role'       => 'teacher',
            'staff'      => $staffRecord?->data,
            'my_classes' => $myClasses,
            'stats'      => [
                ['label' => 'My Classes',              'value' => count($myClasses), 'icon' => '🏫', 'color' => 'blue'],
                ['label' => 'Attendance Marked Today', 'value' => $todayMarked,      'icon' => '✅', 'color' => 'green'],
                ['label' => 'Ongoing Exams',           'value' => $ongoingExams,     'icon' => '📝', 'color' => 'orange'],
            ],
            'notices'     => $this->recentRecords('notices', 3),
            'quick_links' => [
                ['label' => 'Mark Attendance', 'route' => '/tables/attendance/records/new', 'icon' => '✅'],
                ['label' => 'Enter Marks',     'route' => '/marks/bulk-entry',              'icon' => '📝'],
                ['label' => 'My Students',     'route' => '/tables/1/records',              'icon' => '🎓'],
                ['label' => 'Notices',         'route' => '/tables/notices/records',        'icon' => '📢'],
            ],
        ]);
    }

    // ── Student ───────────────────────────────────────────────────────────────

    private function student(User $user): JsonResponse
    {
        $studentTable = AppTable::where('name', 'students')->first();
        $student = $studentTable ? AppRecord::where('app_table_id', $studentTable->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.email')) = ?", [$user->email])
            ->first() : null;

        $studentId = $student?->id;
        $stats = [];
        $myMarks = [];

        if ($studentId) {
            $attTable = AppTable::where('name', 'student_attendance')->value('id');
            $myAtt    = $attTable ? AppRecord::where('app_table_id', $attTable)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [$studentId])
                ->whereMonth('created_at', now()->month)->get() : collect();
            $present = $myAtt->filter(fn($r) => ($r->data['status'] ?? '') === 'present')->count();
            $total   = $myAtt->count();
            $attPct  = $total ? round($present / $total * 100) : 0;

            $marksTable = AppTable::where('name', 'marks')->value('id');
            $myMarks    = $marksTable ? AppRecord::where('app_table_id', $marksTable)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [$studentId])
                ->latest()->take(5)->get()->map(fn($r) => $r->data)->toArray() : [];

            $feeTable    = AppTable::where('name', 'fee_payments')->value('id');
            $pendingFees = $feeTable ? AppRecord::where('app_table_id', $feeTable)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [$studentId])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) IN ('pending','partial')")
                ->count() : 0;

            $stats = [
                ['label' => 'Attendance This Month', 'value' => "{$attPct}%", 'icon' => '📅', 'color' => $attPct >= 75 ? 'green' : 'red'],
                ['label' => 'Pending Fees',           'value' => $pendingFees, 'icon' => '💰', 'color' => $pendingFees ? 'red' : 'green'],
            ];
        }

        return response()->json([
            'role'         => 'student',
            'student'      => $student?->data,
            'student_id'   => $studentId,
            'stats'        => $stats,
            'recent_marks' => $myMarks,
            'notices'      => $this->recentRecords('notices', 5),
            'quick_links'  => [
                ['label' => 'My Profile',   'route' => '/student-profile/me', 'icon' => '👤'],
                ['label' => 'My Marks',     'route' => '/student/marks',      'icon' => '📊'],
                ['label' => 'Attendance',   'route' => '/student/attendance', 'icon' => '📅'],
                ['label' => 'Online Tests', 'route' => '/online-tests',       'icon' => '📝'],
                ['label' => 'Fee Status',   'route' => '/student/fees',       'icon' => '💰'],
            ],
        ]);
    }

    // ── Parent ────────────────────────────────────────────────────────────────

    private function parent(User $user): JsonResponse
    {
        $parentTable = AppTable::where('name', 'parents')->first();
        $parent = $parentTable ? AppRecord::where('app_table_id', $parentTable->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.email')) = ?", [$user->email])
            ->first() : null;

        $childStats = [];

        if ($parent) {
            $linkTable    = AppTable::where('name', 'student_parent_link')->first();
            $studentTable = AppTable::where('name', 'students')->first();

            if ($linkTable && $studentTable) {
                $links = AppRecord::where('app_table_id', $linkTable->id)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.parent_id')) = ?", [$parent->id])
                    ->get();

                foreach ($links as $link) {
                    $sid = $link->data['student_id'] ?? null;
                    if (!$sid) continue;

                    $s = AppRecord::where('app_table_id', $studentTable->id)->find($sid);
                    if (!$s) continue;

                    $childStats[] = $this->buildChildStats($s);
                }
            }
        }

        return response()->json([
            'role'        => 'parent',
            'parent'      => $parent?->data,
            'children'    => $childStats,
            'notices'     => $this->recentRecords('notices', 5),
            'quick_links' => [
                ['label' => 'My Children',  'route' => '/parent/children',   'icon' => '👨‍👧'],
                ['label' => 'Fee Payments', 'route' => '/parent/fees',       'icon' => '💰'],
                ['label' => 'Attendance',   'route' => '/parent/attendance', 'icon' => '📅'],
                ['label' => 'Notices',      'route' => '/parent/notices',    'icon' => '📢'],
            ],
        ]);
    }

    private function buildChildStats(AppRecord $student): array
    {
        $sid = $student->id;

        $attTable = AppTable::where('name', 'student_attendance')->value('id');
        $att = $attTable ? AppRecord::where('app_table_id', $attTable)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [$sid])
            ->whereMonth('created_at', now()->month)->get() : collect();
        $present = $att->filter(fn($r) => ($r->data['status'] ?? '') === 'present')->count();
        $total   = $att->count();

        $feeTable   = AppTable::where('name', 'fee_payments')->value('id');
        $pendingFee = $feeTable ? AppRecord::where('app_table_id', $feeTable)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [$sid])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) IN ('pending','partial')")
            ->count() : 0;

        return [
            'student'      => array_merge($student->data, ['id' => $sid]),
            'attendance'   => $total ? round($present / $total * 100) : 0,
            'pending_fees' => $pendingFee,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function tableCount(string $name): int
    {
        $id = AppTable::where('name', $name)->value('id');
        return $id ? AppRecord::where('app_table_id', $id)->whereNull('parent_record_id')->count() : 0;
    }

    private function tableCountWhere(string $name, string $field, string $value): int
    {
        $id = AppTable::where('name', $name)->value('id');
        return $id ? AppRecord::where('app_table_id', $id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.{$field}')) = ?", [$value])
            ->count() : 0;
    }

    private function recentRecords(string $name, int $limit): array
    {
        $id = AppTable::where('name', $name)->value('id');
        if (!$id) return [];
        return AppRecord::where('app_table_id', $id)
            ->latest()->take($limit)->get()
            ->map(fn($r) => array_merge($r->data, ['id' => $r->id]))->toArray();
    }
}
