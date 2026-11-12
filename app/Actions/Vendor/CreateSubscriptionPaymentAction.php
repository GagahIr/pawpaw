<?php

namespace App\Actions\Vendor;

use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Services\MidtransService;
use Illuminate\Support\Str;

class CreateSubscriptionPaymentAction
{
    public function __construct(private MidtransService $midtransService) {}

    /**
     * @return array{token: string, redirect_url: string}
     */
    public function execute(Vendor $vendor): array
    {
        $orderId = 'VENDOR-'.$vendor->id.'-'.Str::upper(Str::random(10));
        $response = $this->midtransService->createSubscriptionPayment(
            $orderId,
            $vendor->name,
            $vendor->email,
            route('vendor.payment.callback', ['subdomain' => $vendor->slug]),
        );

        VendorPayment::create([
            'vendor_id' => $vendor->id,
            'midtrans_order_id' => $orderId,
            'amount' => config('midtrans.subscription.amount'),
            'currency' => config('midtrans.subscription.currency'),
            'status' => 'pending',
            'payment_details' => $response,
        ]);

        return $response;
    }
}
