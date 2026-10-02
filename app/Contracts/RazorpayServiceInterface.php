<?php

namespace App\Contracts;

interface RazorpayServiceInterface
{
    /**
     * Create a Razorpay order and return order details.
     */
    public function createOrder(int $amountPaise, string $receipt, array $notes): array;

    /**
     * Verify the payment signature. Returns true if valid.
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool;

    /**
     * Return the public key_id for the frontend checkout.
     */
    public function keyId(): string;
}
