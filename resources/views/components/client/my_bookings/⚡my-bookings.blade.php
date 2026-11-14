<?php

use App\Actions\Client\Booking\GetActiveBookingsAction;
use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.client')] class extends Component {
    private const PAGE_SIZE = 10;

    public string $tab = 'active';
    public string $search = '';
    public string $status = '';
    public string $type = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sort = 'newest';
    public int $limit = self::PAGE_SIZE;

    public string $cancelType = '';
    public int $cancelId = 0;

    public ?array $selectedBooking = null;

    private function userClientId(): ?int
    {
        return auth('client')->user()?->userClient?->id;
    }

    #[Computed]
    public function result(): array
    {
        $id = $this->userClientId();

        if (!$id) {
            return ['items' => collect(), 'has_more' => false];
        }

        return app(GetActiveBookingsAction::class)->execute(
            $id,
            $this->tab === 'history',
            [
                'status' => $this->status,
                'type' => $this->type,
                'search' => $this->search,
                'from' => $this->dateFrom,
                'to' => $this->dateTo,
                'sort' => $this->sort,
            ],
            $this->limit,
        );
    }

    /** Jumlah filter "lanjutan" yang aktif (di dalam panel Filter lainnya). */
    #[Computed]
    public function advancedCount(): int
    {
        return collect([$this->type !== '', $this->dateFrom !== '', $this->dateTo !== '', $this->sort !== 'newest'])
            ->filter()
            ->count();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->status !== '' || $this->advancedCount > 0;
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'status', 'type', 'dateFrom', 'dateTo', 'sort', 'tab'], true)) {
            $this->limit = self::PAGE_SIZE;
        }
    }

    public function updatedTab(): void
    {
        $this->status = '';
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'type', 'dateFrom', 'dateTo', 'sort');
        $this->limit = self::PAGE_SIZE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    public function askCancel(string $type, int $id): void
    {
        $this->cancelType = $type;
        $this->cancelId = $id;
        $this->modal('cancel-booking')->show();
    }

    public function showDetail(string $type, int $id): void
    {
        $booking = $this->result['items']->first(fn($b) => $b['type'] === $type && $b['id'] === $id);

        if ($booking) {
            $this->selectedBooking = $booking;
            $this->modal('detail-booking')->show();
        }
    }

    public function cancel(): void
    {
        $model = match ($this->cancelType) {
            'clinic' => BookingClinic::class,
            'grooming' => BookingGrooming::class,
            'pet_hotel' => BookingPetHotel::class,
            default => null,
        };

        // Hanya booking milik sendiri dan masih "pending" yang boleh dibatalkan
        $booking = $model && $this->userClientId() ? $model::query()->where('user_client_id', $this->userClientId())->where('status', 'pending')->find($this->cancelId) : null;

        $this->modal('cancel-booking')->close();

        if (!$booking) {
            Flux::toast('Reservasi ini tidak bisa dibatalkan lagi.', variant: 'danger');
            return;
        }

        $booking->update(['status' => 'cancelled']);
        unset($this->result);

        Flux::toast('Reservasi dibatalkan.', variant: 'success');
    }
};
?>

@php
    $statusMap = [
        'pending' => [
            'label' => 'Menunggu konfirmasi',
            'color' => 'amber',
            'icon' => 'clock',
            'hint' => 'Vendor akan segera mengonfirmasi.',
        ],
        'confirmed' => [
            'label' => 'Dikonfirmasi',
            'color' => 'green',
            'icon' => 'check-circle',
            'hint' => 'Datang 10 menit lebih awal.',
        ],
        'completed' => ['label' => 'Selesai', 'color' => 'zinc', 'icon' => 'check', 'hint' => null],
        'cancelled' => ['label' => 'Dibatalkan', 'color' => 'red', 'icon' => 'x-circle', 'hint' => null],
        'rejected' => ['label' => 'Ditolak vendor', 'color' => 'red', 'icon' => 'x-circle', 'hint' => null],
        'expired' => [
            'label' => 'Kedaluwarsa',
            'color' => 'zinc',
            'icon' => 'clock',
            'hint' => 'Vendor belum sempat mengonfirmasi. Silakan buat reservasi baru.',
        ],
    ];

    $statusOptions =
        $tab === 'active'
            ? ['' => 'Semua', 'pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi']
            : [
                '' => 'Semua',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan',
                'rejected' => 'Ditolak',
                'expired' => 'Kedaluwarsa',
            ];

    $selectClass =
        'min-h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent';
    $items = $this->result['items'];
@endphp

{{-- Polling hanya di tab aktif agar status pending berubah tanpa refresh --}}
<div class="mx-auto max-w-3xl px-4 py-6 sm:py-10" @if ($tab === 'active') wire:poll.30s @endif>
    <div class="mb-6">
        <flux:heading size="xl">Reservasi saya</flux:heading>
        <flux:subheading>Pantau jadwal dan status reservasi Anda</flux:subheading>
    </div>

    {{-- Tab --}}
    <div role="tablist" aria-label="Jenis reservasi" class="mb-5 flex gap-6 border-b border-zinc-200">
        @foreach (['active' => 'Aktif', 'history' => 'Riwayat'] as $key => $label)
            @php($isCurrent = $tab === $key)
            <button type="button" role="tab" aria-selected="{{ $isCurrent ? 'true' : 'false' }}"
                wire:click="$set('tab', '{{ $key }}')"
                class="-mb-px min-h-11 border-b-2 px-1 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2
                {{ $isCurrent ? 'border-accent text-accent' : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="mb-6 space-y-3">
        <flux:input wire:model.live.debounce.400ms="search" type="search" icon="magnifying-glass"
            placeholder="Cari nama vendor atau layanan" />

        {{-- Chip status --}}
        <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1" role="group" aria-label="Filter status">
            @foreach ($statusOptions as $value => $label)
                @php($on = $status === (string) $value)
                <button type="button" wire:click="$set('status', '{{ $value }}')"
                    aria-pressed="{{ $on ? 'true' : 'false' }}"
                    class="min-h-10 shrink-0 rounded-full border px-4 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
                    {{ $on ? 'border-accent bg-accent text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Filter lanjutan --}}
        <details class="rounded-lg border border-zinc-200 bg-white" @if ($this->advancedCount > 0) open @endif>
            <summary
                class="flex min-h-11 cursor-pointer items-center justify-between px-4 text-sm font-medium text-zinc-800">
                <span>Filter lainnya</span>
                @if ($this->advancedCount > 0)
                    <flux:badge size="sm" color="green">{{ $this->advancedCount }} aktif</flux:badge>
                @endif
            </summary>

            <div class="grid gap-4 border-t border-zinc-100 p-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label for="f-type" class="text-sm font-medium text-zinc-700">Jenis layanan</label>
                    <select id="f-type" wire:model.live="type" class="{{ $selectClass }}">
                        <option value="">Semua layanan</option>
                        <option value="clinic">Klinik</option>
                        <option value="grooming">Grooming</option>
                        <option value="pet_hotel">Pet hotel</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label for="f-sort" class="text-sm font-medium text-zinc-700">Urutkan</label>
                    <select id="f-sort" wire:model.live="sort" class="{{ $selectClass }}">
                        <option value="newest">Terbaru dipesan</option>
                        <option value="soonest">Jadwal terdekat</option>
                        <option value="latest">Jadwal terjauh</option>
                    </select>
                </div>

                <flux:input wire:model.live="dateFrom" type="date" label="Jadwal dari" max="{{ $dateTo ?: '' }}" />
                <flux:input wire:model.live="dateTo" type="date" label="Jadwal sampai"
                    min="{{ $dateFrom ?: '' }}" />
            </div>
        </details>

        @if ($this->hasFilters)
            <div class="flex items-center justify-between text-sm">
                <span class="text-zinc-600"
                    aria-live="polite">{{ $items->count() }}{{ $this->result['has_more'] ? '+' : '' }} reservasi
                    ditemukan</span>
                <button type="button" wire:click="resetFilters"
                    class="font-medium text-accent hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    Reset filter
                </button>
            </div>
        @endif
    </div>

    {{-- Daftar --}}
    <div wire:loading.class="opacity-60"
        wire:target="search,status,type,dateFrom,dateTo,sort,tab,resetFilters,loadMore">
        @if ($items->isEmpty())
            <div class="flex flex-col items-center py-14 text-center">
                <flux:icon name="{{ $this->hasFilters ? 'magnifying-glass' : 'calendar-days' }}"
                    class="size-14 text-zinc-300" />

                @if ($this->hasFilters)
                    <p class="mt-4 text-lg font-medium text-zinc-900">Tidak ada reservasi yang cocok</p>
                    <p class="mt-1 max-w-xs text-sm text-zinc-600">Coba ubah kata kunci atau longgarkan filter.</p>
                    <flux:button wire:click="resetFilters" variant="outline" class="mt-6">Reset filter</flux:button>
                @elseif ($tab === 'active')
                    <p class="mt-4 text-lg font-medium text-zinc-900">Belum ada reservasi aktif</p>
                    <p class="mt-1 max-w-xs text-sm text-zinc-600">
                        Reservasi klinik, grooming, atau pet hotel Anda akan muncul di sini.
                    </p>
                    <flux:button href="{{ route('client.clinic') }}" wire:navigate variant="primary" class="mt-6">
                        Cari layanan
                    </flux:button>
                @else
                    <p class="mt-4 text-lg font-medium text-zinc-900">Belum ada riwayat</p>
                    <p class="mt-1 max-w-xs text-sm text-zinc-600">
                        Reservasi yang selesai, dibatalkan, atau kedaluwarsa akan tampil di sini.
                    </p>
                @endif
            </div>
        @else
            <div class="space-y-3">
                @foreach ($items as $booking)
                    @php($st = $statusMap[$booking['status']] ?? ['label' => ucfirst($booking['status']), 'color' => 'zinc', 'icon' => 'information-circle', 'hint' => null])

                    <flux:card wire:key="{{ $booking['type'] }}-{{ $booking['id'] }}" class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <flux:badge :color="$st['color']" :icon="$st['icon']" size="sm">
                                {{ $st['label'] }}</flux:badge>
                            <span class="text-xs text-zinc-600">
                                {{ $booking['code'] }}
                                @if ($booking['created_human'])
                                    &middot; Dipesan {{ $booking['created_human'] }}
                                @endif
                            </span>
                        </div>

                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-semibold text-zinc-900">
                                {{ $booking['service_item_name'] }}</h3>
                            <p class="text-sm text-zinc-600">
                                {{ $booking['category_label'] }} &middot;
                                @if ($booking['vendor_slug'])
                                    <a href="{{ route('client.vendor.detail', $booking['vendor_slug']) }}"
                                        wire:navigate class="hover:underline">{{ $booking['vendor_name'] }}</a>
                                @else
                                    {{ $booking['vendor_name'] }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-3 rounded-lg bg-zinc-50 px-4 py-3">
                            <flux:icon name="calendar-days" class="size-5 shrink-0 text-accent" />
                            <div class="min-w-0">
                                <p class="font-medium text-zinc-900">{{ $booking['date_label'] }}</p>
                                <p class="text-sm text-zinc-600">
                                    {{ $booking['time_label'] ? 'Pukul ' . $booking['time_label'] : $booking['nights'] . ' malam' }}
                                </p>
                            </div>
                        </div>

                        @if ($booking['total_price'])
                            <div class="flex items-baseline justify-between gap-4">
                                <span class="text-sm text-zinc-600">
                                    @if (($booking['nights'] ?? 0) > 1)
                                        Rp {{ number_format($booking['unit_price'], 0, ',', '.') }} &times;
                                        {{ $booking['nights'] }} malam
                                    @else
                                        Total
                                    @endif
                                </span>
                                <span class="text-lg font-bold text-zinc-900">Rp
                                    {{ number_format($booking['total_price'], 0, ',', '.') }}</span>
                            </div>
                        @endif

                        @if ($st['hint'])
                            <p class="text-sm text-zinc-600">{{ $st['hint'] }}</p>
                        @endif

                        <div class="flex flex-wrap gap-2 border-t border-zinc-100 pt-4">
                            <flux:button size="sm" variant="outline" icon="document-text"
                                wire:click="showDetail('{{ $booking['type'] }}', {{ $booking['id'] }})">
                                Detail reservasi
                            </flux:button>

                            @if ($tab === 'active')
                                @if ($booking['whatsapp_url'])
                                    <flux:button size="sm" variant="outline" icon="chat-bubble-left-ellipsis"
                                        href="{{ $booking['whatsapp_url'] }}" target="_blank" rel="noopener">Hubungi
                                        vendor</flux:button>
                                @endif
                                @if ($booking['maps_url'])
                                    <flux:button size="sm" variant="outline" icon="map-pin"
                                        href="{{ $booking['maps_url'] }}" target="_blank" rel="noopener">Lihat rute
                                    </flux:button>
                                @endif
                                @if ($booking['status'] === 'pending')
                                    <flux:button size="sm" variant="subtle"
                                        class="ml-auto text-red-600 hover:text-red-700"
                                        wire:click="askCancel('{{ $booking['type'] }}', {{ $booking['id'] }})">
                                        Batalkan</flux:button>
                                @endif
                            @elseif ($booking['vendor_slug'])
                                {{-- Riwayat: ajak pengguna memesan lagi, terutama yang kedaluwarsa/dibatalkan --}}
                                <flux:button size="sm"
                                    variant="{{ in_array($booking['status'], ['expired', 'cancelled', 'rejected']) ? 'primary' : 'outline' }}"
                                    href="{{ route('client.vendor.detail', $booking['vendor_slug']) }}" wire:navigate>
                                    Pesan lagi
                                </flux:button>
                            @endif
                        </div>
                    </flux:card>
                @endforeach
            </div>

            @if ($this->result['has_more'])
                <div class="mt-6 flex justify-center">
                    <flux:button wire:click="loadMore" variant="outline" wire:loading.attr="disabled"
                        wire:target="loadMore">
                        <span wire:loading.remove wire:target="loadMore">Tampilkan lebih banyak</span>
                        <span wire:loading wire:target="loadMore">Memuat...</span>
                    </flux:button>
                </div>
            @endif
        @endif
    </div>

    {{-- Konfirmasi pembatalan --}}
    <flux:modal name="cancel-booking" class="min-w-[22rem] md:min-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Batalkan reservasi?</flux:heading>
                <flux:text class="mt-2">
                    Reservasi yang dibatalkan tidak bisa dikembalikan. Anda bisa membuat reservasi baru kapan saja.
                </flux:text>
            </div>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="subtle">Tidak</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancel" wire:loading.attr="disabled" wire:target="cancel">
                    <span wire:loading.remove wire:target="cancel">Ya, batalkan</span>
                    <span wire:loading wire:target="cancel">Memproses...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Detail Reservasi --}}
    <flux:modal name="detail-booking" class="min-w-[22rem] md:min-w-lg">
        @if ($selectedBooking)
            @php($detailSt = $statusMap[$selectedBooking['status']] ?? $statusMap['pending'])
            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-medium text-zinc-500">{{ $selectedBooking['code'] }}</span>
                        <flux:badge size="sm" color="{{ $detailSt['color'] }}">
                            {{ $detailSt['label'] }}
                        </flux:badge>
                    </div>
                    <flux:heading size="lg" class="mt-2">{{ $selectedBooking['service_item_name'] }}</flux:heading>
                    <flux:text class="text-sm text-zinc-600">
                        {{ $selectedBooking['category_label'] }} &middot; {{ $selectedBooking['vendor_name'] }}
                    </flux:text>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-600">Vendor / Klinik</span>
                        <span class="font-medium text-zinc-900 text-right">{{ $selectedBooking['vendor_name'] }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-600">Kategori Layanan</span>
                        <span class="font-medium text-zinc-900 text-right">{{ $selectedBooking['category_label'] }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-600">Jadwal Tanggal</span>
                        <span class="font-medium text-zinc-900 text-right">{{ $selectedBooking['date_label'] }}</span>
                    </div>

                    @if ($selectedBooking['time_label'])
                        <div class="flex justify-between gap-4">
                            <span class="text-zinc-600">Jam Kunjungan</span>
                            <span class="font-medium text-zinc-900 text-right">{{ $selectedBooking['time_label'] }} WIB</span>
                        </div>
                    @endif

                    @if ($selectedBooking['nights'])
                        <div class="flex justify-between gap-4">
                            <span class="text-zinc-600">Lama Menginap</span>
                            <span class="font-medium text-zinc-900 text-right">{{ $selectedBooking['nights'] }} malam</span>
                        </div>
                    @endif

                    <div class="border-t border-dashed border-zinc-200 pt-3">
                        @if (($selectedBooking['nights'] ?? 0) > 1)
                            <div class="flex justify-between text-xs text-zinc-500 mb-1">
                                <span>Tarif per malam</span>
                                <span>Rp {{ number_format($selectedBooking['unit_price'], 0, ',', '.') }} &times; {{ $selectedBooking['nights'] }} malam</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-baseline">
                            <span class="font-semibold text-zinc-900">Total Biaya</span>
                            <span class="text-lg font-bold text-accent">
                                Rp {{ number_format($selectedBooking['total_price'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                @if ($detailSt['hint'])
                    <p class="text-xs text-zinc-500 bg-zinc-50 rounded-lg p-3 border border-zinc-100">
                        💡 {{ $detailSt['hint'] }}
                    </p>
                @endif

                <div class="flex justify-between items-center gap-3 pt-2">
                    <div class="flex gap-2">
                        @if ($selectedBooking['whatsapp_url'])
                            <flux:button size="sm" variant="outline" icon="chat-bubble-left-ellipsis"
                                href="{{ $selectedBooking['whatsapp_url'] }}" target="_blank" rel="noopener">
                                Hubungi
                            </flux:button>
                        @endif
                        @if ($selectedBooking['maps_url'])
                            <flux:button size="sm" variant="outline" icon="map-pin"
                                href="{{ $selectedBooking['maps_url'] }}" target="_blank" rel="noopener">
                                Rute
                            </flux:button>
                        @endif
                    </div>

                    <flux:modal.close>
                        <flux:button variant="primary">Tutup</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
