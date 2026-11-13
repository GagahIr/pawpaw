<?php

use App\Actions\Vendor\CreateSubscriptionPaymentAction;
use App\Enums\VendorVerificationStatus;
use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Enums\VendorSubscriptionStatus;

new #[Layout('layouts.app')] class extends Component {
    public Vendor $vendor;
    public ?string $snapToken = null;

    public function mount(): void
    {
        $this->vendor = app('vendor.context')->get() ?? abort(404);

        if ($this->vendor->status_subscription === VendorSubscriptionStatus::Active && $this->vendor->subscription_expired_at?->isFuture()) {
            $this->redirectRoute('vendor.admin.dashboard', ['subdomain' => $this->vendor->slug]);
        }
    }

    public function createPayment(CreateSubscriptionPaymentAction $action): void
    {
        if ($this->vendor->fresh()->status_subscription === VendorSubscriptionStatus::Active && $this->vendor->fresh()->subscription_expired_at?->isFuture()) {
            $this->redirectRoute('vendor.admin.dashboard', ['subdomain' => $this->vendor->slug]);

            return;
        }

        $this->snapToken = $action->execute($this->vendor)['token'];
        $this->dispatch('open-midtrans-snap', token: $this->snapToken);
    }
};
?>

<div class="min-h-screen px-4 py-12">
    <div class="mx-auto max-w-xl space-y-6">
        <flux:heading size="xl">Langganan Mitra</flux:heading>
        <flux:subheading>Aktifkan akses operasional mitra melalui pembayaran manual.</flux:subheading>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        @if ($vendor->status_verification !== VendorVerificationStatus::Approved)
            <flux:callout variant="info" icon="information-circle" heading="Menunggu verifikasi">
                Pembayaran dibuka setelah akun disetujui oleh Super Admin.
            </flux:callout>
        @endif

        <flux:card class="space-y-4">
            <div class="flex justify-between gap-4">
                <span class="text-zinc-500">Paket</span>
                <span class="font-medium">{{ config('midtrans.subscription.plan_name') }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-zinc-500">Biaya</span>
                <span class="font-medium">Rp
                    {{ number_format(config('midtrans.subscription.amount'), 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-zinc-500">Masa aktif</span>
                <span class="font-medium">{{ config('midtrans.subscription.duration_days') }} hari</span>
            </div>
            <flux:callout variant="secondary" icon="arrow-path">Tidak diperpanjang otomatis. Perpanjangan dilakukan
                manual.</flux:callout>
            <flux:error name="payment" />
            <flux:button wire:click="createPayment" variant="primary" class="w-full"
                :disabled="$vendor->status_verification !== VendorVerificationStatus::Approved">
                Bayar Sekarang
            </flux:button>
        </flux:card>
    </div>
</div>

@script
    <script>
        $wire.on('open-midtrans-snap', ({
            token
        }) => {
            const open = () => window.snap.pay(token, {
                onSuccess: result => window.location.href =
                    '{{ route('vendor.payment.callback', ['subdomain' => $vendor->slug]) }}?status=success&order_id=' +
                    encodeURIComponent(result.order_id),
                onPending: result => window.location.href =
                    '{{ route('vendor.payment.callback', ['subdomain' => $vendor->slug]) }}?status=pending&order_id=' +
                    encodeURIComponent(result.order_id),
                onError: result => window.location.href =
                    '{{ route('vendor.payment.callback', ['subdomain' => $vendor->slug]) }}?status=failed&order_id=' +
                    encodeURIComponent(result.order_id),
                onClose: () => {},
            });

            if (window.snap) {
                open();
                return;
            }

            const script = document.createElement('script');
            script.src =
                '{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}';
            script.dataset.clientKey = '{{ config('midtrans.client_key') }}';
            script.onload = open;
            document.head.appendChild(script);
        });
    </script>
@endscript
