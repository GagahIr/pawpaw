<?php

namespace App\Actions\Admin\Booking;

use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;

class GetVendorPendingBookingCountsAction
{
    /**
     * @return array{clinic: int, pet_hotel: int, grooming: int, total: int}
     */
    public function execute(?int $vendorId = null): array
    {
        $vendorId ??= app('vendor.context')->id();

        if (! $vendorId) {
            return [
                'clinic' => 0,
                'pet_hotel' => 0,
                'grooming' => 0,
                'total' => 0,
            ];
        }

        $activeStatuses = ['pending', 'confirmed'];

        $clinicCount = BookingClinic::query()
            ->forVendor($vendorId)
            ->whereIn('status', $activeStatuses)
            ->count();

        $petHotelCount = BookingPetHotel::query()
            ->forVendor($vendorId)
            ->whereIn('status', $activeStatuses)
            ->count();

        $groomingCount = BookingGrooming::query()
            ->forVendor($vendorId)
            ->whereIn('status', $activeStatuses)
            ->count();

        return [
            'clinic' => $clinicCount,
            'pet_hotel' => $petHotelCount,
            'grooming' => $groomingCount,
            'total' => $clinicCount + $petHotelCount + $groomingCount,
        ];
    }
}
