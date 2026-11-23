<?php

use App\Actions\Client\Dashboard\GetClientDashboardDataAction;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.client')] class extends Component {
    public $userClient;

    public function mount(): void
    {
        $this->userClient = auth('client')->user()->userClient;
    }

    #[Computed]
    public function dashboard(): array
    {
        if (! $this->userClient) {
            return ['stats' => ['active' => 0, 'pending' => 0, 'completed' => 0, 'total' => 0], 'recent' => collect()];
        }

        return app(GetClientDashboardDataAction::class)->execute($this->userClient->id);
    }

    #[Computed]
    public function shouldShowFakeBookingAlert(): bool
    {
        return $this->userClient && $this->userClient->count_fake_booking >= 3;
    }

    #[Computed]
    public function fakeBookingWarningMessage(): string
    {
        if (! $this->userClient) {
            return '';
        }

        $count = $this->userClient->count_fake_booking;
        $remaining = 5 - $count;

        return "Anda memiliki {$count} pemesanan yang tidak hadir. Mencapai 5 akan menangguhkan akun Anda. ({$remaining} tersisa)";
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan', 'rejected' => 'Ditolak', 'expired' => 'Kedaluwarsa', default => ucfirst($status),
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'amber', 'confirmed' => 'green', 'completed' => 'zinc',
            'cancelled', 'rejected' => 'red', default => 'zinc',
        };
    }
};
?>

<div class="clinic-page">
    @include('components.client.components.clinic-atmosphere')
    <div class="clinic-wrap">
        <div class="mx-auto max-w-6xl px-4 py-6 sm:py-10">
            @if ($this->shouldShowFakeBookingAlert)
                <div class="sticky top-0 z-50 bg-amber-50 border-b border-amber-200 px-4 py-3 sm:px-6 -mx-4 sm:-mx-6 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-amber-800">{{ $this->fakeBookingWarningMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-10">
                <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-[#1a5c40]">Dashboard pelanggan</p>
                        <h1 class="mt-3 text-3xl font-bold text-zinc-900 sm:text-4xl">Halo, {{ auth('client')->user()->name }}!</h1>
                        <p class="mt-3 max-w-xl text-zinc-600">Kelola reservasi, temukan layanan, dan rawat si kesayangan dengan lebih mudah.</p>
                    </div>
                    <flux:button href="{{ route('client.clinic') }}" wire:navigate variant="primary" class="!bg-[#1a5c40]">Buat reservasi</flux:button>
                </div>
                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([['label' => 'Reservasi aktif', 'value' => $this->dashboard['stats']['active'], 'icon' => 'calendar-days'], ['label' => 'Menunggu konfirmasi', 'value' => $this->dashboard['stats']['pending'], 'icon' => 'clock'], ['label' => 'Sudah selesai', 'value' => $this->dashboard['stats']['completed'], 'icon' => 'check-circle'], ['label' => 'Total reservasi', 'value' => $this->dashboard['stats']['total'], 'icon' => 'clipboard-document-list']] as $stat)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4"><div class="flex items-center gap-3 text-zinc-500"><flux:icon name="{{ $stat['icon'] }}" class="size-5" /><span class="text-sm">{{ $stat['label'] }}</span></div><p class="mt-3 text-3xl font-bold text-zinc-900">{{ $stat['value'] }}</p></div>
                    @endforeach
                </div>
            </section>

            <section class="mt-8">
                <div class="flex items-center justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-wider text-[#1a5c40]">Aktivitas terakhir</p><h2 class="mt-1 text-2xl font-bold text-zinc-900">Riwayat terbaru</h2></div><flux:button href="{{ route('client.my-bookings') }}" wire:navigate variant="ghost">Lihat semua</flux:button></div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @forelse ($this->dashboard['recent'] as $booking)
                        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="truncate font-semibold text-zinc-900">{{ $booking['service_item_name'] }}</h3><p class="mt-1 text-sm text-zinc-600">{{ $booking['vendor_name'] }} · {{ $booking['category_label'] }}</p></div><flux:badge size="sm" :color="$this->statusColor($booking['status'])">{{ $this->statusLabel($booking['status']) }}</flux:badge></div><div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-zinc-600"><span>{{ $booking['date_label'] }}</span><span class="font-medium text-zinc-800">{{ $booking['code'] }}</span></div></div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center md:col-span-2"><p class="font-semibold text-zinc-900">Belum ada riwayat reservasi</p><p class="mt-1 text-sm text-zinc-600">Mulai dengan mencari layanan untuk si kesayangan.</p><flux:button href="{{ route('client.clinic') }}" wire:navigate variant="primary" class="mt-5 !bg-[#1a5c40]">Cari layanan</flux:button></div>
                    @endforelse
                </div>
            </section>

            <section class="mt-8"><p class="text-sm font-semibold uppercase tracking-wider text-[#1a5c40]">Pilih kebutuhan</p><h2 class="mt-1 text-2xl font-bold text-zinc-900">Cari layanan</h2><div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach ([['name' => 'Klinik', 'description' => 'Konsultasi dan perawatan kesehatan.', 'icon' => 'heart'], ['name' => 'Grooming', 'description' => 'Perawatan bersih, rapi, dan nyaman.', 'icon' => 'sparkles'], ['name' => 'Pet hotel', 'description' => 'Tempat tinggal nyaman saat Anda bepergian.', 'icon' => 'home']] as $service)
                    <a href="{{ route('client.clinic') }}" wire:navigate class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-[#1a5c40]"><flux:icon name="{{ $service['icon'] }}" class="size-7 text-[#1a5c40]" /><h3 class="mt-4 font-bold text-zinc-900">{{ $service['name'] }}</h3><p class="mt-1 text-sm text-zinc-600">{{ $service['description'] }}</p><span class="mt-4 inline-block text-sm font-semibold text-[#1a5c40]">Cari layanan →</span></a>
                @endforeach
            </div></section>
        </div>
    </div>
    @include('components.client.components.clinic-footer')
</div>
