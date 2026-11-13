<?php

use App\Enums\VendorSubscriptionStatus;
use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    public Vendor $vendor;
    public string $status = 'failed';
    public ?string $orderId = null;

    public function mount(): void
    {
        $this->vendor = app('vendor.context')->get() ?? abort(404);
        $this->status = request()->string('status', 'failed')->toString();
        $this->orderId = request()->string('order_id')->toString() ?: null;

        $this->redirectIfActive();
    }

    public function checkStatus(): void
    {
        $this->redirectIfActive();
    }

    private function redirectIfActive(): void
    {
        $vendor = $this->vendor->fresh();

        if ($vendor->status_subscription === VendorSubscriptionStatus::Active && $vendor->subscription_expired_at?->isFuture()) {
            $this->redirectRoute('vendor.admin.dashboard', ['subdomain' => $vendor->slug]);
        }
    }
};
?>

<div class="min-h-screen px-4 py-12" @if ($status === 'success') wire:poll.2s="checkStatus" @endif>
    <div class="mx-auto max-w-xl space-y-6">
        <flux:heading size="xl">Status Pembayaran</flux:heading>
        <flux:callout
            :variant="match($status) { 'success' => 'success', 'pending' => 'warning', default => 'danger' }">
            {{ match ($status) {
                'success' => 'Pembayaran diterima. Sedang mengaktifkan langganan, Anda akan diarahkan ke dashboard...',
                'pending' => 'Pembayaran masih menunggu penyelesaian.',
                default => 'Pembayaran gagal atau dibatalkan. Silakan coba kembali.',
            } }}
        </flux:callout>
        @if ($orderId)
            <flux:text>Order ID: {{ $orderId }}</flux:text>
        @endif
        @if ($status !== 'success')
            <flux:button :href="route('vendor.payment.subscription')" variant="primary">Kembali ke Pembayaran
            </flux:button>
        @endif
    </div>
</div>
