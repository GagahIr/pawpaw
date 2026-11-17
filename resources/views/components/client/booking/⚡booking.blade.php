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

    /** @return list<int> Closed weekdays using the calendar's Sunday-first numbering. */
    #[Computed]
    public function closedWeekdays(): array
    {
        return collect(range(0, 6))
            ->reject(fn (int $day): bool => $this->vendor->vendorOperatingHours
                ->contains(fn ($hour): bool => $hour->day_of_week->value === $day && !$hour->is_closed))
            ->map(fn (int $day): int => ($day + 1) % 7)
            ->values()
            ->all();
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

<div class="booking-page">
    <div class="clinic-request-progress" wire:loading.delay aria-hidden="true"><span></span></div>
    @include('components.client.components.clinic-atmosphere')
    <div class="booking-wrap">
    <a href="{{ route('client.vendor.detail', $vendor->slug) }}" wire:navigate
        class="clinic-back">
        <flux:icon.arrow-left class="size-4" /> Kembali ke detail klinik
    </a>

    <div class="booking-content">
        <header class="booking-intro" data-animate="fade-up" data-motion-once>
            <span class="booking-eyebrow">SATU LANGKAH LEBIH DEKAT MERAWATNYA</span>
            <div class="booking-title-row">
                <h1>Buat reservasi</h1>
                <svg class="booking-paw" viewBox="0 0 40 40" fill="currentColor" aria-hidden="true"><ellipse cx="10" cy="14" rx="4" ry="6" transform="rotate(-25 10 14)"/><ellipse cx="19" cy="9" rx="4" ry="6"/><ellipse cx="29" cy="13" rx="4" ry="6" transform="rotate(25 29 13)"/><path d="M11 29c0-5 6-13 10-12s11 9 9 14-7 1-10 2-9 1-9-4Z"/></svg>
            </div>
            <p class="booking-vendor">{{ $vendor->name }}</p>
            <p class="booking-address">{{ $vendor->address }}</p>
            <p class="booking-description">Pilih perawatan dan jadwal yang pas untuk si kesayangan.</p>
            <figure class="booking-companion booking-companion-planner" aria-hidden="true">
                <svg class="booking-companion-doodle" viewBox="0 0 40 40" fill="currentColor"><ellipse cx="10" cy="14" rx="4" ry="6" transform="rotate(-25 10 14)"/><ellipse cx="19" cy="9" rx="4" ry="6"/><ellipse cx="29" cy="13" rx="4" ry="6" transform="rotate(25 29 13)"/><path d="M11 29c0-5 6-13 10-12s11 9 9 14-7 1-10 2-9 1-9-4Z"/></svg>
                <span class="booking-companion-spark">✦</span>
                <span class="booking-companion-spark booking-companion-spark-small">✦</span>
                <img src="{{ asset('assets/calendar.webp') }}" alt="" width="145" height="153" draggable="false">
                <span class="booking-planner-ticket">Waktunya merawat <span>✦</span></span>
            </figure>
        </header>

        <ol class="booking-journey" aria-label="Tahapan reservasi" data-animate="fade-up" data-animate-delay="0.08">
            <li data-complete="{{ $vendorServiceId ? 'true' : 'false' }}" @if (!$vendorServiceId) aria-current="step" @endif>
                <span class="booking-journey-number" aria-hidden="true">01</span>
                <span><strong>Layanan</strong><small>Mulai dari kebutuhannya</small></span>
            </li>
            <li data-complete="{{ $vendorServiceItemId ? 'true' : 'false' }}" @if ($vendorServiceId && !$vendorServiceItemId) aria-current="step" @endif>
                <span class="booking-journey-number" aria-hidden="true">02</span>
                <span><strong>Perawatan</strong><small>Pilih yang paling pas</small></span>
            </li>
            <li data-complete="{{ $this->canSubmit ? 'true' : 'false' }}" @if ($vendorServiceItemId && !$this->canSubmit) aria-current="step" @endif>
                <span class="booking-journey-number" aria-hidden="true">03</span>
                <span><strong>Jadwal</strong><small>Siapkan waktu untuknya</small></span>
            </li>
        </ol>

        <form wire:submit="confirmReservation" class="booking-layout">
            <div class="booking-fields" data-animate="fade-up" data-animate-delay="0.12">

            {{-- 1. Layanan --}}
            <section class="space-y-3" aria-labelledby="step-service">
                <div>
                    <h2 id="step-service" class="booking-section-title"><span class="booking-step-number">01</span>Pilih layanan</h2>
                </div>
                <div class="booking-categories">
                    @foreach ($vendor->vendorServices as $service)
                        @php($active = (string) $service->id === $vendorServiceId)
                        <button type="button" wire:click="selectService({{ $service->id }})"
                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                            class="min-h-11 rounded-lg border px-3 py-2.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
                            {{ $active ? 'border-accent bg-accent text-white shadow-sm' : 'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-50' }}">
                            {{ mb_strtolower($service->categoryService->name) === 'clinic' ? 'Klinik / Medis' : $service->categoryService->name }}
                        </button>
                    @endforeach
                </div>
                <flux:error name="vendorServiceId" />
            </section>

            @if ($vendorServiceId)
                {{-- 2. Perawatan --}}
                <section class="space-y-3" aria-labelledby="step-item">
                    <h2 id="step-item" class="booking-section-title"><span class="booking-step-number">02</span>Pilih perawatan</h2>

                    <div class="booking-treatments" role="radiogroup" aria-labelledby="step-item">
                        @foreach ($this->selectedService->vendorServiceItems as $item)
                            @php($active = (string) $item->id === $vendorServiceItemId)
                            <button type="button" role="radio" aria-checked="{{ $active ? 'true' : 'false' }}"
                                wire:key="booking-treatment-{{ $item->id }}" wire:click="selectItem({{ $item->id }})"
                                class="booking-choice flex w-full items-start gap-3 rounded-xl border p-4 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-1
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
                        <h2 id="step-schedule" class="booking-section-title"><span class="booking-step-number">03</span>Pilih jadwal</h2>

                        @if ($this->isPetHotel)
                            {{-- ===== Pet hotel: check-in / check-out ===== --}}
                            <div class="space-y-2">
                                <p class="booking-stay-label">Mau menginap berapa lama?</p>
                                <p class="booking-stay-help">Dihitung dari tanggal check-in. Kamu bisa mengubahnya di bawah.</p>
                                <div class="booking-stay-options" role="group" aria-label="Lama menginap">
                                    @foreach ([1, 2, 3, 7, 14] as $nights)
                                        @php($active = $endDate !== '' && $this->totalNights === $nights)
                                        <button type="button" class="booking-stay-option" wire:click="setNights({{ $nights }})"
                                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                                            >
                                            <svg class="booking-stay-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M19.5 14A8 8 0 0 1 10 4.5 8 8 0 1 0 19.5 14Z"/><path d="m17 3 .7 1.8 1.8.7-1.8.7L17 8l-.7-1.8-1.8-.7 1.8-.7Z"/></svg>
                                            <span><strong>{{ $nights }}</strong> malam</span>
                                            <small>{{ match ($nights) { 1 => 'Singgah singkat', 2 => 'Rehat sejenak', 3 => 'Lebih santai', 7 => 'Satu minggu', 14 => 'Dua minggu' } }}</small>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <p class="booking-date-alternative"><span>Atau pilih tanggalmu sendiri</span></p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-client.booking-date-picker :closed-weekdays="$this->closedWeekdays" model="bookingDate" label="Tanggal check-in" :value="$bookingDate" :min="now()->toDateString()" :max="$this->maxBookingDate" />
                                    <flux:error name="bookingDate" />
                                </div>
                                <div>
                                    <x-client.booking-date-picker :closed-weekdays="$this->closedWeekdays" model="endDate" label="Tanggal check-out" :value="$endDate" :min="$bookingDate ?: now()->toDateString()" :max="$this->maxEndDate" />
                                    <flux:error name="endDate" />
                                </div>
                            </div>

                        @else
                            {{-- Layanan harian memakai kartu pilihan dan kalender PawPaw yang sama. --}}
                            <div class="space-y-2">
                                <p class="booking-stay-label">Mau datang kapan?</p>
                                <p class="booking-stay-help">Pilih tanggal yang tersedia untuk si kesayangan.</p>
                                <div class="booking-visit-dates" role="group" aria-label="Tanggal kunjungan">
                                    @foreach ($this->dateStrip as $day)
                                        @php($active = $bookingDate === $day['value'])
                                        <button type="button" wire:key="booking-visit-date-{{ $day['value'] }}" wire:click="selectDate('{{ $day['value'] }}')"
                                            @disabled($day['closed'])
                                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                                            class="booking-stay-option booking-visit-date">
                                            <span class="booking-visit-day">{{ $day['day'] }}</span>
                                            <strong class="booking-visit-number">{{ $day['num'] }}</strong>
                                            <small>{{ $day['closed'] ? 'Tutup' : $day['month'] }}</small>
                                        </button>
                                    @endforeach
                                </div>

                                <p class="booking-date-alternative"><span>Atau pilih tanggalmu sendiri</span></p>
                                <div class="booking-visit-custom-date">
                                    <x-client.booking-date-picker :closed-weekdays="$this->closedWeekdays" model="bookingDate" label="Tanggal kunjungan" :value="$bookingDate" :min="now()->toDateString()" :max="$this->maxBookingDate" />
                                </div>
                                <flux:error name="bookingDate" />
                            </div>

                            {{-- ===== Jam kunjungan ===== --}}
                            <div class="min-h-40 space-y-3" wire:loading.class="opacity-60"
                                wire:target="selectDate,bookingDate,nextDay,useNextAvailable,selectItem">
                                <p class="booking-stay-label">Pilih jam kunjungan</p>
                                <p class="booking-stay-help">Jam yang redup sudah lewat atau tidak tersedia.</p>

                                @php($status = $this->daySlots['status'])

                                @if ($status === 'ok' || $status === 'full' || $status === 'passed')
                                    @foreach ($this->groupedSlots as $group => $slots)
                                        <div class="space-y-2">
                                            <p class="booking-time-period"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg>{{ $group }}</p>
                                            <div class="booking-time-options">
                                                @foreach ($slots as $slot)
                                                    @php($active = $timeSlot === (string) $slot['minutes'])
                                                    <button type="button"
                                                        wire:click="selectTime({{ $slot['minutes'] }})"
                                                        @disabled($slot['disabled'])
                                                        aria-pressed="{{ $active ? 'true' : 'false' }}"
                                                        aria-label="Pilih jam {{ $slot['label'] }}{{ $slot['reason'] === 'booked' ? ', sudah terisi' : ($slot['reason'] === 'past' ? ', sudah lewat' : '') }}"
                                                        class="booking-stay-option booking-time-option">
                                                        <span class="booking-time-value"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $slot['label'] }}</span>
                                                        <small>{{ $active ? 'Dipilih' : ($slot['disabled'] ? ($slot['reason'] === 'booked' ? 'Terisi' : 'Lewat') : 'Tersedia') }}</small>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if ($status !== 'ok')
                                    {{-- Empty state: pesan sesuai penyebab --}}
                                    <div
                                        class="booking-schedule-empty">
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
                                            <flux:button type="button" size="sm" variant="primary" class="booking-primary"
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

            </div>

            <aside class="booking-summary" aria-labelledby="booking-summary-title" data-animate="soft-pop" data-animate-delay="0.12">
                <div class="booking-summary-sticker"><img src="{{ asset('assets/calendar.webp') }}" alt="" width="28" height="28" aria-hidden="true"> Pilihan si kesayangan</div>
                <div class="booking-summary-heading">
                    <span class="booking-eyebrow">SEBELUM MELANJUTKAN</span>
                    <h2 id="booking-summary-title">Ringkasan reservasi</h2>
                </div>
                <p class="booking-summary-vendor">{{ $vendor->name }}</p>
                @if (!$this->canSubmit)
                    <div class="booking-summary-preview">
                        @if ($this->selectedItem)
                            <strong>{{ $this->selectedItem->name }}</strong>
                            <span>Rp {{ number_format($this->selectedItem->price, 0, ',', '.') }}{{ $this->isPetHotel ? ' / malam' : ' / kunjungan' }}</span>
                        @else
                            <p>Perawatan dan jadwal pilihanmu akan tampil di sini.</p>
                        @endif
                    </div>
                @endif

                @if ($this->selectedItem && $this->canSubmit)
                    <div
                        class="booking-cost" wire:key="booking-cost-{{ $vendorServiceItemId }}-{{ $bookingDate }}-{{ $endDate }}-{{ $timeSlot }}">
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

                <flux:button type="submit" variant="primary" class="booking-primary w-full py-3 text-base"
                    :disabled="!$this->canSubmit" wire:loading.attr="disabled" wire:target="confirmReservation">
                    <span wire:loading.remove wire:target="confirmReservation">Tinjau reservasi</span>
                    <span wire:loading wire:target="confirmReservation">Memeriksa...</span>
                </flux:button>

                @if ($this->helperText !== '')
                    <p class="text-center text-sm text-zinc-600" aria-live="polite">{{ $this->helperText }}</p>
                @endif
                <p class="booking-payment-note">Menunggu konfirmasi klinik. Pembayaran layanan dilakukan langsung di klinik.</p>
            </aside>
        </form>
    </div>

    {{-- Modal konfirmasi (nota) --}}
    <flux:modal name="confirm-reservation" class="booking-confirm-modal">
        <div class="space-y-6">
            <div class="booking-confirm-heading">
                <div class="booking-confirm-mascot" aria-hidden="true">
                    <img src="{{ asset('assets/booking-puppy.webp') }}" alt="" width="86" height="100">
                    <span>✦</span>
                </div>
                <div>
                    <p class="booking-eyebrow">UNTUK SI PALING DISAYANG</p>
                    <flux:heading size="lg" class="!text-xl !font-bold">Periksa reservasi</flux:heading>
                    <p class="booking-confirm-subtitle">Sudah pas? Kirim ke klinik pilihanmu.</p>
                </div>
            </div>

            @php($confirmItem = $this->selectedItem)

            <div class="booking-confirm-ticket space-y-4">
                <div class="border-b border-zinc-200 pb-4">
                    <h3 class="text-lg font-bold text-zinc-900">{{ $confirmItem?->name ?? '-' }}</h3>
                    <p class="text-sm text-zinc-600">{{ $vendor->name }} &middot;
                        {{ mb_strtolower((string) $this->selectedCategory) === 'clinic' ? 'Klinik / Medis' : ($this->selectedCategory ?? '-') }}
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
                        <span class="font-medium text-zinc-700">Estimasi total</span>
                        <span class="text-xl font-bold text-accent">
                            {{ $confirmItem ? 'Rp ' . number_format($this->totalPrice, 0, ',', '.') : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <p class="booking-confirm-notice"><img src="{{ asset('assets/calendar.webp') }}" alt="" width="25" height="25" aria-hidden="true"> Klinik akan memeriksa jadwalmu sebelum mengonfirmasi reservasi.</p>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Ubah data</flux:button>
                </flux:modal.close>
                <flux:button wire:click="reserve" variant="primary" class="booking-primary" wire:loading.attr="disabled"
                    wire:target="reserve">
                    <span wire:loading.remove wire:target="reserve">Kirim permintaan reservasi</span>
                    <span wire:loading wire:target="reserve">Memproses...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
</div>
