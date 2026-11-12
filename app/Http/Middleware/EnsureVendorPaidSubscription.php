<?php

namespace App\Http\Middleware;

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorPaidSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = app('vendor.context')->get();

        if (! $vendor || $vendor->status_verification !== VendorVerificationStatus::Approved) {
            return redirect()->route('vendor.payment.subscription')->with('info', 'Akun menunggu verifikasi Super Admin.');
        }

        if (
            $vendor->status_subscription !== VendorSubscriptionStatus::Active
            || ! $vendor->subscription_expired_at
            || $vendor->subscription_expired_at->isPast()
        ) {
            return redirect()->route('vendor.payment.subscription')->with('info', 'Selesaikan pembayaran langganan untuk mengakses fitur mitra.');
        }

        return $next($request);
    }
}
