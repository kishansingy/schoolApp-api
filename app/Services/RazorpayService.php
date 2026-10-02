<?php

namespace App\Services;

use App\Contracts\RazorpayServiceInterface;
use Razorpay\Api\Api;

class RazorpayService implements RazorpayServiceInterface
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret')
        );
    }

    public function createOrder(int $amountPaise, string $receipt, array $notes): array
    {
        $order = $this->api->order->create([
            'amount'   => $amountPaise,
            'currency' => 'INR',
            'receipt'  => $receipt,
            'notes'    => $notes,
        ]);

        return [
            'order_id' => $order->id,
            'amount'   => $amountPaise,
            'currency' => 'INR',
            'key_id'   => $this->keyId(),
        ];
    }

    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac(
            'sha256',
            $orderId . '|' . $paymentId,
            config('services.razorpay.key_secret')
        );

        return hash_equals($expected, $signature);
    }

    public function keyId(): string
    {
        return config('services.razorpay.key_id');
    }
}
