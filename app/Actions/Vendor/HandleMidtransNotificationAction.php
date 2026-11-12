<?php

namespace App\Actions\Vendor;

use App\Enums\VendorSubscriptionStatus;
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

        $payment = VendorPayment::query()->where('midtrans_order_id', $orderId)->first();

        if (! $payment) {
            Log::warning('Midtrans payment not found.', ['order_id' => $orderId]);

            return;
        }

        $status = match ($transactionStatus) {
            'settlement', 'capture' => 'success',
            'pending' => 'pending',
            'expire' => 'expired',
            'deny', 'cancel' => 'failed',
            default => 'pending',
        };

        DB::transaction(function () use ($payment, $notification, $status): void {
            $wasPaid = $payment->paid_at !== null;
            $payment->update([
                'status' => $status,
                'midtrans_transaction_id' => $notification['transaction_id'] ?? $payment->midtrans_transaction_id,
                'paid_at' => $status === 'success' ? ($payment->paid_at ?? now()) : $payment->paid_at,
                'payment_details' => $notification,
            ]);

            if ($status !== 'success' || $wasPaid) {
                return;
            }

            $vendor = $payment->vendor;
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
