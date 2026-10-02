<?php

namespace App\Contracts;

interface FeeRepositoryInterface
{
    /**
     * Get pending/partial fee records for a student.
     */
    public function pendingByStudent(int|string $studentId): array;

    /**
     * Get paid fee records for a student.
     */
    public function paidByStudent(int|string $studentId): array;

    /**
     * Mark a list of fee record IDs as paid via online payment.
     */
    public function markAsPaid(array $feeIds, string $orderId, string $paymentId): void;

    /**
     * Mark fee IDs as paid from webhook payload (idempotent).
     */
    public function markAsPaidFromWebhook(array $feeIds, string $paymentId, float $amount): void;
}
