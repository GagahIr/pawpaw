<?php

use App\Actions\Client\Booking\BookingAction;
use App\Models\Vendor;
use App\Models\VendorServiceItem;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
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

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $this->bookingDate = now()->toDateString();
    }

    public function maxBookingDate(): string
    {
        $days = $this->isPetHotel() ? self::MAX_ADVANCE_DAYS_PET_HOTEL : self::MAX_ADVANCE_DAYS;

        return now()->addDays($days)->toDateString();
    }

    public function maxEndDate(): string
    {
        return Carbon::parse($this->bookingDate ?: now())
            ->addDays(self::MAX_STAY_NIGHTS)
            ->toDateString();
    }

    public function updatedBookingDate(): void
    {
        $this->timeSlot = '';

        if ($this->endDate !== '' && $this->endDate < $this->bookingDate) {
            $this->endDate = '';
        }
    }

    public function updatedVendorServiceItemId(): void
    {
        $this->timeSlot = '';
    }

    public function selectService(int|string $serviceId): void
    {
        $this->vendorServiceId = (string) $serviceId;
        $this->vendorServiceItemId = '';
        $this->bookingDate = now()->toDateString();
        $this->endDate = '';
        $this->timeSlot = '';
    }

    public function selectedCategory(): ?string
    {
        return $this->vendor()->vendorServices->firstWhere('id', (int) $this->vendorServiceId)?->categoryService?->name;
    }

    public function isPetHotel(): bool
    {
        return mb_strtolower((string) $this->selectedCategory()) === 'pet hotel';
    }

    private function reservationRules(bool $isPetHotel): array
    {
        return [
            'vendorServiceId' => ['required', 'integer'],
            'vendorServiceItemId' => ['required', 'integer'],
            'bookingDate' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $this->maxBookingDate()],
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

                    // Cek ulang di server: slot harus masih tersedia dan belum lewat.
                    if (!array_key_exists((int) $value, $this->slotOptions())) {
                        $fail('Jam yang dipilih sudah tidak tersedia. Silakan pilih jam lain.');
                    }
                },
            ],
        ];
    }

    /**
     * Validasi form, lalu tampilkan modal konfirmasi (belum menyimpan apa pun).
     */
    public function confirmReservation(): void
    {
        try {
            $this->validate($this->reservationRules($this->isPetHotel()));
        } catch (ValidationException $exception) {
            Flux::toast($exception->validator->errors()->first(), variant: 'danger');
            throw $exception;
        }

        $this->modal('confirm-reservation')->show();
    }

    public function reserve(BookingAction $bookingAction)
    {
        $isPetHotel = $this->isPetHotel();

        try {
            $this->validate($this->reservationRules($isPetHotel));

            $bookingAction->execute(auth('client')->user(), $this->slug, (int) $this->vendorServiceId, (int) $this->vendorServiceItemId, $this->bookingDate, (int) $this->timeSlot, $isPetHotel ? $this->endDate : null);
        } catch (ValidationException $exception) {
            $this->modal('confirm-reservation')->close();
            Flux::toast($exception->validator->errors()->first(), variant: 'danger');
            throw $exception;
        }

        Flux::toast('Reservasi berhasil dibuat. Menunggu konfirmasi vendor.', variant: 'success');

        return $this->redirectRoute('client.vendor.detail', ['slug' => $this->slug], navigate: true);
    }

    public function slotOptions(): array
    {
        if ($this->isPetHotel() || !$this->vendorServiceItemId || !$this->bookingDate) {
            return [];
        }

        $vendor = $this->vendor();
        $service = $vendor->vendorServices->firstWhere('id', (int) $this->vendorServiceId);
        $item = $service?->vendorServiceItems->firstWhere('id', (int) $this->vendorServiceItemId);
        $dayOfWeek = Carbon::parse($this->bookingDate)->dayOfWeekIso - 1;
        $hour = $vendor->vendorOperatingHours->first(fn($hour) => $hour->day_of_week->value === $dayOfWeek);

        if (!$item || !$hour || $hour->is_closed) {
            return [];
        }

        $start = Carbon::parse($this->bookingDate . ' ' . $hour->open_time);
        $end = Carbon::parse($this->bookingDate . ' ' . $hour->close_time);
        $slots = [];

        $earliest = now()->addMinutes(self::MIN_LEAD_MINUTES);

        for ($slot = $start->copy(); $slot->copy()->addMinutes($item->duration)->lte($end); $slot->addMinutes(30)) {
            if ($slot->lt($earliest)) {
                continue;
            }
            $slots[$slot->hour * 60 + $slot->minute] = $slot->format('H:i');
        }

        return $slots;
    }

    public function selectedItem(): ?VendorServiceItem
    {
        return $this->vendor()->vendorServices->firstWhere('id', (int) $this->vendorServiceId)?->vendorServiceItems->firstWhere('id', (int) $this->vendorServiceItemId);
    }

    public function with(): array
    {
        return ['vendor' => $this->vendor()];
    }

    private function vendor(): Vendor
    {
        return Vendor::query()
            ->with([
                'vendorServices' => fn($query) => $query->where('status_service', true)->with(['categoryService', 'vendorServiceItems']),
                'vendorOperatingHours',
            ])
            ->where('slug', $this->slug)
            ->whereNotNull('join_at')
            ->firstOrFail();
    }
};
?>

<div class="mx-auto max-w-2xl py-10">
    <a href="{{ route('client.vendor.detail', $vendor->slug) }}" wire:navigate
        class="text-sm text-[#1a5c40] hover:underline">
        ← Kembali ke {{ $vendor->name }}
    </a>

    <flux:card class="mt-5 space-y-6">
        <div>
            <flux:heading size="xl">Reservasi {{ $vendor->name }}</flux:heading>
            <flux:subheading>{{ $vendor->name }} · {{ $vendor->address }}</flux:subheading>
        </div>

        <form wire:submit="confirmReservation" class="space-y-5">
            <flux:field>
                <flux:label>Pilih service</flux:label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($vendor->vendorServices as $service)
                        <flux:button type="button" wire:click="selectService({{ $service->id }})"
                            variant="{{ (string) $service->id === $vendorServiceId ? 'primary' : 'outline' }}"
                            size="sm">
                            {{ $service->categoryService->name }}
                        </flux:button>
                    @endforeach
                </div>
                <flux:error name="vendorServiceId" />
            </flux:field>

            <flux:field>
                <flux:label>Pilih layanan</flux:label>
                <flux:select wire:model.live="vendorServiceItemId" class="w-full rounded-lg border-zinc-300"
                    :disabled="!$vendorServiceId">
                    <flux:select.option value="">Pilih layanan</flux:select.option>
                    @if ($selectedService = $vendor->vendorServices->firstWhere('id', (int) $vendorServiceId))
                        @foreach ($selectedService->vendorServiceItems as $item)
                            <flux:select.option value="{{ $item->id }}">{{ $item->name }} — Rp
                                {{ number_format($item->price, 0, ',', '.') }} ({{ $item->duration }} menit)
                            </flux:select.option>
                        @endforeach
                    @endif
                </flux:select>
                <flux:error name="vendorServiceItemId" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:input wire:model.live="bookingDate" type="date"
                        label="{{ $this->isPetHotel() ? 'Tanggal mulai' : 'Tanggal' }}"
                        min="{{ now()->toDateString() }}" max="{{ $this->maxBookingDate() }}" />
                    <flux:error name="bookingDate" />
                </div>

                @if ($this->isPetHotel())
                    <div>
                        <flux:input wire:model.live="endDate" type="date" label="Tanggal selesai"
                            min="{{ $bookingDate ?: now()->toDateString() }}"
                            max="{{ $this->maxEndDate() }}" />
                        <flux:error name="endDate" />
                    </div>
                @else
                    <flux:field>
                        <flux:label>Jam kunjungan</flux:label>
                        <flux:select wire:model="timeSlot" class="w-full rounded-lg border-zinc-300"
                            :disabled="!$vendorServiceItemId">
                            <flux:select.option value="">Pilih jam</flux:select.option>
                            @foreach ($this->slotOptions() as $slot => $label)
                                <flux:select.option value="{{ $slot }}">{{ $label }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @if ($vendorServiceItemId && $this->slotOptions() === [])
                            <flux:description>Tidak ada slot tersedia pada tanggal ini.</flux:description>
                        @endif
                        <flux:error name="timeSlot" />
                    </flux:field>
                @endif
            </div>

            <flux:button type="submit" variant="primary" class="w-full !bg-[#1a5c40]" wire:loading.attr="disabled"
                wire:target="confirmReservation">Buat Reservasi</flux:button>
        </form>
    </flux:card>

    <!-- Confirm Reservation Modal -->
    <flux:modal name="confirm-reservation" class="min-w-[22rem] md:min-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Konfirmasi reservasi</flux:heading>
                <flux:text class="mt-2">Pastikan detail reservasi berikut sudah benar sebelum dikirim.
                </flux:text>
            </div>

            @php($confirmItem = $this->selectedItem())

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500">Vendor</dt>
                    <dd class="text-right font-medium">{{ $vendor->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500">Service</dt>
                    <dd class="text-right font-medium">{{ $this->selectedCategory() ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500">Layanan</dt>
                    <dd class="text-right font-medium">{{ $confirmItem?->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500">Harga</dt>
                    <dd class="text-right font-medium">
                        {{ $confirmItem ? 'Rp ' . number_format($confirmItem->price, 0, ',', '.') : '-' }}</dd>
                </div>

                @if ($this->isPetHotel())
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">Tanggal mulai</dt>
                        <dd class="text-right font-medium">
                            {{ $bookingDate ? \Carbon\Carbon::parse($bookingDate)->translatedFormat('d M Y') : '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">Tanggal selesai</dt>
                        <dd class="text-right font-medium">
                            {{ $endDate ? \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') : '-' }}</dd>
                    </div>
                @else
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">Tanggal</dt>
                        <dd class="text-right font-medium">
                            {{ $bookingDate ? \Carbon\Carbon::parse($bookingDate)->translatedFormat('d M Y') : '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">Jam</dt>
                        <dd class="text-right font-medium">
                            {{ $timeSlot !== '' ? $this->slotOptions()[(int) $timeSlot] ?? '-' : '-' }}</dd>
                    </div>
                @endif
            </dl>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Periksa lagi</flux:button>
                </flux:modal.close>
                <flux:button wire:click="reserve" variant="primary" class="!bg-[#1a5c40]" wire:loading.attr="disabled"
                    wire:target="reserve">
                    Ya, Buat Reservasi
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
