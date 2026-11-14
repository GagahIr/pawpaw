<?php

use App\Actions\Client\Booking\BookingAction;
use App\Models\Vendor;
use App\Models\VendorServiceItem;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.client')] class extends Component {
    public string $slug;
    public string $bookingDate = '';
    public string $endDate = '';
    public string $vendorServiceId = '';
    public string $vendorServiceItemId = '';
    public string $timeSlot = '';

    private const MIN_LEAD_MINUTES = 60;
    private const MAX_ADVANCE_DAYS = 30;
    private const MAX_ADVANCE_DAYS_PET_HOTEL = 90;
    private const MAX_STAY_NIGHTS = 30;
    private const SLOT_STEP_MINUTES = 30;
    private const DATE_STRIP_DAYS = 14;
    private const NEXT_AVAILABLE_LOOKAHEAD = 14;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $this->bookingDate = now()->toDateString();

        // Auto-pilih kalau vendor hanya punya 1 kategori layanan
        $services = $this->vendor->vendorServices;
        if ($services->count() === 1) {
            $this->vendorServiceId = (string) $services->first()->id;
        }
    }

    /* -----------------------------------------------------------------
     |  Data (di-cache per request lewat #[Computed])
     | ----------------------------------------------------------------- */

    #[Computed]
    public function vendor(): Vendor
    {
        return Vendor::query()
            ->with([
                'vendorServices' => fn($query) => $query->where('status_service', true)->with(['categoryService', 'vendorServiceItems']),
                'vendorOperatingHours',
            ])
            ->where('slug', $this->slug)
            ->whereNotNull('join_at')
            ->where('status_verification', 'approved')
            ->where('status_subscription', 'active')
            ->where('subscription_expired_at', '>', now())
            ->firstOrFail();
    }

    #[Computed]
    public function selectedService()
    {
        return $this->vendor->vendorServices->firstWhere('id', (int) $this->vendorServiceId);
    }

    #[Computed]
    public function selectedItem(): ?VendorServiceItem
    {
        return $this->selectedService?->vendorServiceItems->firstWhere('id', (int) $this->vendorServiceItemId);
    }

    #[Computed]
    public function selectedCategory(): ?string
    {
        return $this->selectedService?->categoryService?->name;
    }

    #[Computed]
    public function isPetHotel(): bool
    {
        return mb_strtolower((string) $this->selectedCategory) === 'pet hotel';
    }

    #[Computed]
    public function maxBookingDate(): string
    {
        $days = $this->isPetHotel ? self::MAX_ADVANCE_DAYS_PET_HOTEL : self::MAX_ADVANCE_DAYS;
        return now()->addDays($days)->toDateString();
    }

    #[Computed]
    public function maxEndDate(): string
    {
        return Carbon::parse($this->bookingDate ?: now())
            ->addDays(self::MAX_STAY_NIGHTS)
            ->toDateString();
    }

    #[Computed]
    public function totalNights(): int
    {
        if (!$this->bookingDate || !$this->endDate) {
            return 0;
        }
        return max(1, Carbon::parse($this->bookingDate)->diffInDays(Carbon::parse($this->endDate)));
    }

    #[Computed]
    public function totalPrice(): int
    {
        if (!$this->selectedItem) {
            return 0;
        }
        return (int) $this->selectedItem->price * ($this->isPetHotel ? max(1, $this->totalNights) : 1);
    }

    /** Hasil slot untuk tanggal yang sedang dipilih. */
    #[Computed]
    public function daySlots(): array
    {
        if ($this->isPetHotel || !$this->selectedItem || !$this->bookingDate) {
            return ['status' => 'idle', 'slots' => []];
        }
        return $this->slotsForDate($this->bookingDate, $this->selectedItem);
    }

    /** Slot dikelompokkan: Pagi / Siang / Sore. */
    #[Computed]
    public function groupedSlots(): array
    {
        $groups = ['Pagi' => [], 'Siang' => [], 'Sore' => []];

        foreach ($this->daySlots['slots'] as $slot) {
            $group = $slot['minutes'] < 12 * 60 ? 'Pagi' : ($slot['minutes'] < 16 * 60 ? 'Siang' : 'Sore');
            $groups[$group][] = $slot;
        }

        return array_filter($groups);
    }

    /** Menit mulai yang benar-benar bisa dipilih. */
    #[Computed]
    public function availableMinutes(): array
    {
        return collect($this->daySlots['slots'])->where('disabled', false)->pluck('minutes')->all();
    }

    /** Hari terdekat (setelah tanggal terpilih) yang masih punya slot. */
    #[Computed]
    public function nextAvailableDate(): ?array
    {
        if ($this->isPetHotel || !$this->selectedItem || !$this->bookingDate) {
            return null;
        }

        $cursor = Carbon::parse($this->bookingDate);
        $limit = Carbon::parse($this->maxBookingDate);

        for ($i = 1; $i <= self::NEXT_AVAILABLE_LOOKAHEAD; $i++) {
            $date = $cursor->copy()->addDays($i);
            if ($date->gt($limit)) {
                break;
            }
            if ($this->slotsForDate($date->toDateString(), $this->selectedItem)['status'] === 'ok') {
                return ['date' => $date->toDateString(), 'label' => $date->locale('id')->isoFormat('dddd, D MMM')];
            }
        }

        return null;
    }

    /** Strip tanggal horizontal untuk layanan harian. */
    #[Computed]
    public function dateStrip(): array
    {
        $strip = [];
        $limit = Carbon::parse($this->maxBookingDate);

        for ($i = 0; $i < self::DATE_STRIP_DAYS; $i++) {
            $date = now()->addDays($i);
            if ($date->gt($limit)) {
                break;
            }
            $hour = $this->hourFor($date->toDateString());
            $strip[] = [
                'value' => $date->toDateString(),
                'day' => $i === 0 ? 'Hari ini' : ($i === 1 ? 'Besok' : $date->locale('id')->isoFormat('ddd')),
                'num' => $date->day,
                'month' => $date->locale('id')->isoFormat('MMM'),
                'closed' => !$hour || $hour->is_closed,
            ];
        }

        return $strip;
    }

    /** Jam selesai (untuk layanan harian), contoh "10:00 – 11:00". */
    #[Computed]
    public function timeRange(): ?string
    {
        if ($this->timeSlot === '' || !$this->selectedItem) {
            return null;
        }
        $start = Carbon::parse($this->bookingDate)->startOfDay()->addMinutes((int) $this->timeSlot);
        return $start->format('H:i') . ' – ' . $start->copy()->addMinutes($this->selectedItem->duration)->format('H:i');
    }

    #[Computed]
    public function canSubmit(): bool
    {
        if (!$this->vendorServiceId || !$this->vendorServiceItemId) {
            return false;
        }
        return $this->isPetHotel ? $this->endDate !== '' : $this->timeSlot !== '';
    }

    /** Teks bantuan: memberi tahu pengguna "kurang apa lagi". */
    #[Computed]
    public function helperText(): string
    {
        return match (true) {
            !$this->vendorServiceId => 'Pilih kategori layanan untuk memulai',
            !$this->vendorServiceItemId => 'Pilih jenis perawatan',
            $this->isPetHotel && $this->endDate === '' => 'Pilih tanggal check-out untuk melanjutkan',
            !$this->isPetHotel && $this->timeSlot === '' => 'Pilih jam kunjungan untuk melanjutkan',
            default => '',
        };
    }

    /* -----------------------------------------------------------------
     |  Logika slot
     | ----------------------------------------------------------------- */

    private function hourFor(string $date)
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso - 1;
        return $this->vendor->vendorOperatingHours->first(fn($hour) => $hour->day_of_week->value === $dayOfWeek);
    }

    /**
     * Menit mulai (dari 00:00) yang sudah terisi pada tanggal tertentu.
     *
     * TODO: sambungkan ke tabel booking milik Anda. Contoh:
     *
     *   return Booking::query()
     *       ->where('vendor_id', $this->vendor->id)
     *       ->whereDate('booking_date', $date)
     *       ->whereIn('status', ['pending', 'confirmed'])
     *       ->get()
     *       ->flatMap(fn ($b) => range($b->start_minutes, $b->start_minutes + $b->duration - 1, 30))
     *       ->all();
     *
     * Jika vendor punya >1 staf/kapasitas, hitung per slot: terisi bila jumlah booking >= kapasitas.
     */
    private function bookedMinutes(string $date): array
    {
        return [];
    }

    /**
     * @return array{status: string, slots: array<int, array{minutes:int,label:string,disabled:bool,reason:?string}>}
     * status: closed | passed | full | ok
     */
    private function slotsForDate(string $date, VendorServiceItem $item): array
    {
        $hour = $this->hourFor($date);

        if (!$hour || $hour->is_closed) {
            return ['status' => 'closed', 'slots' => []];
        }

        $appTimezone = config('app.timezone', 'UTC');
        $now = now();
        $isToday = Carbon::parse($date, $appTimezone)->isToday();

        $start = Carbon::parse($date . ' ' . $hour->open_time, $appTimezone);
        $end = Carbon::parse($date . ' ' . $hour->close_time, $appTimezone);
        $booked = $this->bookedMinutes($date);
        $slots = [];

        for ($slot = $start->copy(); $slot->copy()->addMinutes($item->duration)->lte($end); $slot->addMinutes(self::SLOT_STEP_MINUTES)) {
            $minutes = $slot->hour * 60 + $slot->minute;
            $isPast = $isToday && $slot->lte($now);
            $reason = $isPast ? 'past' : (in_array($minutes, $booked, true) ? 'booked' : null);

            $slots[] = [
                'minutes' => $minutes,
                'label' => $slot->format('H:i'),
                'disabled' => $reason !== null,
                'reason' => $reason,
            ];
        }

        if ($slots === []) {
            return ['status' => 'closed', 'slots' => []];
        }

        $collection = collect($slots);
        $status = match (true) {
            $collection->contains('disabled', false) => 'ok',
            $collection->every(fn($s) => $s['reason'] === 'past') => 'passed',
            default => 'full',
        };

        return ['status' => $status, 'slots' => $slots];
    }

    /* -----------------------------------------------------------------
     |  Aksi
     | ----------------------------------------------------------------- */

    public function selectService(int|string $serviceId): void
    {
        if ((string) $serviceId === $this->vendorServiceId) {
            return;
        }

        $this->vendorServiceId = (string) $serviceId;
        $this->vendorServiceItemId = '';
        $this->endDate = '';
        $this->timeSlot = '';

        // Pertahankan tanggal jika masih valid untuk kategori baru
        if ($this->bookingDate > $this->maxBookingDate || $this->bookingDate < now()->toDateString()) {
            $this->bookingDate = now()->toDateString();
        }

        unset($this->selectedService, $this->selectedItem, $this->selectedCategory, $this->isPetHotel);
    }

    public function selectItem(int|string $itemId): void
    {
        $this->vendorServiceItemId = (string) $itemId;
        $this->timeSlot = '';
        unset($this->selectedItem);
    }

    public function selectTime(int|string $minutes): void
    {
        $this->timeSlot = (string) $minutes;
    }

    public function selectDate(string $date): void
    {
        $this->bookingDate = $date;
        $this->afterBookingDateChanged();
    }

    public function updatedBookingDate(): void
    {
        $this->afterBookingDateChanged();
    }

    public function updatedVendorServiceItemId(): void
    {
        $this->timeSlot = '';
    }

    public function nextDay(): void
    {
        $this->selectDate(Carbon::parse($this->bookingDate)->addDay()->toDateString());
    }

    public function useNextAvailable(): void
    {
        if ($next = $this->nextAvailableDate) {
            $this->selectDate($next['date']);
        }
    }

    public function setNights(int $nights): void
    {
        $nights = max(1, min($nights, self::MAX_STAY_NIGHTS));
        $this->endDate = Carbon::parse($this->bookingDate)->addDays($nights)->toDateString();
    }

    private function afterBookingDateChanged(): void
    {
        $this->timeSlot = '';

        if ($this->endDate !== '' && $this->endDate < $this->bookingDate) {
            $this->endDate = '';
        }
    }

    /* -----------------------------------------------------------------
     |  Validasi & submit
     | ----------------------------------------------------------------- */

    private function reservationRules(bool $isPetHotel): array
    {
        return [
            'vendorServiceId' => ['required', 'integer'],
            'vendorServiceItemId' => ['required', 'integer'],
            'bookingDate' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->maxBookingDate],
            'endDate' => [
                $isPetHotel ? 'required' : 'nullable',
                'date',
                'after_or_equal:bookingDate',
                function ($attribute, $value, $fail) {
                    if (!$value || !$this->bookingDate) {
                        return;
                    }
                    if (Carbon::parse($this->bookingDate)->diffInDays(Carbon::parse($value)) > self::MAX_STAY_NIGHTS) {
                        $fail('Lama menginap maksimal ' . self::MAX_STAY_NIGHTS . ' malam.');
                    }
                },
            ],
            'timeSlot' => [
                'bail',
                $isPetHotel ? 'nullable' : 'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($isPetHotel) {
                    if ($isPetHotel) {
                        return;
                    }
                    if (!in_array((int) $value, $this->availableMinutes, true)) {
                        $fail('Jam yang dipilih sudah tidak tersedia. Silakan pilih jam lain.');
                    }
                },
            ],
        ];
    }

    public function confirmReservation(): void
    {
        try {
            $this->validate($this->reservationRules($this->isPetHotel));
        } catch (ValidationException $exception) {
            Flux::toast($exception->validator->errors()->first(), variant: 'danger');
            throw $exception;
        }

        $this->modal('confirm-reservation')->show();
    }

    public function reserve(BookingAction $bookingAction)
    {
        $isPetHotel = $this->isPetHotel;

        try {
            $this->validate($this->reservationRules($isPetHotel));

            $bookingAction->execute(auth('client')->user(), $this->slug, (int) $this->vendorServiceId, (int) $this->vendorServiceItemId, $this->bookingDate, (int) $this->timeSlot, $isPetHotel ? $this->endDate : null);
        } catch (ValidationException $exception) {
            $this->modal('confirm-reservation')->close();
            Flux::toast($exception->validator->errors()->first(), variant: 'danger');
            throw $exception;
        }

        Flux::toast('Reservasi berhasil dibuat. Menunggu konfirmasi vendor.', variant: 'success');
        return $this->redirectRoute('client.my-bookings', navigate: true);
    }

    public function with(): array
    {
        return ['vendor' => $this->vendor];
    }
};
?>

<div class="mx-auto max-w-2xl py-6 sm:py-10">
    <a href="{{ route('client.vendor.detail', $vendor->slug) }}" wire:navigate
        class="text-sm font-medium text-accent hover:underline">
        &larr; Kembali ke {{ $vendor->name }}
    </a>

    <flux:card class="mt-5 space-y-8">
        <div>
            <flux:heading size="xl">Buat reservasi</flux:heading>
            <flux:subheading>{{ $vendor->name }} &middot; {{ $vendor->address }}</flux:subheading>
        </div>

        <form wire:submit="confirmReservation" class="space-y-8">

            {{-- 1. Layanan --}}
            <section class="space-y-3" aria-labelledby="step-service">
                <div>
                    <h2 id="step-service" class="text-sm font-semibold text-zinc-900">1. Pilih layanan</h2>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($vendor->vendorServices as $service)
                        @php($active = (string) $service->id === $vendorServiceId)
                        <button type="button" wire:click="selectService({{ $service->id }})"
                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                            class="min-h-11 rounded-lg border px-3 py-2.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
                            {{ $active ? 'border-accent bg-accent text-white shadow-sm' : 'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-50' }}">
                            {{ $service->categoryService->name }}
                        </button>
                    @endforeach
                </div>
                <flux:error name="vendorServiceId" />
            </section>

            @if ($vendorServiceId)
                {{-- 2. Perawatan --}}
                <section class="space-y-3" aria-labelledby="step-item">
                    <h2 id="step-item" class="text-sm font-semibold text-zinc-900">2. Pilih perawatan</h2>

                    <div class="space-y-2" role="radiogroup" aria-labelledby="step-item">
                        @foreach ($this->selectedService->vendorServiceItems as $item)
                            @php($active = (string) $item->id === $vendorServiceItemId)
                            <button type="button" role="radio" aria-checked="{{ $active ? 'true' : 'false' }}"
                                wire:click="selectItem({{ $item->id }})"
                                class="flex w-full items-start gap-3 rounded-xl border p-4 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
                                {{ $active ? 'border-accent bg-accent/5 ring-1 ring-accent' : 'border-zinc-200 bg-white hover:border-zinc-300 hover:bg-zinc-50' }}">
                                <span
                                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border {{ $active ? 'border-accent bg-accent' : 'border-zinc-300' }}">
                                    @if ($active)
                                        <span class="size-2 rounded-full bg-white"></span>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium text-zinc-900">{{ $item->name }}</span>
                                    <span class="mt-0.5 block text-sm text-zinc-600">
                                        {{ $this->isPetHotel ? 'Per malam' : $item->duration . ' menit' }}
                                    </span>
                                    @if (!empty($item->description))
                                        <span
                                            class="mt-1 line-clamp-2 block text-sm text-zinc-600">{{ $item->description }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 font-semibold text-zinc-900">
                                    Rp {{ number_format($item->price, 0, ',', '.') }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <flux:error name="vendorServiceItemId" />
                </section>

                {{-- 3. Jadwal --}}
                @if ($vendorServiceItemId)
                    <section class="space-y-5" aria-labelledby="step-schedule">
                        <h2 id="step-schedule" class="text-sm font-semibold text-zinc-900">3. Pilih jadwal</h2>

                        @if ($this->isPetHotel)
                            {{-- ===== Pet hotel: check-in / check-out ===== --}}
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <flux:input wire:model.live="bookingDate" type="date" label="Tanggal check-in"
                                        min="{{ now()->toDateString() }}" max="{{ $this->maxBookingDate }}" />
                                    <flux:error name="bookingDate" />
                                </div>
                                <div>
                                    <flux:input wire:model.live="endDate" type="date" label="Tanggal check-out"
                                        min="{{ $bookingDate ?: now()->toDateString() }}"
                                        max="{{ $this->maxEndDate }}" />
                                    <flux:error name="endDate" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="text-sm text-zinc-600">Atau pilih lama menginap</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ([1, 2, 3, 7, 14] as $nights)
                                        @php($active = $endDate !== '' && $this->totalNights === $nights)
                                        <button type="button" wire:click="setNights({{ $nights }})"
                                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                                            class="min-h-11 rounded-lg border px-4 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
                                            {{ $active ? 'border-accent bg-accent text-accent-content' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100' }}">
                                            {{ $nights }} malam
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- ===== Layanan harian: strip tanggal ===== --}}
                            <div class="space-y-2">
                                <p class="text-sm text-zinc-600">Tanggal kunjungan</p>
                                <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-2" role="group"
                                    aria-label="Tanggal kunjungan">
                                    @foreach ($this->dateStrip as $day)
                                        @php($active = $bookingDate === $day['value'])
                                        <button type="button" wire:click="selectDate('{{ $day['value'] }}')"
                                            @disabled($day['closed'])
                                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                                            class="flex min-h-16 w-[4.5rem] shrink-0 flex-col items-center justify-center rounded-xl border px-2 py-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
                                            {{ $active ? 'border-accent bg-accent text-white shadow-sm' : ($day['closed'] ? 'cursor-not-allowed border-zinc-200 bg-zinc-50 text-zinc-400' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100') }}">
                                            <span class="text-xs">{{ $day['day'] }}</span>
                                            <span
                                                class="text-lg font-semibold leading-tight">{{ $day['num'] }}</span>
                                            <span class="text-xs">{{ $day['closed'] ? 'Tutup' : $day['month'] }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                <details class="text-sm">
                                    <summary class="cursor-pointer font-medium text-accent hover:underline">
                                        Pilih tanggal lain
                                    </summary>
                                    <div class="mt-2 max-w-xs">
                                        <flux:input wire:model.live="bookingDate" type="date"
                                            min="{{ now()->toDateString() }}" max="{{ $this->maxBookingDate }}" />
                                    </div>
                                </details>
                                <flux:error name="bookingDate" />
                            </div>

                            {{-- ===== Jam kunjungan ===== --}}
                            <div class="min-h-40 space-y-3" wire:loading.class="opacity-60"
                                wire:target="selectDate,bookingDate,nextDay,useNextAvailable,selectItem">
                                <p class="text-sm text-zinc-600">Jam kunjungan</p>

                                @php($status = $this->daySlots['status'])

                                @if ($status === 'ok' || $status === 'full' || $status === 'passed')
                                    @foreach ($this->groupedSlots as $group => $slots)
                                        <div class="space-y-2">
                                            <p class="text-xs font-medium text-zinc-600">{{ $group }}</p>
                                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                                @foreach ($slots as $slot)
                                                    @php($active = $timeSlot === (string) $slot['minutes'])
                                                    <button type="button"
                                                        wire:click="selectTime({{ $slot['minutes'] }})"
                                                        @disabled($slot['disabled'])
                                                        aria-pressed="{{ $active ? 'true' : 'false' }}"
                                                        aria-label="Pilih jam {{ $slot['label'] }}{{ $slot['reason'] === 'booked' ? ', sudah terisi' : ($slot['reason'] === 'past' ? ', sudah lewat' : '') }}"
                                                        class="min-h-11 rounded-lg border px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
                                                        {{ $active ? 'border-accent bg-accent text-white shadow-sm' : ($slot['disabled'] ? 'cursor-not-allowed border-zinc-200 bg-zinc-50 text-zinc-400 line-through' : 'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-100') }}">
                                                        {{ $slot['label'] }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if ($status !== 'ok')
                                    {{-- Empty state: pesan sesuai penyebab --}}
                                    <div
                                        class="flex flex-col items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-center">
                                        <p class="text-sm text-zinc-700">
                                            @if ($status === 'closed')
                                                Vendor tutup pada tanggal ini.
                                            @elseif ($status === 'passed')
                                                Jam operasional hari ini sudah lewat atau terlalu dekat.
                                            @else
                                                Semua jam pada tanggal ini sudah terisi.
                                            @endif
                                        </p>

                                        @if ($next = $this->nextAvailableDate)
                                            <flux:button type="button" size="sm" variant="primary"
                                                wire:click="useNextAvailable">
                                                Lihat {{ $next['label'] }}
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-zinc-600">
                                                Belum ada slot kosong dalam {{ 14 }} hari ke depan.
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                <flux:error name="timeSlot" />
                            </div>
                        @endif
                    </section>
                @endif
            @endif

            {{-- Ringkasan biaya + tombol (selalu tampil, sticky di mobile) --}}
            <div
                class="sticky bottom-0 -mx-6 -mb-6 space-y-3 rounded-b-xl border-t border-zinc-200 bg-white/95 px-6 py-4 backdrop-blur sm:static sm:mx-0 sm:mb-0 sm:rounded-none sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none">

                @if ($this->selectedItem && $this->canSubmit)
                    <div
                        class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                        <div class="min-w-0">
                            <p class="text-sm text-zinc-600">Estimasi biaya</p>
                            <p class="text-xl font-bold text-zinc-900">
                                Rp {{ number_format($this->totalPrice, 0, ',', '.') }}
                            </p>
                            @if ($this->isPetHotel)
                                <p class="text-xs text-zinc-600">
                                    Rp {{ number_format($this->selectedItem->price, 0, ',', '.') }}
                                    &times; {{ $this->totalNights }} malam
                                </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-zinc-900">{{ $this->selectedItem->name }}</p>
                            <p class="text-xs text-zinc-600">
                                @if ($this->isPetHotel)
                                    {{ \Carbon\Carbon::parse($bookingDate)->locale('id')->isoFormat('D MMM') }}
                                    &ndash;
                                    {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMM') }}
                                @else
                                    {{ $this->timeRange }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                <flux:button type="submit" variant="primary" class="w-full py-3 text-base"
                    :disabled="!$this->canSubmit" wire:loading.attr="disabled" wire:target="confirmReservation">
                    <span wire:loading.remove wire:target="confirmReservation">Lanjut pesan</span>
                    <span wire:loading wire:target="confirmReservation">Memeriksa...</span>
                </flux:button>

                @if ($this->helperText !== '')
                    <p class="text-center text-sm text-zinc-600" aria-live="polite">{{ $this->helperText }}</p>
                @endif
            </div>
        </form>
    </flux:card>

    {{-- Modal konfirmasi (nota) --}}
    <flux:modal name="confirm-reservation" class="min-w-[22rem] md:min-w-md">
        <div class="space-y-6">
            <div class="border-b border-zinc-100 pb-4 text-center">
                <flux:heading size="lg" class="!text-xl !font-bold">Nota reservasi</flux:heading>
                <p class="mt-1 text-sm text-zinc-600">Pastikan detail pesanan sudah sesuai</p>
            </div>

            @php($confirmItem = $this->selectedItem)

            <div class="space-y-4 rounded-xl border border-zinc-100 bg-zinc-50 p-5 shadow-sm">
                <div class="border-b border-zinc-200 pb-4">
                    <h3 class="text-lg font-bold text-zinc-900">{{ $confirmItem?->name ?? '-' }}</h3>
                    <p class="text-sm text-zinc-600">{{ $vendor->name }} &middot;
                        {{ $this->selectedCategory ?? '-' }}
                    </p>
                </div>

                <dl class="space-y-3 text-sm">
                    @if ($this->isPetHotel)
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-600">Check-in</dt>
                            <dd class="text-right font-medium text-zinc-900">
                                {{ $bookingDate ? \Carbon\Carbon::parse($bookingDate)->locale('id')->isoFormat('ddd, D MMM YYYY') : '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-600">Check-out</dt>
                            <dd class="text-right font-medium text-zinc-900">
                                {{ $endDate ? \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('ddd, D MMM YYYY') : '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-600">Durasi</dt>
                            <dd class="text-right font-medium text-zinc-900">{{ $this->totalNights }} malam</dd>
                        </div>
                    @else
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-600">Tanggal</dt>
                            <dd class="text-right font-medium text-zinc-900">
                                {{ $bookingDate ? \Carbon\Carbon::parse($bookingDate)->locale('id')->isoFormat('ddd, D MMM YYYY') : '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-600">Jam</dt>
                            <dd class="text-right font-medium text-zinc-900">{{ $this->timeRange ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-600">Estimasi pengerjaan</dt>
                            <dd class="text-right font-medium text-zinc-900">{{ $confirmItem?->duration ?? '-' }}
                                menit
                            </dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-4 space-y-1 border-t border-dashed border-zinc-300 pt-4">
                    @if ($confirmItem && $this->isPetHotel)
                        <div class="flex justify-between text-sm text-zinc-600">
                            <span>Rp {{ number_format($confirmItem->price, 0, ',', '.') }} &times;
                                {{ max(1, $this->totalNights) }} malam</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-zinc-700">Total tagihan</span>
                        <span class="text-xl font-bold text-accent">
                            {{ $confirmItem ? 'Rp ' . number_format($this->totalPrice, 0, ',', '.') : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs text-zinc-600">
                Reservasi akan menunggu konfirmasi dari vendor.
            </p>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Ubah data</flux:button>
                </flux:modal.close>
                <flux:button wire:click="reserve" variant="primary" wire:loading.attr="disabled"
                    wire:target="reserve">
                    <span wire:loading.remove wire:target="reserve">Konfirmasi pesanan</span>
                    <span wire:loading wire:target="reserve">Memproses...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
