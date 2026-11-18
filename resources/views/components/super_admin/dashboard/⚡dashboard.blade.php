<?php

use App\Actions\SuperAdmin\Dashboard\GetSuperAdminDashboardAnalyticsAction;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.super-admin')] class extends Component {
    public function with(GetSuperAdminDashboardAnalyticsAction $action): array
    {
        return [
            'analytics' => $action->getOverview(),
            'monthLabel' => now()->locale('id')->translatedFormat('F Y'),
        ];
    }

    public function formatRupiah(int|float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public function badgeClasses(string $group, string $status): string
    {
        $palette = [
            'payment' => [
                'success' => 'bg-emerald-50 text-emerald-700',
                'pending' => 'bg-amber-50 text-amber-700',
                'failed' => 'bg-red-50 text-red-700',
                'expired' => 'bg-zinc-100 text-zinc-600',
                'refunded' => 'bg-blue-50 text-blue-700',
            ],
            'booking' => [
                'completed' => 'bg-emerald-50 text-emerald-700',
                'confirmed' => 'bg-blue-50 text-blue-700',
                'pending' => 'bg-amber-50 text-amber-700',
                'cancelled' => 'bg-zinc-100 text-zinc-600',
                'rejected' => 'bg-red-50 text-red-700',
            ],
            'subscription' => [
                'active' => 'bg-emerald-50 text-emerald-700',
                'expired' => 'bg-amber-50 text-amber-700',
                'suspended' => 'bg-red-50 text-red-700',
                'inactive' => 'bg-zinc-100 text-zinc-600',
            ],
            'verification' => [
                'approved' => 'bg-emerald-50 text-emerald-700',
                'pending' => 'bg-amber-50 text-amber-700',
                'rejected' => 'bg-red-50 text-red-700',
            ],
        ];

        return $palette[$group][$status] ?? 'bg-zinc-100 text-zinc-600';
    }

    public function statusLabel(string $group, string $status): string
    {
        $labels = [
            'payment' => ['success' => 'Berhasil', 'pending' => 'Menunggu', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'refunded' => 'Refund'],
            'subscription' => ['active' => 'Aktif', 'expired' => 'Kedaluwarsa', 'suspended' => 'Ditangguhkan', 'inactive' => 'Belum aktif'],
            'verification' => ['approved' => 'Terverifikasi', 'pending' => 'Menunggu', 'rejected' => 'Ditolak'],
        ];

        return $labels[$group][$status] ?? ucfirst($status);
    }
};
?>

<div class="space-y-6">
    {{-- Header --}}
    <div>
        <flux:heading size="xl">Dashboard</flux:heading>
        <flux:subheading>Ringkasan platform PawPaw. Angka bulanan menghitung {{ $monthLabel }}.</flux:subheading>
    </div>

    {{-- 1. Key Metrics --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Metrik utama">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500">Total pendapatan bulan ini</p>
                    <p class="mt-2 truncate text-2xl font-bold text-zinc-900">{{ $this->formatRupiah($analytics['metrics']['revenue']) }}</p>
                </div>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#DCEBE1] text-[#1B5E43]">
                    <flux:icon name="banknotes" class="size-5" />
                </span>
            </div>
            <p class="mt-3 text-xs text-zinc-500">Pembayaran langganan berstatus berhasil</p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500">Mitra aktif</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900">{{ number_format($analytics['metrics']['active_vendors'], 0, ',', '.') }}</p>
                </div>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#DCEBE1] text-[#1B5E43]">
                    <flux:icon name="building-storefront" class="size-5" />
                </span>
            </div>
            <p class="mt-3 text-xs text-zinc-500">Langganan aktif dan belum kedaluwarsa</p>
        </article>

        <a href="{{ route('super_admin.verification_vendors') }}" wire:navigate
            class="block rounded-xl border border-zinc-200 bg-white p-5 shadow-xs transition hover:border-[#1B5E43]">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500">Menunggu verifikasi</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900">{{ number_format($analytics['metrics']['pending_verification'], 0, ',', '.') }}</p>
                </div>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <flux:icon name="shield-check" class="size-5" />
                </span>
            </div>
            <p class="mt-3 text-xs text-zinc-500">Buka antrean verifikasi</p>
        </a>

        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-zinc-500">Total booking bulan ini</p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900">{{ number_format($analytics['metrics']['bookings'], 0, ',', '.') }}</p>
                </div>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <flux:icon name="calendar-days" class="size-5" />
                </span>
            </div>
            <p class="mt-3 text-xs text-zinc-500">Klinik, grooming, dan pet hotel</p>
        </article>
    </section>

    {{-- 2 & 5. Revenue chart + Payment health --}}
    <section class="grid grid-cols-1 items-stretch gap-5 lg:grid-cols-12">
        <div
            x-data="superAdminRevenueChart"
            data-payload="{{ json_encode($analytics['revenue']) }}"
            x-init="initChart($el.dataset.payload)"
            class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:col-span-8"
        >
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900">Tren pendapatan 12 bulan</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">Total pembayaran berhasil per bulan</p>
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
                    <div>
                        <p class="flex items-center gap-1.5 text-zinc-500">
                            <span class="size-2 rounded-full bg-zinc-400"></span> Bulan lalu
                        </p>
                        <p class="mt-0.5 text-sm font-semibold text-zinc-700">{{ $this->formatRupiah($analytics['revenue']['last_month']) }}</p>
                    </div>
                    <div>
                        <p class="flex items-center gap-1.5 text-zinc-500">
                            <span class="size-2 rounded-full bg-[#1B5E43]"></span> Bulan ini
                        </p>
                        <p class="mt-0.5 text-sm font-semibold text-zinc-900">{{ $this->formatRupiah($analytics['revenue']['this_month']) }}</p>
                    </div>
                    @php($change = $analytics['revenue']['change_percent'])
                    @if ($change === null)
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 font-semibold text-zinc-600">Belum ada pembanding</span>
                    @else
                        <span class="rounded-full px-2.5 py-1 font-semibold {{ $change >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            {{ $change >= 0 ? '▲' : '▼' }} {{ number_format(abs($change), 1, ',', '.') }}% dari bulan lalu
                        </span>
                    @endif
                </div>
            </div>

            @if (! $analytics['revenue']['has_data'])
                <div class="mb-2 rounded-2xl border border-zinc-100 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-500">
                    Belum ada pembayaran berhasil dalam 12 bulan terakhir. Grafik terisi otomatis saat mitra membayar langganan.
                </div>
            @endif

            <div wire:ignore class="flex w-full flex-1 items-center">
                <div x-ref="canvas" class="min-h-[300px] w-full"></div>
            </div>
        </div>

        <div
            x-data="superAdminBarChart"
            data-payload="{{ json_encode($analytics['payment_health']) }}"
            x-init="initChart($el.dataset.payload)"
            class="flex flex-col rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:col-span-4"
        >
            <h2 class="text-base font-semibold text-zinc-900">Kesehatan pembayaran</h2>
            <p class="mt-0.5 text-xs text-zinc-500">Jumlah transaksi per status, {{ $monthLabel }}</p>

            <div wire:ignore class="mt-4 flex w-full flex-1 items-center">
                <div x-ref="canvas" class="min-h-[260px] w-full"></div>
            </div>
        </div>
    </section>

    {{-- 3 & 4. Vendor status + Booking distribution --}}
    <section class="grid grid-cols-1 items-stretch gap-5 lg:grid-cols-12">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:col-span-5">
            <h2 class="text-base font-semibold text-zinc-900">Status mitra</h2>
            <p class="mt-0.5 text-xs text-zinc-500">Langganan dan verifikasi seluruh mitra</p>

            <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                @foreach ([
                    ['title' => 'Langganan', 'chart' => $analytics['vendor_status']['subscription']],
                    ['title' => 'Verifikasi', 'chart' => $analytics['vendor_status']['verification']],
                ] as $group)
                    <div
                        x-data="superAdminPieChart"
                        data-payload="{{ json_encode($group['chart']) }}"
                        x-init="initChart($el.dataset.payload)"
                    >
                        <p class="text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $group['title'] }}</p>

                        <div wire:ignore class="mx-auto mt-2 flex w-full max-w-[200px] items-center justify-center">
                            <div x-ref="canvas" class="min-h-[200px] w-full"></div>
                        </div>

                        <ul class="mt-3 space-y-1.5">
                            @foreach ($group['chart']['labels'] as $index => $label)
                                <li class="flex items-center justify-between gap-2 text-xs">
                                    <span class="flex min-w-0 items-center gap-2 text-zinc-600">
                                        <span class="size-2.5 shrink-0 rounded-xs" style="background-color: {{ $group['chart']['colors'][$index] }}"></span>
                                        <span class="truncate">{{ $label }}</span>
                                    </span>
                                    <span class="font-semibold text-zinc-900">{{ $group['chart']['values'][$index] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:col-span-7">
            <h2 class="text-base font-semibold text-zinc-900">Distribusi booking</h2>
            <p class="mt-0.5 text-xs text-zinc-500">{{ $analytics['metrics']['bookings'] }} booking pada {{ $monthLabel }}, berdasarkan tanggal kunjungan</p>

            <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach ([
                    ['title' => 'Per jenis layanan', 'chart' => $analytics['booking_distribution']['by_type']],
                    ['title' => 'Per status', 'chart' => $analytics['booking_distribution']['by_status']],
                ] as $group)
                    <div
                        x-data="superAdminBarChart"
                        data-payload="{{ json_encode($group['chart']) }}"
                        x-init="initChart($el.dataset.payload)"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $group['title'] }}</p>

                        <div wire:ignore class="mt-2 w-full">
                            <div x-ref="canvas" class="min-h-[260px] w-full"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 6. Tables --}}
    <section class="grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Data terbaru">
        {{-- Top 5 vendors --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-xs">
            <div class="border-b border-zinc-100 px-6 py-4">
                <h2 class="text-base font-semibold text-zinc-900">Top 5 mitra</h2>
                <p class="mt-0.5 text-xs text-zinc-500">Berdasarkan total pendapatan sepanjang waktu</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-zinc-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Mitra</th>
                            <th class="px-3 py-3 font-medium">Pendapatan</th>
                            <th class="px-3 py-3 font-medium">Langganan</th>
                            <th class="px-6 py-3 font-medium">Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($analytics['tables']['top_vendors'] as $vendor)
                            <tr>
                                <td class="px-6 py-3 font-medium text-zinc-900">{{ $vendor->name }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-zinc-700">{{ $this->formatRupiah((int) $vendor->revenue_total) }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->badgeClasses('subscription', $vendor->effective_subscription) }}">
                                        {{ $this->statusLabel('subscription', $vendor->effective_subscription) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->badgeClasses('verification', $vendor->status_verification->value) }}">
                                        {{ $this->statusLabel('verification', $vendor->status_verification->value) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-zinc-500">Belum ada mitra dengan pembayaran berhasil.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent payments --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-xs">
            <div class="border-b border-zinc-100 px-6 py-4">
                <h2 class="text-base font-semibold text-zinc-900">Pembayaran terbaru</h2>
                <p class="mt-0.5 text-xs text-zinc-500">Lima transaksi langganan terakhir</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-zinc-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Mitra</th>
                            <th class="px-3 py-3 font-medium">Jumlah</th>
                            <th class="px-3 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($analytics['tables']['recent_payments'] as $payment)
                            <tr>
                                <td class="px-6 py-3 font-medium text-zinc-900">{{ $payment->vendor?->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-zinc-700">{{ $this->formatRupiah($payment->amount) }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->badgeClasses('payment', $payment->status) }}">
                                        {{ $this->statusLabel('payment', $payment->status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-zinc-500">{{ ($payment->paid_at ?? $payment->created_at)->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-zinc-500">Belum ada pembayaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pending verifications --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-xs">
            <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900">Menunggu verifikasi</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">Yang paling lama menunggu di urutan atas</p>
                </div>
                <a href="{{ route('super_admin.verification_vendors') }}" wire:navigate
                    class="shrink-0 text-xs font-semibold text-[#1B5E43] hover:underline">Lihat semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-zinc-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Mitra</th>
                            <th class="px-3 py-3 font-medium">Bergabung</th>
                            <th class="px-6 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($analytics['tables']['pending_verifications'] as $vendor)
                            <tr>
                                <td class="px-6 py-3 font-medium text-zinc-900">{{ $vendor->name }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-zinc-500">{{ $vendor->join_at?->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap px-6 py-3 text-right">
                                    <a href="{{ route('super_admin.verification_vendors') }}" wire:navigate
                                        class="text-xs font-semibold text-[#1B5E43] hover:underline">Tinjau</a>
                                    <span class="px-1 text-zinc-300">|</span>
                                    <a href="{{ route('super_admin.manage_vendors') }}" wire:navigate
                                        class="text-xs font-semibold text-zinc-600 hover:underline">Kelola</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-zinc-500">Tidak ada mitra yang menunggu verifikasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent bookings --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-xs">
            <div class="border-b border-zinc-100 px-6 py-4">
                <h2 class="text-base font-semibold text-zinc-900">Booking terbaru</h2>
                <p class="mt-0.5 text-xs text-zinc-500">Lima booking terakhir dari semua layanan</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-zinc-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Mitra</th>
                            <th class="px-3 py-3 font-medium">Jenis</th>
                            <th class="px-3 py-3 font-medium">Tanggal</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($analytics['tables']['recent_bookings'] as $booking)
                            <tr>
                                <td class="px-6 py-3 font-medium text-zinc-900">{{ $booking['vendor_name'] }}</td>
                                <td class="px-3 py-3 text-zinc-700">{{ $booking['type_label'] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-zinc-500">{{ $booking['booked_at']->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->badgeClasses('booking', $booking['status']) }}">
                                        {{ $booking['status_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-zinc-500">Belum ada booking.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- 7. Growth stats --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Statistik pertumbuhan">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-zinc-500">Mitra baru bulan ini</p>
            <p class="mt-1.5 text-xl font-bold text-zinc-900">{{ number_format($analytics['growth']['new_vendors'], 0, ',', '.') }}</p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-zinc-500">Tingkat keberhasilan pembayaran</p>
            <p class="mt-1.5 text-xl font-bold text-zinc-900">
                {{ $analytics['growth']['payment_success_rate'] === null ? '-' : number_format($analytics['growth']['payment_success_rate'], 1, ',', '.') . '%' }}
            </p>
            <p class="mt-1 text-[0.7rem] text-zinc-400">Transaksi yang sudah final bulan ini</p>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs">
            <p class="text-xs font-medium text-zinc-500">Rata-rata booking per mitra aktif</p>
            <p class="mt-1.5 text-xl font-bold text-zinc-900">{{ number_format($analytics['growth']['avg_booking_per_vendor'], 1, ',', '.') }}</p>
            <p class="mt-1 text-[0.7rem] text-zinc-400">Booking bulan ini dibagi mitra aktif</p>
        </article>
    </section>
</div>
