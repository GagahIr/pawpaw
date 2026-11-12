<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $status = 'failed';
    public ?string $orderId = null;

    public function mount(): void
    {
        $this->status = request()->string('status', 'failed')->toString();
        $this->orderId = request()->string('order_id')->toString() ?: null;
    }
};
?>

<div class="min-h-screen px-4 py-12">
    <div class="mx-auto max-w-xl space-y-6">
        <flux:heading size="xl">Status Pembayaran</flux:heading>
        <flux:callout :variant="match ($status) { 'success' => 'success', 'pending' => 'warning', default => 'danger' }">
            {{ match ($status) {
                'success' => 'Pembayaran diterima. Aktivasi langganan menunggu notifikasi Midtrans.',
                'pending' => 'Pembayaran masih menunggu penyelesaian.',
                default => 'Pembayaran gagal atau dibatalkan. Silakan coba kembali.',
            } }}
        </flux:callout>
        @if ($orderId)
            <flux:text>Order ID: {{ $orderId }}</flux:text>
        @endif
        <flux:button :href="route('vendor.payment.subscription')" variant="primary">Kembali ke Pembayaran</flux:button>
    </div>
</div>
