<?php

namespace App\Actions\Client\Booking;

use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use App\Models\User;
use App\Models\VendorServiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        User $user,
        string $vendorSlug,
        int $vendorServiceId,
        int $vendorServiceItemId,
        string $bookingDate,
        int $timeSlot,
        ?string $endDate = null,
    ): BookingClinic|BookingGrooming|BookingPetHotel {
        return DB::transaction(function () use ($user, $vendorSlug, $vendorServiceId, $vendorServiceItemId, $bookingDate, $timeSlot, $endDate): BookingClinic|BookingGrooming|BookingPetHotel {
            $item = VendorServiceItem::query()
                ->with(['vendorService.vendor', 'vendorService.categoryService'])
                ->where('vendor_service_id', $vendorServiceId)
                ->whereHas('vendorService', fn ($query) => $query->where('status_service', true)->whereHas('vendor', fn ($query) => $query->where('slug', $vendorSlug)))
                ->lockForUpdate()
                ->findOrFail($vendorServiceItemId);
            $category = $this->category($item->vendorService->categoryService->name);
            $startDate = Carbon::parse($bookingDate)->startOfDay();
            $hotelEndDate = $endDate ? Carbon::parse($endDate)->startOfDay() : null;

            $validator = Validator::make([
                'booking_date' => $bookingDate,
                'time_slot' => $timeSlot,
                'end_date' => $endDate,
            ], [
                'booking_date' => ['required', 'date', 'after_or_equal:today'],
                'time_slot' => [$category === 'pet_hotel' ? 'nullable' : 'required', 'integer', 'min:0'],
                'end_date' => [$category === 'pet_hotel' ? 'required' : 'nullable', 'date', 'after_or_equal:booking_date'],
            ]);

            $validator->after(function ($validator) use ($category, $startDate, $hotelEndDate, $timeSlot, $item): void {
                if ($category === 'pet_hotel') {
                    $this->validatePetHotelAvailability($validator, $item, $startDate, $hotelEndDate);

                    return;
                }

                $this->validateAppointmentAvailability($validator, $item, $startDate, $timeSlot, $category);
            });
            $validator->validate();

            $attributes = [
                'user_client_id' => $user->userClient()->firstOrCreate()->id,
                'vendor_service_item_id' => $item->id,
                'status' => 'pending',
            ];

            return match ($category) {
                'clinic' => BookingClinic::create($attributes + ['booking_date' => $startDate, 'time_slot' => $timeSlot]),
                'grooming' => BookingGrooming::create($attributes + ['booking_date' => $startDate, 'time_slot' => $timeSlot]),
                'pet_hotel' => BookingPetHotel::create($attributes + ['start_date' => $startDate, 'end_date' => $hotelEndDate]),
            };
        });
    }

    private function category(string $name): string
    {
        return match (mb_strtolower(trim($name))) {
            'clinic', 'klinik' => 'clinic',
            'grooming' => 'grooming',
            'pet hotel', 'pet_hotel' => 'pet_hotel',
            default => throw ValidationException::withMessages(['vendor_service_id' => 'Kategori service tidak didukung.']),
        };
    }

    private function validateAppointmentAvailability($validator, VendorServiceItem $item, Carbon $date, int $timeSlot, string $category): void
    {
        $hour = $item->vendorService->vendor->vendorOperatingHours()->where('day_of_week', $date->dayOfWeekIso - 1)->first();
        if (! $hour || $hour->is_closed) {
            $validator->errors()->add('booking_date', 'Vendor tutup pada tanggal tersebut.');

            return;
        }

        $start = Carbon::parse($date->toDateString().' '.$hour->open_time);
        $end = Carbon::parse($date->toDateString().' '.$hour->close_time);
        $slotStart = $date->copy()->addMinutes($timeSlot);
        $slotEnd = $slotStart->copy()->addMinutes($item->duration);
        if ($slotStart->lt($start) || $slotEnd->gt($end)) {
            $validator->errors()->add('time_slot', 'Slot berada di luar jam operasional vendor.');
        }
        if ($date->isToday() && $slotStart->isPast()) {
            $validator->errors()->add('time_slot', 'Slot waktu sudah lewat.');
        }

        $bookingModel = $category === 'clinic' ? BookingClinic::class : BookingGrooming::class;
        $bookings = $bookingModel::query()
            ->where('vendor_service_item_id', $item->id)
            ->whereDate('booking_date', $date)
            ->where('time_slot', $timeSlot)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
        if ($item->capacity !== null && $bookings >= $item->capacity) {
            $validator->errors()->add('time_slot', 'Slot sudah penuh.');
        }
    }

    private function validatePetHotelAvailability($validator, VendorServiceItem $item, Carbon $startDate, ?Carbon $endDate): void
    {
        if (! $endDate) {
            return;
        }

        $bookings = BookingPetHotel::query()
            ->where('vendor_service_item_id', $item->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->count();
        if ($item->capacity !== null && $bookings >= $item->capacity) {
            $validator->errors()->add('end_date', 'Kapasitas pet hotel penuh pada rentang tanggal tersebut.');
        }
    }
}
