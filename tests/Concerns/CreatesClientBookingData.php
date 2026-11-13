<?php

namespace Tests\Concerns;

use App\Enums\DayOfWeek;
use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use App\Models\CategoryService;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOperatingHour;
use App\Models\VendorService;
use App\Models\VendorServiceItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Pembuat data untuk tes menu klinik, reservasi, dan riwayat sisi client.
 */
trait CreatesClientBookingData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createClientUser(array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => 'client']);

        $user = User::factory()->create(array_replace([
            'name' => 'Amanda Dealova Putri',
            'phone_number' => fake()->unique()->numerify('08##########'),
            'role_id' => $role->id,
        ], $attributes));

        $user->userClient()->firstOrCreate();

        return $user;
    }

    /**
     * Mitra yang lolos semua syarat tampil di daftar klinik: sudah join, terverifikasi,
     * dan langganan aktif.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createVendor(array $attributes = []): Vendor
    {
        $name = $attributes['name'] ?? fake()->unique()->company();

        return Vendor::factory()->create(array_replace([
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => 'Jl. Soekarno Hatta No. 1, Malang',
            'contact' => '081200001111',
            'status_verification' => VendorVerificationStatus::Approved,
            'status_subscription' => VendorSubscriptionStatus::Active,
            'subscription_expired_at' => now()->addYear(),
            'join_at' => now()->subMonth(),
        ], $attributes));
    }

    /**
     * @param  list<array{name: string, price: int, duration: int, capacity: int|null}>|null  $items
     */
    public function addService(Vendor $vendor, string $category = 'Clinic', ?array $items = null, bool $active = true): VendorService
    {
        $service = VendorService::create([
            'vendor_id' => $vendor->id,
            'category_service_id' => CategoryService::firstOrCreate(['name' => $category])->id,
            'status_service' => $active,
        ]);

        foreach ($items ?? [['name' => 'Vaksinasi', 'price' => 150000, 'duration' => 60, 'capacity' => 1]] as $item) {
            VendorServiceItem::create($item + ['vendor_service_id' => $service->id]);
        }

        return $service->load('vendorServiceItems');
    }

    public function openEveryDay(Vendor $vendor, string $open = '08:00:00', string $close = '20:00:00'): void
    {
        foreach (DayOfWeek::cases() as $day) {
            VendorOperatingHour::create([
                'vendor_id' => $vendor->id,
                'day_of_week' => $day,
                'open_time' => $open,
                'close_time' => $close,
                'is_closed' => false,
            ]);
        }
    }

    public function closeDay(Vendor $vendor, DayOfWeek $day): void
    {
        VendorOperatingHour::where('vendor_id', $vendor->id)
            ->where('day_of_week', $day->value)
            ->update(['is_closed' => true]);
    }

    /**
     * Booking klinik atau grooming (jadwal harian dengan jam mulai dalam menit sejak 00:00).
     */
    public function createAppointment(
        User $user,
        VendorServiceItem $item,
        CarbonInterface $date,
        int $timeSlot = 600,
        string $status = 'pending',
        string $type = 'clinic',
        ?CarbonInterface $createdAt = null,
    ): BookingClinic|BookingGrooming {
        $model = $type === 'grooming' ? BookingGrooming::class : BookingClinic::class;

        $booking = $model::create([
            'user_client_id' => $user->userClient()->firstOrCreate()->id,
            'vendor_service_item_id' => $item->id,
            'booking_date' => $date->copy()->startOfDay(),
            'time_slot' => $timeSlot,
            'status' => $status,
        ]);

        return $this->stampCreatedAt($booking, $createdAt);
    }

    public function createPetHotelBooking(
        User $user,
        VendorServiceItem $item,
        CarbonInterface $start,
        CarbonInterface $end,
        string $status = 'pending',
        ?CarbonInterface $createdAt = null,
    ): BookingPetHotel {
        $booking = BookingPetHotel::create([
            'user_client_id' => $user->userClient()->firstOrCreate()->id,
            'vendor_service_item_id' => $item->id,
            'start_date' => $start->copy()->startOfDay(),
            'end_date' => $end->copy()->startOfDay(),
            'status' => $status,
        ]);

        return $this->stampCreatedAt($booking, $createdAt);
    }

    private function stampCreatedAt(BookingClinic|BookingGrooming|BookingPetHotel $booking, ?CarbonInterface $createdAt): BookingClinic|BookingGrooming|BookingPetHotel
    {
        if ($createdAt) {
            $booking->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }

        return $booking;
    }
}
