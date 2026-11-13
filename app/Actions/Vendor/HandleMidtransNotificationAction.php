<?php

namespace App\Actions\Vendor;

use App\Enums\VendorSubscriptionStatus;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleMidtransNotificationAction
{
    public function execute(array $notification): void
    {
        $orderId = $notification['order_id'] ?? null;
        $transactionStatus = $notification['transaction_status'] ?? null;

        if (! $orderId || ! $transactionStatus) {
            Log::warning('Incomplete Midtrans notification.', $notification);

            return;
        }

        $status = match ($transactionStatus) {
            'settlement' => 'success',
            'capture' => ($notification['fraud_status'] ?? 'accept') === 'accept' ? 'success' : 'pending',
            'pending' => 'pending',
            'expire' => 'expired',
            'deny', 'cancel', 'failure' => 'failed',
            'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => 'refunded',
            default => 'pending',
        };

        DB::transaction(function () use ($orderId, $notification, $status): void {
            // Lock baris payment: notifikasi paralel akan antre di sini.
            $payment = VendorPayment::query()
                ->where('midtrans_order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning('Midtrans payment not found.', ['order_id' => $orderId]);

                return;
            }

            // Sudah sukses: abaikan notifikasi lain yang tidak menaikkan status
            // (pending/expire yang telat datang, atau duplikat settlement).
            if ($payment->paid_at !== null && $status !== 'refunded') {
                return;
            }

            $payment->update([
                'status' => $status,
                'midtrans_transaction_id' => $notification['transaction_id'] ?? $payment->midtrans_transaction_id,
                'paid_at' => $status === 'success' ? now() : $payment->paid_at,
                'payment_details' => $notification,
            ]);

            if ($status !== 'success') {
                return;
            }

            $vendor = Vendor::query()->lockForUpdate()->findOrFail($payment->vendor_id);
            $start = $vendor->subscription_expired_at?->isFuture()
                ? $vendor->subscription_expired_at->copy()
                : now();

            $vendor->update([
                'status_subscription' => VendorSubscriptionStatus::Active,
                'subscription_started_at' => $vendor->subscription_started_at ?? now(),
                'subscription_expired_at' => $start->addDays(config('midtrans.subscription.duration_days')),
                'current_plan' => config('midtrans.subscription.plan_name'),
                'last_payment_date' => now(),
            ]);
        });
    }
}
