<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function isValidNotification(array $notification): bool
    {
        $signature = $notification['signature_key'] ?? '';
        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';

        if (! $signature || ! $orderId || ! $statusCode || ! $grossAmount) {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.config('midtrans.server_key'));

        return hash_equals($expected, $signature);
    }

    /**
     * @return array{token: string, redirect_url: string}
     */
    public function createSubscriptionPayment(string $orderId, string $vendorName, string $vendorEmail, string $finishUrl): array
    {
        $amount = config('midtrans.subscription.amount');
        $currency = config('midtrans.subscription.currency');
        $separator = str_contains($finishUrl, '?') ? '&' : '?';
        $errorUrl = $finishUrl.$separator.'status=failed';
        $pendingUrl = $finishUrl.$separator.'status=pending';
        $finishUrl .= $separator.'status=success';

        $response = Snap::createTransaction([
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details' => [[
                'id' => 'SUBSCRIPTION-MONTHLY',
                'price' => $amount,
                'quantity' => 1,
                'name' => config('midtrans.subscription.plan_name'),
            ]],
            'customer_details' => [
                'first_name' => $vendorName,
                'email' => $vendorEmail,
            ],
            'callbacks' => [
                'finish' => $finishUrl,
                'unfinish' => $pendingUrl,
                'error' => $errorUrl,
            ],
            'custom_field1' => $currency,
        ]);

        return [
            'token' => $response->token,
            'redirect_url' => $response->redirect_url,
        ];
    }
}
