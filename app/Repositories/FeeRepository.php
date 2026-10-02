<?php

namespace App\Repositories;

use App\Contracts\FeeRepositoryInterface;
use App\Models\AppRecord;
use App\Models\AppTable;

class FeeRepository implements FeeRepositoryInterface
{
    private function feeTableId(): ?int
    {
        return AppTable::where('name', 'fee_payments')->value('id');
    }

    private function formatRecord(AppRecord $record): array
    {
        $d = $record->data;
        $amount     = (float)($d['amount']      ?? 0);
        $amountPaid = (float)($d['amount_paid'] ?? 0);

        return [
            'id'                  => $record->id,
            'receipt_no'          => $d['receipt_no']          ?? '',
            'fee_type'            => $d['fee_type']             ?? 'Fee',
            'amount'              => $amount,
            'amount_paid'         => $amountPaid,
            'due_amount'          => max(0, $amount - $amountPaid),
            'due_date'            => $d['due_date']             ?? '',
            'status'              => $d['status']               ?? 'pending',
            'description'         => $d['description']          ?? '',
            'payment_date'        => $d['payment_date']         ?? '',
            'payment_mode'        => $d['payment_mode']         ?? '',
            'razorpay_payment_id' => $d['razorpay_payment_id']  ?? '',
        ];
    }

    public function pendingByStudent(int|string $studentId): array
    {
        $tableId = $this->feeTableId();
        if (!$tableId) return [];

        return AppRecord::where('app_table_id', $tableId)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [(string) $studentId])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) IN ('pending','partial')")
            ->get()
            ->map(fn($r) => $this->formatRecord($r))
            ->values()
            ->toArray();
    }

    public function paidByStudent(int|string $studentId): array
    {
        $tableId = $this->feeTableId();
        if (!$tableId) return [];

        return AppRecord::where('app_table_id', $tableId)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.student_id')) = ?", [(string) $studentId])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.status')) = 'paid'")
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(data,'$.payment_date')) DESC")
            ->get()
            ->map(fn($r) => $this->formatRecord($r))
            ->values()
            ->toArray();
    }

    public function markAsPaid(array $feeIds, string $orderId, string $paymentId): void
    {
        $tableId = $this->feeTableId();
        if (!$tableId) return;

        foreach ($feeIds as $feeId) {
            $record = AppRecord::where('app_table_id', $tableId)->find($feeId);
            if (!$record) continue;

            $d = $record->data;
            $d['status']              = 'paid';
            $d['payment_mode']        = 'online';
            $d['payment_date']        = now()->format('Y-m-d');
            $d['amount_paid']         = $d['amount'] ?? 0;
            $d['razorpay_order_id']   = $orderId;
            $d['razorpay_payment_id'] = $paymentId;
            $record->update(['data' => $d]);
        }
    }

    public function markAsPaidFromWebhook(array $feeIds, string $paymentId, float $amount): void
    {
        $tableId = $this->feeTableId();
        if (!$tableId) return;

        foreach ($feeIds as $feeId) {
            $record = AppRecord::where('app_table_id', $tableId)->find(trim($feeId));
            if (!$record || ($record->data['status'] ?? '') === 'paid') continue;

            $d = $record->data;
            $d['status']              = 'paid';
            $d['payment_mode']        = 'online';
            $d['payment_date']        = now()->format('Y-m-d');
            $d['amount_paid']         = $d['amount'] ?? $amount;
            $d['razorpay_payment_id'] = $paymentId;
            $record->update(['data' => $d]);
        }
    }
}
