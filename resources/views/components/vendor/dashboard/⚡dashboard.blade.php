<?php

use App\Actions\Admin\Dashboard\GetOwnerDashboardAnalyticsAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    #[Url(as: 'periode', except: 'this_month')]
    public string $datePreset = 'this_month';

    #[Url(as: 'dari', except: '')]
    public string $customStartDate = '';

    #[Url(as: 'sampai', except: '')]
    public string $customEndDate = '';

    #[Url(as: 'tren', except: 'weekly')]
    public string $trendGranularity = 'weekly';

    #[Url(as: 'cari', except: '')]
    public string $search = '';

    #[Url(as: 'pembayaran', except: 'all')]
    public string $paymentFilter = 'all';

    #[Url(as: 'pesanan', except: 'all')]
    public string $bookingStatusFilter = 'all';

    public string $draftStartDate = '';

    public string $draftEndDate = '';

    public function mount(): void
    {
        $today = now();
        $this->draftStartDate = $this->customStartDate !== ''
            ? $this->customStartDate
            : $today->copy()->startOfMonth()->toDateString();
        $this->draftEndDate = $this->customEndDate !== ''
            ? $this->customEndDate
            : $today->copy()->endOfMonth()->toDateString();
    }

    public function selectDatePreset(string $preset): void
    {
        $allowedPresets = ['this_month', 'last_month', 'last_7_days', 'last_30_days', 'this_year'];
        if (! in_array($preset, $allowedPresets, true)) {
            return;
        }

        $this->datePreset = $preset;
        $this->customStartDate = '';
        $this->customEndDate = '';

        if ($preset === 'last_7_days') {
            $this->trendGranularity = 'daily';
        } elseif ($preset === 'this_year') {
            $this->trendGranularity = 'monthly';
        } elseif (in_array($preset, ['this_month', 'last_month', 'last_30_days'], true)) {
            $this->trendGranularity = 'weekly';
        }

        $this->resetPage();
    }

    public function applyCustomDateRange(): void
    {
        $this->validate([
            'draftStartDate' => ['required', 'date'],
            'draftEndDate' => ['required', 'date', 'after_or_equal:draftStartDate'],
        ], [
            'draftStartDate.required' => 'Tanggal mulai harus diisi.',
            'draftEndDate.required' => 'Tanggal akhir harus diisi.',
            'draftEndDate.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ]);

        $this->customStartDate = $this->draftStartDate;
        $this->customEndDate = $this->draftEndDate;
        $this->datePreset = 'custom';
        $this->resetPage();
        $this->dispatch('date-range-applied');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBookingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetTransactionFilters(): void
    {
        $this->search = '';
        $this->paymentFilter = 'all';
        $this->bookingStatusFilter = 'all';
        $this->resetPage();
    }

    public function exportReport(GetOwnerDashboardAnalyticsAction $action): StreamedResponse
    {
        $vendor = app('vendor.context')->get();
        $vendorId = (int) ($vendor?->id ?? 0);

        $dateRange = $action->resolveDateRange(
            $this->datePreset,
            $this->customStartDate ?: null,
            $this->customEndDate ?: null,
        );

        $rows = $vendorId > 0
            ? $action->getExportTransactions(
                $vendorId,
                $dateRange,
                $this->search,
                $this->paymentFilter,
                $this->bookingStatusFilter,
            )
            : collect();

        $slug = $vendor?->slug ?? 'mitra';
        $fileName = sprintf(
            'laporan-transaksi-%s-%s-%s.csv',
            $slug,
            $dateRange['start']->format('Ymd'),
            $dateRange['end']->format('Ymd'),
        );

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No. Transaksi',
                'Tanggal',
                'Detail Jadwal',
                'Kategori',
                'Layanan',
                'Pelanggan',
                'Total Bayar (Rp)',
                'Status Pembayaran',
                'Status Pesanan',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['transaction_code'],
                    $row['date_formatted'],
                    $row['schedule_detail'],
                    $row['category_label'],
                    $row['service_name'],
                    $row['customer_name'],
                    $row['amount'],
                    $row['payment_label'],
                    $row['booking_status_label'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(GetOwnerDashboardAnalyticsAction $action): array
    {
        $user = Auth::guard('vendor')->user();
        $vendor = app('vendor.context')->get() ?? $user?->vendor;
        $vendorId = (int) ($vendor?->id ?? 0);

        $dateRange = $action->resolveDateRange(
            $this->datePreset,
            $this->customStartDate ?: null,
            $this->customEndDate ?: null,
        );

        $analytics = $action->getOverviewAnalytics(
            $vendorId,
            $dateRange,
            $this->trendGranularity,
        );

        $transactions = $action->getRecentTransactions(
            $vendorId,
            $dateRange,
            $this->search,
            $this->paymentFilter,
            $this->bookingStatusFilter,
            5,
        );

        return [
            'ownerName' => $user?->name ?? 'Owner',
            'vendor' => $vendor,
            'operationalInfo' => $action->getVendorOperationalInfo($vendor),
            'dateRange' => $dateRange,
            'analytics' => $analytics,
            'transactions' => $transactions,
        ];
    }
};
?>

<div class="p-6">
    <div class="space-y-6">

        {{-- A. Header Dashboard --}}
        <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    Dashboard Owner
                </h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Pantau arus kas masuk, tren pesanan, dan efisiensi layanan dalam satu klik.
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 font-medium text-zinc-700 border border-zinc-200">
                        <span @class([
                            'size-2 rounded-full',
                            'bg-emerald-500' => $operationalInfo['is_open_today'],
                            'bg-amber-500' => ! $operationalInfo['is_open_today'],
                        ])></span>
                        {{ $vendor?->name ?? 'Mitra PawPaw' }}
                    </span>
                    <span aria-hidden="true">•</span>
                    <span>{{ $operationalInfo['status_text'] }} ({{ $operationalInfo['schedule_text'] }})</span>
                </div>
            </div>

            {{-- Date Range Filter Popover --}}
            <div x-data="{ openDateMenu: false }" x-on:date-range-applied.window="openDateMenu = false" class="relative shrink-0 self-start">
                <button
                    type="button"
                    @click="openDateMenu = !openDateMenu"
                    :aria-expanded="openDateMenu.toString()"
                    aria-haspopup="dialog"
                    class="inline-flex items-center gap-2.5 rounded-full border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 shadow-xs transition hover:border-[#1B5E43]/40 hover:bg-zinc-50 cursor-pointer"
                >
                    <svg class="size-4 text-[#1B5E43]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span>{{ $dateRange['range_label'] }}</span>
                    <svg class="size-4 text-zinc-400 transition-transform" :class="openDateMenu ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div
                    x-cloak
                    x-show="openDateMenu"
                    @click.outside="openDateMenu = false"
                    @keydown.escape.window="openDateMenu = false"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    class="absolute right-0 z-30 mt-2 w-80 max-w-[calc(100vw-2.5rem)] rounded-2xl border border-zinc-200 bg-white p-4 shadow-lg"
                >
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
                        Pilih Rentang Periode
                    </p>

                    @php
                        $presetOptions = [
                            'this_month' => 'Bulan ini',
                            'last_month' => 'Bulan lalu',
                            'last_7_days' => '7 hari terakhir',
                            'last_30_days' => '30 hari terakhir',
                            'this_year' => 'Tahun ini',
                        ];
                    @endphp

                    <div class="grid grid-cols-2 gap-1.5">
                        @foreach ($presetOptions as $presetKey => $presetLabel)
                            <button
                                type="button"
                                wire:click="selectDatePreset('{{ $presetKey }}')"
                                @click="openDateMenu = false"
                                @class([
                                    'rounded-xl px-3 py-2 text-left text-xs font-semibold transition cursor-pointer',
                                    'bg-[#1B5E43] text-white' => $dateRange['preset'] === $presetKey,
                                    'bg-zinc-50 text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' => $dateRange['preset'] !== $presetKey,
                                ])
                            >
                                {{ $presetLabel }}
                            </button>
                        @endforeach
                    </div>

                    <div class="my-3 border-t border-zinc-100"></div>

                    <form wire:submit="applyCustomDateRange" class="space-y-2.5">
                        <p class="text-xs font-semibold text-zinc-900">Rentang tanggal kustom</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="dashboard-start-date" class="block text-[11px] font-medium text-zinc-500 mb-1">Dari</label>
                                <input
                                    id="dashboard-start-date"
                                    type="date"
                                    wire:model="draftStartDate"
                                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 text-xs text-zinc-900 focus:border-[#1B5E43] focus:outline-none"
                                />
                            </div>
                            <div>
                                <label for="dashboard-end-date" class="block text-[11px] font-medium text-zinc-500 mb-1">Sampai</label>
                                <input
                                    id="dashboard-end-date"
                                    type="date"
                                    wire:model="draftEndDate"
                                    class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 text-xs text-zinc-900 focus:border-[#1B5E43] focus:outline-none"
                                />
                            </div>
                        </div>

                        @error('draftStartDate')
                            <p class="text-[11px] text-red-600">{{ $message }}</p>
                        @enderror
                        @error('draftEndDate')
                            <p class="text-[11px] text-red-600">{{ $message }}</p>
                        @enderror

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-[#1B5E43] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#164C36] cursor-pointer"
                        >
                            Terapkan Tanggal
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- B. Summary Cards (4 Kartu Statistik) --}}
        @php
            $summary = $analytics['summary'];
        @endphp
        <section aria-label="Ringkasan performa bisnis" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Card 1: Total Pendapatan (Primary Green Highlight) --}}
            <article class="rounded-xl bg-[#1B5E43] p-6 text-white shadow-xs">
                <p class="text-sm font-medium text-white/85">Total pendapatan</p>
                <p class="mt-3 text-2xl font-bold tabular-nums">
                    {{ $summary['total_revenue']['formatted'] }}
                </p>
            </article>

            {{-- Card 2: Pesanan Selesai --}}
            <article class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs">
                <p class="text-sm font-medium text-zinc-500">Pesanan selesai</p>
                <p class="mt-3 text-2xl font-bold text-zinc-900 tabular-nums">
                    {{ $summary['completed_orders']['formatted'] }}
                </p>
            </article>

            {{-- Card 3: Rata-rata Transaksi --}}
            <article class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs">
                <p class="text-sm font-medium text-zinc-500">Rata-rata transaksi</p>
                <p class="mt-3 text-2xl font-bold text-zinc-900 tabular-nums">
                    {{ $summary['average_transaction']['formatted'] }}
                </p>
            </article>

            {{-- Card 4: Tingkat Pembatalan --}}
            <article class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs">
                <p class="text-sm font-medium text-zinc-500">Tingkat pembatalan</p>
                <p class="mt-3 text-2xl font-bold text-zinc-900 tabular-nums">
                    {{ $summary['cancellation_rate']['formatted'] }}
                </p>
            </article>
        </section>

        {{-- C, D, E. Charts & Popular Services Section --}}
        <section class="grid grid-cols-1 gap-5 lg:grid-cols-12 items-stretch">

            {{-- C. Grafik Tren Pendapatan & Pesanan (Mixed Chart) --}}
            <div
                x-data="ownerTrendChart"
                data-payload="{{ json_encode($analytics['trend']) }}"
                x-init="initChart($el.dataset.payload)"
                class="lg:col-span-7 xl:col-span-8 flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-6 shadow-xs"
            >
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900">
                            Tren pendapatan &amp; pesanan
                        </h2>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{ $analytics['trend']['subtitle'] }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="trend-granularity-select" class="sr-only">Periode grafik tren</label>
                        <select
                            id="trend-granularity-select"
                            wire:model.live="trendGranularity"
                            class="rounded-full border border-zinc-200 bg-zinc-50 px-3.5 py-1.5 text-xs font-semibold text-zinc-900 focus:border-[#1B5E43] focus:outline-none cursor-pointer"
                        >
                            <option value="daily">Harian</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                    </div>
                </div>

                @if (! $analytics['trend']['has_data'])
                    <div class="mb-2 rounded-2xl bg-zinc-50 px-3.5 py-2 text-xs text-zinc-500 border border-zinc-100">
                        Belum ada pesanan berstatus selesai pada rentang waktu ini. Grafik akan terisi otomatis saat transaksi selesai.
                    </div>
                @endif

                <div wire:ignore class="w-full flex-1 flex items-center">
                    <div x-ref="canvas" class="w-full min-h-[320px]"></div>
                </div>
            </div>

            {{-- Right Column: D. Kontribusi Layanan & E. Layanan Terpopuler --}}
            <div class="lg:col-span-5 xl:col-span-4 flex flex-col gap-5">

                {{-- D. Grafik Kontribusi Layanan (Donut Chart) --}}
                <div
                    x-data="ownerContributionChart"
                    data-payload="{{ json_encode($analytics['contribution']) }}"
                    x-init="initChart($el.dataset.payload)"
                    class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs"
                >
                    <h2 class="text-base font-semibold text-zinc-900">
                        Kontribusi layanan
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        Dari total pendapatan periode terpilih ({{ $analytics['contribution']['total_formatted'] }})
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-12 items-center">
                        <div wire:ignore class="sm:col-span-5 flex items-center justify-center">
                            <div x-ref="canvas" class="w-full max-w-[185px] min-h-[195px]"></div>
                        </div>

                        <ul class="sm:col-span-7 space-y-3" aria-label="Rincian kontribusi kategori layanan">
                            @foreach ($analytics['contribution']['items'] as $catItem)
                                <li class="flex items-start justify-between gap-2">
                                    <div class="flex items-start gap-2 min-w-0">
                                        <span
                                            class="mt-1.5 size-2.5 shrink-0 rounded-xs"
                                            style="background-color: {{ $catItem['color'] }}"
                                            aria-hidden="true"
                                        ></span>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-zinc-900 truncate">
                                                {{ $catItem['label'] }}
                                            </p>
                                            <p class="text-[11px] text-zinc-500 tabular-nums">
                                                {{ $catItem['formatted'] }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold text-zinc-900 tabular-nums shrink-0">
                                        {{ $catItem['percentage_label'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <p class="mt-4 border-t border-zinc-100 pt-3 text-xs text-zinc-500">
                        {{ $analytics['contribution']['top_contributor_note'] }}
                    </p>
                </div>

                {{-- E. Layanan Terpopuler --}}
                <div class="flex-1 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900">
                            Layanan terpopuler
                        </h2>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            Berdasarkan pesanan selesai periode terpilih
                        </p>

                        @if (count($analytics['popular_services']) === 0)
                            <div class="mt-6 rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 p-5 text-center">
                                <p class="text-xs font-medium text-zinc-500">
                                    Belum ada layanan yang diselesaikan pada periode ini.
                                </p>
                            </div>
                        @else
                            <ol class="mt-4 divide-y divide-zinc-100">
                                @foreach ($analytics['popular_services'] as $service)
                                    <li class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-5 shrink-0 text-xs font-bold text-zinc-400 tabular-nums">
                                                {{ $service['rank'] }}
                                            </span>
                                            <div class="min-w-0">
                                                <p @class([
                                                    'truncate text-xs sm:text-sm text-zinc-900',
                                                    'font-bold' => $loop->first,
                                                    'font-medium' => ! $loop->first,
                                                ])>
                                                    {{ $service['name'] }}
                                                </p>
                                                <p class="text-[11px] text-zinc-400">
                                                    {{ $service['category_label'] }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="shrink-0 text-xs font-semibold text-zinc-500 tabular-nums">
                                            {{ number_format($service['completed_orders'], 0, ',', '.') }} pesanan
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>

            </div>
        </section>

        {{-- F. Tabel Transaksi Terbaru --}}
        <section aria-labelledby="recent-transactions-heading" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="recent-transactions-heading" class="text-base font-semibold text-zinc-900">
                        Transaksi terkini
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        Ringkasan pembayaran dan penyelesaian pesanan dari seluruh kategori layanan
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="exportReport"
                    wire:loading.attr="disabled"
                    wire:target="exportReport"
                    class="inline-flex items-center justify-center gap-2 self-start sm:self-auto rounded-full bg-[#1B5E43] px-5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-xs transition hover:bg-[#154A35] disabled:opacity-60 cursor-pointer"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span wire:loading.remove wire:target="exportReport">Unduh laporan</span>
                    <span wire:loading wire:target="exportReport">Menyiapkan CSV...</span>
                </button>
            </div>

            {{-- Search, Filters & Lihat Semua --}}
            <div class="mt-5 flex flex-wrap items-center gap-3">
                {{-- Search Input --}}
                <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
                    <label for="dashboard-tx-search" class="sr-only">Cari nomor transaksi atau nama pelanggan</label>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        id="dashboard-tx-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nomor / pelanggan..."
                        class="w-full rounded-full border border-zinc-200 bg-zinc-50 py-2 pl-9 pr-4 text-xs sm:text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-[#1B5E43] focus:bg-white focus:outline-none"
                    />
                </div>

                {{-- Filter Status Pembayaran --}}
                <div>
                    <label for="dashboard-payment-filter" class="sr-only">Filter status pembayaran</label>
                    <select
                        id="dashboard-payment-filter"
                        wire:model.live="paymentFilter"
                        class="rounded-full border border-zinc-200 bg-zinc-50 px-4 py-2 text-xs sm:text-sm font-medium text-zinc-900 focus:border-[#1B5E43] focus:bg-white focus:outline-none cursor-pointer"
                    >
                        <option value="all">Pembayaran: Semua</option>
                        <option value="paid">Pembayaran: Lunas</option>
                        <option value="unpaid">Pembayaran: Belum lunas</option>
                        <option value="refunded">Pembayaran: Dikembalikan</option>
                    </select>
                </div>

                {{-- Filter Status Pesanan --}}
                <div>
                    <label for="dashboard-booking-filter" class="sr-only">Filter status pesanan</label>
                    <select
                        id="dashboard-booking-filter"
                        wire:model.live="bookingStatusFilter"
                        class="rounded-full border border-zinc-200 bg-zinc-50 px-4 py-2 text-xs sm:text-sm font-medium text-zinc-900 focus:border-[#1B5E43] focus:bg-white focus:outline-none cursor-pointer"
                    >
                        <option value="all">Pesanan: Semua</option>
                        <option value="completed">Pesanan: Selesai</option>
                        <option value="processing">Pesanan: Diproses</option>
                        <option value="cancelled">Pesanan: Dibatalkan</option>
                    </select>
                </div>

                @if ($search !== '' || $paymentFilter !== 'all' || $bookingStatusFilter !== 'all')
                    <button
                        type="button"
                        wire:click="resetTransactionFilters"
                        class="text-xs font-semibold text-zinc-500 underline decoration-zinc-300 underline-offset-4 hover:text-zinc-900 cursor-pointer"
                    >
                        Reset filter
                    </button>
                @endif

                {{-- Lihat Semua (Navigates to existing booking pages) --}}
                @php
                    $subdomainParams = $vendor?->slug ? ['subdomain' => $vendor->slug] : [];
                @endphp
                <div class="ms-auto">
                    <flux:dropdown position="bottom" align="end">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-[#1B5E43] hover:text-[#134530] transition cursor-pointer"
                        >
                            <span>Lihat semua</span>
                            <span aria-hidden="true">→</span>
                        </button>

                        <flux:menu>
                            <flux:menu.item icon="clipboard-document-list" href="{{ route('vendor.admin.booking-clinic', $subdomainParams) }}">
                                Booking Klinik Hewan
                            </flux:menu.item>
                            <flux:menu.item icon="home-modern" href="{{ route('vendor.admin.booking-pet-hotel', $subdomainParams) }}">
                                Booking Pet Hotel
                            </flux:menu.item>
                            <flux:menu.item icon="sparkles" href="{{ route('vendor.admin.booking-grooming', $subdomainParams) }}">
                                Booking Grooming
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>

            {{-- Table Container --}}
            <div tabindex="0" role="region" aria-labelledby="recent-transactions-heading" class="owner-transactions-region mt-5">
                @if ($transactions->isEmpty())
                    <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-zinc-900">
                            Tidak ada transaksi ditemukan
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            Belum ada data reservasi yang cocok dengan periode atau filter pencarian Anda.
                        </p>
                    </div>
                @else
                    <table class="owner-transactions-table text-left text-xs sm:text-sm">
                        <caption class="sr-only">Daftar transaksi dan reservasi terbaru mitra</caption>
                        <thead>
                            <tr class="bg-zinc-50 text-xs font-semibold text-zinc-500">
                                <th scope="col" class="rounded-l-xl py-3 pl-4 pr-3">No. transaksi</th>
                                <th scope="col" class="px-3 py-3">Tanggal</th>
                                <th scope="col" class="px-3 py-3">Layanan</th>
                                <th scope="col" class="px-3 py-3">Pelanggan</th>
                                <th scope="col" class="px-3 py-3">Total bayar</th>
                                <th scope="col" class="px-3 py-3">Pembayaran</th>
                                <th scope="col" class="rounded-r-xl py-3 pl-3 pr-4">Pesanan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($transactions as $tx)
                                <tr wire:key="tx-{{ $tx['service_type'] }}-{{ $tx['booking_id'] }}" class="transition-colors hover:bg-zinc-50">
                                    <th scope="row" class="py-3.5 pl-4 pr-3 font-bold text-[#1B5E43] whitespace-nowrap">
                                        <a href="{{ route($tx['booking_route'], $subdomainParams) }}" class="hover:underline">
                                            {{ $tx['transaction_code'] }}
                                        </a>
                                    </th>
                                    <td class="px-3 py-3.5 text-zinc-600 whitespace-nowrap">
                                        <div>
                                            <p class="font-medium text-zinc-700">{{ $tx['date_formatted'] }}</p>
                                            @if ($tx['schedule_detail'] !== '')
                                                <p class="text-[11px] text-zinc-400">{{ $tx['schedule_detail'] }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5 text-zinc-700">
                                        <div>
                                            <p class="font-medium text-zinc-900">{{ $tx['service_name'] }}</p>
                                            <p class="text-[11px] text-zinc-400">{{ $tx['category_label'] }}</p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5 font-medium text-zinc-700">
                                        {{ $tx['customer_name'] }}
                                    </td>
                                    <td class="px-3 py-3.5 font-bold text-zinc-900 tabular-nums whitespace-nowrap">
                                        {{ $tx['amount_formatted'] }}
                                    </td>
                                    <td class="px-3 py-3.5 whitespace-nowrap">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                                            'bg-[#E5F2EA] text-[#1B5E43]' => $tx['payment_key'] === 'paid',
                                            'bg-[#FBF0E4] text-[#9A5B25]' => $tx['payment_key'] === 'unpaid',
                                            'bg-[#F3EEE6] text-[#786B59]' => $tx['payment_key'] === 'refunded',
                                        ])>
                                            {{ $tx['payment_label'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 pl-3 pr-4 whitespace-nowrap">
                                        <span
                                            title="{{ $tx['raw_status_note'] }}"
                                            @class([
                                                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                                                'bg-[#E5F2EA] text-[#1B5E43]' => $tx['booking_status_key'] === 'completed',
                                                'bg-[#FBF0E4] text-[#9A5B25]' => $tx['booking_status_key'] === 'processing',
                                                'bg-[#FDECE8] text-[#B43E28]' => $tx['booking_status_key'] === 'cancelled',
                                            ])
                                        >
                                            {{ $tx['booking_status_label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Pagination Footer --}}
            <div class="mt-5 flex flex-col gap-3 border-t border-zinc-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-zinc-500">
                    @if ($transactions->total() > 0)
                        Menampilkan {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} dari {{ number_format($transactions->total(), 0, ',', '.') }} pesanan
                    @else
                        Menampilkan 0 pesanan
                    @endif
                </p>

                @if ($transactions->hasPages())
                    <nav role="navigation" aria-label="Navigasi halaman transaksi" class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="previousPage"
                            @disabled($transactions->onFirstPage())
                            aria-label="Halaman sebelumnya"
                            class="inline-flex size-8 items-center justify-center rounded-lg border border-zinc-200 bg-white text-xs font-semibold text-zinc-600 transition hover:bg-zinc-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                        >
                            ‹
                        </button>

                        @php
                            $currentPage = $transactions->currentPage();
                            $lastPage = $transactions->lastPage();
                            $windowStart = max(1, $currentPage - 2);
                            $windowEnd = min($lastPage, $windowStart + 4);
                            $windowStart = max(1, $windowEnd - 4);
                        @endphp

                        @for ($page = $windowStart; $page <= $windowEnd; $page++)
                            <button
                                type="button"
                                wire:click="gotoPage({{ $page }})"
                                @if ($page === $currentPage) aria-current="page" @endif
                                @class([
                                    'inline-flex size-8 items-center justify-center rounded-lg text-xs font-bold transition cursor-pointer tabular-nums',
                                    'bg-[#1B5E43] text-white shadow-xs' => $page === $currentPage,
                                    'text-zinc-600 hover:bg-zinc-50' => $page !== $currentPage,
                                ])
                            >
                                {{ $page }}
                            </button>
                        @endfor

                        <button
                            type="button"
                            wire:click="nextPage"
                            @disabled(! $transactions->hasMorePages())
                            aria-label="Halaman berikutnya"
                            class="inline-flex size-8 items-center justify-center rounded-lg border border-zinc-200 bg-white text-xs font-semibold text-zinc-600 transition hover:bg-zinc-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                        >
                            ›
                        </button>
                    </nav>
                @endif
            </div>
        </section>

    </div>
</div>
