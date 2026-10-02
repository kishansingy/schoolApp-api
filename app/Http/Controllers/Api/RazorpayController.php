<?php

namespace App\Http\Controllers\Api;

use App\Contracts\FeeRepositoryInterface;
use App\Contracts\RazorpayServiceInterface;
use App\Contracts\StudentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\VerifyPaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RazorpayController extends Controller
{
    public function __construct(
        private readonly RazorpayServiceInterface  $razorpay,
        private readonly FeeRepositoryInterface    $feeRepo,
        private readonly StudentRepositoryInterface $studentRepo,
    ) {}

    // ── Pending fees for a student ────────────────────────────────────────────
    public function pendingFees(Request $request, int|string $studentId): JsonResponse
    {
        return response()->json([
            'student'   => $this->studentRepo->findById($studentId),
            'fees'      => $this->feeRepo->pendingByStudent($studentId),
            'paid_fees' => $this->feeRepo->paidByStudent($studentId),
        ]);
    }

    // ── Create Razorpay order ─────────────────────────────────────────────────
    public function createOrder(CreateOrderRequest $request): JsonResponse
    {
        $amountPaise = (int) round($request->amount * 100);
        $receipt     = 'fee_' . $request->student_id . '_' . time();
        $notes       = array_merge($request->notes ?? [], [
            'student_id' => (string) $request->student_id,
            'fee_ids'    => implode(',', $request->fee_ids),
        ]);

        $order = $this->razorpay->createOrder($amountPaise, $receipt, $notes);

        return response()->json($order);
    }

    // ── Verify payment signature & mark fees paid ─────────────────────────────
    public function verifyPayment(VerifyPaymentRequest $request): JsonResponse
    {
        $valid = $this->razorpay->verifySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if (!$valid) {
            return response()->json(['error' => 'Payment verification failed. Invalid signature.'], 422);
        }

        $this->feeRepo->markAsPaid(
            $request->fee_ids,
            $request->razorpay_order_id,
            $request->razorpay_payment_id
        );

        return response()->json([
            'success'    => true,
            'payment_id' => $request->razorpay_payment_id,
            'message'    => 'Payment successful. Fee records updated.',
        ]);
    }

    // ── Webhook (Razorpay server → our server, no auth) ───────────────────────
    public function webhook(Request $request): JsonResponse
    {
        if (!$this->isValidWebhook($request)) {
            return response()->json(['error' => 'Invalid webhook signature'], 400);
        }

        if ($request->input('event') === 'payment.captured') {
            $this->handlePaymentCaptured($request->input('payload.payment.entity', []));
        }

        return response()->json(['status' => 'ok']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function isValidWebhook(Request $request): bool
    {
        $secret = config('services.razorpay.webhook_secret');
        if (!$secret) return true; // webhook secret not configured — allow

        $expected  = hash_hmac('sha256', $request->getContent(), $secret);
        $signature = $request->header('X-Razorpay-Signature', '');

        return hash_equals($expected, $signature);
    }

    private function handlePaymentCaptured(array $payload): void
    {
        $notes     = $payload['notes'] ?? [];
        $feeIdsStr = $notes['fee_ids'] ?? '';
        if (!$feeIdsStr) return;

        $feeIds = explode(',', $feeIdsStr);
        $amount = isset($payload['amount']) ? (float)($payload['amount'] / 100) : 0;

        $this->feeRepo->markAsPaidFromWebhook($feeIds, $payload['id'] ?? '', $amount);
    }
}
