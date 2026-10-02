<?php

namespace App\Http\Controllers\Api;

use App\Contracts\WhatsappServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\WhatsappLog;
use App\Models\WhatsappTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappController extends Controller
{
    public function __construct(
        private readonly WhatsappServiceInterface $whatsapp
    ) {}

    // ── Config ────────────────────────────────────────────────────────────────

    public function config(): JsonResponse
    {
        return response()->json([
            'configured'    => $this->whatsapp->isConfigured(),
            'phone_id'      => config('services.whatsapp.phone_id') ? '****' . substr(config('services.whatsapp.phone_id'), -4) : null,
            'school_number' => config('services.whatsapp.school_number'),
            'school_name'   => config('services.whatsapp.school_name'),
        ]);
    }

    // ── Templates ─────────────────────────────────────────────────────────────

    public function templates(): JsonResponse
    {
        return response()->json(WhatsappTemplate::where('active', true)->orderBy('category')->orderBy('name')->get());
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'required|string',
            'category'           => 'required|string',
            'body'               => 'required|string',
            'meta_template_name' => 'nullable|string',
            'meta_lang_code'     => 'nullable|string',
            'meta_params_map'    => 'nullable|array',
        ]);
        return response()->json(WhatsappTemplate::create($data), 201);
    }

    public function updateTemplate(Request $request, WhatsappTemplate $template): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'required|string',
            'category'           => 'required|string',
            'body'               => 'required|string',
            'active'             => 'boolean',
            'meta_template_name' => 'nullable|string',
            'meta_lang_code'     => 'nullable|string',
            'meta_params_map'    => 'nullable|array',
        ]);
        $template->update($data);
        return response()->json($template);
    }

    public function destroyTemplate(WhatsappTemplate $template): \Illuminate\Http\Response
    {
        $template->delete();
        return response()->noContent();
    }

    // ── Recipients ────────────────────────────────────────────────────────────

    public function broadcastTables(): JsonResponse
    {
        $tables = AppTable::where('whatsapp_broadcast', true)->get(['id', 'name', 'label', 'whatsapp_phone_field']);

        return response()->json($tables->map(fn($t) => [
            'value'       => $t->name,
            'label'       => $t->label,
            'phone_field' => $t->whatsapp_phone_field,
        ]));
    }

    public function recipients(Request $request): JsonResponse
    {
        $tableName = $request->type ?? 'students';
        $table     = AppTable::where('name', $tableName)->first();
        if (!$table) return response()->json([]);

        // Use the explicitly configured phone field, fallback to common names
        $phoneField = $table->whatsapp_phone_field;

        $list = AppRecord::where('app_table_id', $table->id)->get()->map(function ($r) use ($tableName, $phoneField) {
            $d = $r->data ?? [];

            $name = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))
                ?: ($d['name'] ?? $d['customer_name'] ?? $d['contact_name'] ?? "#{$r->id}");

            // Use configured field first, then fallback
            $rawPhone = $phoneField
                ? ($d[$phoneField] ?? '')
                : ($d['whatsapp'] ?? $d['phone'] ?? $d['mobile'] ?? $d['contact_number'] ?? '');

            $phone = $this->whatsapp->normalizePhone($rawPhone);

            return ['id' => $r->id, 'name' => $name, 'phone' => $phone, 'raw' => $d, 'type' => $tableName];
        })->filter(fn($r) => !empty($r['phone']))->values();

        return response()->json($list);
    }

    // ── Send single ───────────────────────────────────────────────────────────

    public function send(Request $request): JsonResponse
    {
        $data  = $request->validate([
            'to'              => 'required|string',
            'message'         => 'required|string',
            'recipient_name'  => 'nullable|string',
            'recipient_type'  => 'nullable|string',
            'recipient_id'    => 'nullable|integer',
            'template_name'   => 'nullable|string',
            'meta_template'   => 'nullable|string',
            'meta_lang'       => 'nullable|string',
            'template_params' => 'nullable|array',
        ]);
        $phone = $this->whatsapp->normalizePhone($data['to']);

        $result = !empty($data['meta_template'])
            ? $this->whatsapp->sendTemplate($phone, $data['meta_template'], $data['meta_lang'] ?? 'en_US', $data['template_params'] ?? [])
            : $this->whatsapp->sendText($phone, $data['message']);

        $log = $this->logMessage($phone, $data['message'], $result, [
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_type' => $data['recipient_type'] ?? null,
            'recipient_id'   => $data['recipient_id']   ?? null,
            'template_name'  => $data['template_name']  ?? null,
        ]);

        return response()->json(['success' => $result['success'], 'log_id' => $log->id, 'error' => $result['error'] ?? null, 'raw' => $result['raw'] ?? null], $result['success'] ? 200 : 422);
    }

    public function sendHelloWorld(Request $request): JsonResponse
    {
        $phone  = $this->whatsapp->normalizePhone($request->validate(['to' => 'required|string'])['to']);
        $result = $this->whatsapp->sendTemplate($phone, 'hello_world', 'en_US');
        $this->logMessage($phone, '[Template: hello_world]', $result, ['template_name' => 'hello_world']);
        return response()->json(['success' => $result['success'], 'error' => $result['error'] ?? null, 'raw' => $result['raw'] ?? null]);
    }

    // ── Bulk send ─────────────────────────────────────────────────────────────

    public function sendBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipients'             => 'required|array|min:1',
            'recipients.*.phone'     => 'required|string',
            'recipients.*.name'      => 'nullable|string',
            'recipients.*.id'        => 'nullable|integer',
            'recipients.*.type'      => 'nullable|string',
            'message_template'       => 'required|string',
            'template_name'          => 'nullable|string',
        ]);

        $sent = 0; $failed = 0;
        foreach ($data['recipients'] as $recipient) {
            $phone   = $this->whatsapp->normalizePhone($recipient['phone']);
            $message = str_replace(
                ['{{name}}', '{{student_name}}', '{{parent_name}}', '{{staff_name}}'],
                $recipient['name'] ?? 'Student',
                $data['message_template']
            );
            $result = $this->whatsapp->sendText($phone, $message);
            $this->logMessage($phone, $message, $result, [
                'recipient_name' => $recipient['name'] ?? null,
                'recipient_type' => $recipient['type'] ?? null,
                'recipient_id'   => $recipient['id']   ?? null,
                'template_name'  => $data['template_name'] ?? null,
            ]);
            $result['success'] ? $sent++ : $failed++;
        }

        return response()->json(['sent' => $sent, 'failed' => $failed]);
    }

    // ── Payment reminder ──────────────────────────────────────────────────────

    public function sendPaymentReminder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_ids' => 'required|array',
            'due_amount'  => 'nullable|string',
            'due_date'    => 'nullable|string',
        ]);

        $parentsTable = AppTable::where('name', 'parents')->first();
        $linkTable    = AppTable::where('name', 'student_parent_link')->first();
        $sent = 0; $failed = 0;

        foreach ($data['student_ids'] as $studentId) {
            $student = AppRecord::find($studentId);
            if (!$student) continue;

            $sd          = $student->data ?? [];
            $studentName = trim(($sd['first_name'] ?? '') . ' ' . ($sd['last_name'] ?? ''));
            $dueAmount   = $data['due_amount'] ?? 'pending amount';
            $dueDate     = $data['due_date']   ?? 'soon';

            [$phone, $recipientName, $recipientType] = $this->resolveParentPhone($studentId, $sd, $linkTable, $parentsTable);
            if (!$phone) continue;

            $result = $this->whatsapp->sendTemplate($phone, 'payment_reminder_3', 'en_US', [$studentName, $dueAmount, $dueDate]);
            $this->logMessage($phone, "Fee reminder: ₹{$dueAmount} for {$studentName} due by {$dueDate}.", $result, [
                'recipient_name' => $recipientName,
                'recipient_type' => $recipientType,
                'recipient_id'   => $studentId,
                'template_name'  => 'payment_reminder_3',
            ]);
            $result['success'] ? $sent++ : $failed++;
        }

        return response()->json(['sent' => $sent, 'failed' => $failed]);
    }

    // ── Attendance warning ────────────────────────────────────────────────────

    public function sendAttendanceWarning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_ids'    => 'required|array',
            'attendance_pct' => 'nullable|string',
            'custom_note'    => 'nullable|string',
        ]);

        $linkTable    = AppTable::where('name', 'student_parent_link')->first();
        $parentsTable = AppTable::where('name', 'parents')->first();
        $sent = 0; $failed = 0;

        foreach ($data['student_ids'] as $studentId) {
            $student = AppRecord::find($studentId);
            if (!$student) continue;

            $sd          = $student->data ?? [];
            $studentName = trim(($sd['first_name'] ?? '') . ' ' . ($sd['last_name'] ?? ''));
            $pct         = $data['attendance_pct'] ?? 'below 75%';
            $note        = $data['custom_note'] ? "\n\n{$data['custom_note']}" : '';
            $message     = "Dear Parent/Guardian,\n\nWe wish to inform you that the attendance of *{$studentName}* has fallen to *{$pct}*.\n\nRegular attendance is mandatory. Please ensure your child attends school regularly.{$note}\n\nRegards,\nSchool Administration";

            [$phone, $recipientName, $recipientType] = $this->resolveParentPhone($studentId, $sd, $linkTable, $parentsTable);
            if (!$phone) continue;

            $result = $this->whatsapp->sendText($phone, $message);
            $this->logMessage($phone, $message, $result, [
                'recipient_name' => $recipientName,
                'recipient_type' => $recipientType,
                'recipient_id'   => $studentId,
                'template_name'  => 'attendance_warning',
            ]);
            $result['success'] ? $sent++ : $failed++;
        }

        return response()->json(['sent' => $sent, 'failed' => $failed]);
    }

    // ── Mobile endpoints ──────────────────────────────────────────────────────

    public function contactSchool(Request $request): JsonResponse
    {
        $data         = $request->validate(['message' => 'required|string|max:1000']);
        $schoolNumber = config('services.whatsapp.school_number');

        if (!$schoolNumber) {
            return response()->json(['message' => 'School WhatsApp number not configured.'], 422);
        }

        $user        = $request->user();
        $fullMessage = "{$data['message']}\n\n— From: {$user->name} ({$user->email})";
        $result      = $this->whatsapp->sendText($schoolNumber, $fullMessage);

        $this->logMessage($schoolNumber, $fullMessage, $result, [
            'recipient_name'  => config('services.whatsapp.school_name', 'School'),
            'recipient_type'  => 'school',
            'template_name'   => 'contact_school',
            'sent_by_user_id' => $user->id,
        ]);

        return response()->json(['success' => $result['success'], 'error' => $result['error'] ?? null], $result['success'] ? 200 : 422);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $data  = $request->validate(['to' => 'required|string', 'message' => 'required|string|max:1000', 'recipient_name' => 'nullable|string', 'recipient_type' => 'nullable|string', 'recipient_id' => 'nullable|integer']);
        $phone = $this->whatsapp->normalizePhone($data['to']);

        if (!$phone) return response()->json(['message' => 'Invalid phone number.'], 422);

        $result = $this->whatsapp->sendText($phone, $data['message']);
        $log    = $this->logMessage($phone, $data['message'], $result, [
            'recipient_name'  => $data['recipient_name'] ?? null,
            'recipient_type'  => $data['recipient_type'] ?? null,
            'recipient_id'    => $data['recipient_id']   ?? null,
            'template_name'   => 'mobile_send',
            'sent_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['success' => $result['success'], 'log_id' => $log->id, 'error' => $result['error'] ?? null], $result['success'] ? 200 : 422);
    }

    public function myLogs(Request $request): JsonResponse
    {
        return response()->json(WhatsappLog::where('sent_by_user_id', $request->user()->id)->orderBy('created_at', 'desc')->limit(100)->get());
    }

    public function receivedLogs(Request $request): JsonResponse
    {
        $user  = $request->user();
        $phone = $this->whatsapp->normalizePhone($user->phone ?? '');

        if (!$phone) {
            $record = DB::table('app_records')
                ->join('app_tables', 'app_records.app_table_id', '=', 'app_tables.id')
                ->whereIn('app_tables.name', ['students', 'parents'])
                ->where(fn($q) => $q->whereRaw("JSON_EXTRACT(app_records.data, '$.email') = ?", [$user->email])
                    ->orWhereRaw("JSON_EXTRACT(app_records.data, '$.user_id') = ?", [$user->id]))
                ->select('app_records.data')->first();

            if ($record) {
                $d     = json_decode($record->data, true);
                $phone = $this->whatsapp->normalizePhone($d['whatsapp'] ?? $d['phone'] ?? '');
            }
        }

        if (!$phone) return response()->json([]);

        return response()->json(WhatsappLog::where('to_number', $phone)->where('status', 'sent')->orderBy('created_at', 'desc')->limit(100)->get());
    }

    public function logs(Request $request): JsonResponse
    {
        $q = WhatsappLog::orderBy('created_at', 'desc');
        if ($request->status)   $q->where('status', $request->status);
        if ($request->type)     $q->where('recipient_type', $request->type);
        if ($request->date)     $q->whereDate('created_at', $request->date);
        if ($request->template) $q->where('template_name', $request->template);
        return response()->json($q->limit(200)->get());
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'total'  => WhatsappLog::count(),
            'sent'   => WhatsappLog::where('status', 'sent')->count(),
            'failed' => WhatsappLog::where('status', 'failed')->count(),
            'today'  => WhatsappLog::whereDate('created_at', today())->count(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function logMessage(string $phone, string $message, array $result, array $extra = []): WhatsappLog
    {
        return WhatsappLog::create(array_merge([
            'to_number'           => $phone,
            'message'             => $message,
            'status'              => $result['success'] ? 'sent' : 'failed',
            'wa_message_id'       => $result['message_id'] ?? null,
            'error'               => $result['error']      ?? null,
        ], array_filter([
            'recipient_name'      => $extra['recipient_name']  ?? null,
            'recipient_type'      => $extra['recipient_type']  ?? null,
            'recipient_record_id' => $extra['recipient_id']    ?? null,
            'template_name'       => $extra['template_name']   ?? null,
            'sent_by_user_id'     => $extra['sent_by_user_id'] ?? null,
        ], fn($v) => $v !== null)));
    }

    private function resolveParentPhone(int|string $studentId, array $studentData, ?object $linkTable, ?object $parentsTable): array
    {
        if ($linkTable && $parentsTable) {
            $link = AppRecord::where('app_table_id', $linkTable->id)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [$studentId])
                ->first();
            if ($link) {
                $parent = AppRecord::find($link->data['parent_id'] ?? null);
                if ($parent) {
                    $pd    = $parent->data ?? [];
                    $phone = $this->whatsapp->normalizePhone($pd['whatsapp'] ?? $pd['phone'] ?? '');
                    if ($phone) {
                        return [$phone, trim(($pd['first_name'] ?? '') . ' ' . ($pd['last_name'] ?? '')), 'parent'];
                    }
                }
            }
        }

        $phone = $this->whatsapp->normalizePhone($studentData['whatsapp'] ?? $studentData['phone'] ?? '');
        return [$phone ?: null, trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? '')), 'student'];
    }
}
