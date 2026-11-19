<?php

use App\Models\VendorPayment;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.super-admin')] class extends Component {
    public bool $showDetailModal = false;
    public ?VendorPayment $selectedTransaction = null;

    #[On('viewTransaction')]
    public function viewTransaction(int $rowId): void
    {
        $this->selectedTransaction = VendorPayment::query()->with('vendor')->findOrFail($rowId);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedTransaction = null;
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'settlement', 'capture' => 'green',
            'pending' => 'yellow',
            'expire', 'cancel' => 'zinc',
            default => 'red',
        };
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Manage Transaksi</flux:heading>
        <flux:subheading>Pantau pembayaran langganan mitra. Status berasal dari webhook Midtrans dan tidak dapat diubah manual.</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $summary = [
                ['label' => 'Total transaksi', 'value' => VendorPayment::query()->count()],
                ['label' => 'Menunggu pembayaran', 'value' => VendorPayment::query()->where('status', 'pending')->count()],
                ['label' => 'Berhasil', 'value' => VendorPayment::query()->whereIn('status', ['settlement', 'capture'])->count()],
                ['label' => 'Gagal / kedaluwarsa', 'value' => VendorPayment::query()->whereIn('status', ['expire', 'cancel', 'deny', 'failure'])->count()],
            ];
        @endphp
        @foreach ($summary as $item)
            <flux:card><flux:subheading>{{ $item['label'] }}</flux:subheading><flux:heading size="xl" class="mt-2">{{ number_format($item['value']) }}</flux:heading></flux:card>
        @endforeach
    </div>

    <flux:card><livewire:super-admin.datatables.transactions-table /></flux:card>

    <flux:modal wire:model="showDetailModal" class="w-full max-w-2xl">
        @if ($selectedTransaction)
            <div class="space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><flux:heading size="lg">Detail transaksi</flux:heading><flux:subheading>{{ $selectedTransaction->midtrans_order_id }}</flux:subheading></div>
                    <flux:badge color="{{ $this->statusColor($selectedTransaction->status) }}">{{ ucfirst(str_replace('_', ' ', $selectedTransaction->status)) }}</flux:badge>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:card><p class="text-xs text-zinc-500">Mitra</p><p class="mt-1 font-medium">{{ $selectedTransaction->vendor?->name ?? '-' }}</p><p class="text-sm text-zinc-500">{{ $selectedTransaction->vendor?->email ?? '-' }}</p></flux:card>
                    <flux:card><p class="text-xs text-zinc-500">Nominal</p><p class="mt-1 font-medium">{{ $selectedTransaction->currency }} {{ number_format($selectedTransaction->amount, 0, ',', '.') }}</p><p class="text-sm text-zinc-500">Dibuat {{ $selectedTransaction->created_at?->format('d M Y H:i') }}</p></flux:card>
                </div>
                <div class="grid gap-4 sm:grid-cols-2"><div><p class="text-xs text-zinc-500">Midtrans transaction ID</p><p class="break-all text-sm">{{ $selectedTransaction->midtrans_transaction_id ?? '-' }}</p></div><div><p class="text-xs text-zinc-500">Waktu pembayaran</p><p class="text-sm">{{ $selectedTransaction->paid_at?->format('d M Y H:i') ?? 'Belum dibayar' }}</p></div></div>
                <div><p class="text-xs text-zinc-500">Data gateway</p><pre class="mt-2 max-h-64 overflow-auto rounded-lg bg-zinc-100 p-3 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ json_encode($selectedTransaction->payment_details ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                <div class="flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-700"><flux:button wire:click="closeDetail" variant="ghost">Tutup</flux:button></div>
            </div>
        @endif
    </flux:modal>
</div>