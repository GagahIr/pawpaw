<?php

namespace App\Console\Commands;

use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:mark-completed-bookings')]
#[Description('Mark confirmed bookings as completed jika sudah 48+ jam lewat jadwal')]
class MarkCompletedBookings extends Command
{
    public function handle()
    {
        $cutoff = now()->subHours(48);

        $clinicCount = 0;
        BookingClinic::query()
            ->where('status', 'confirmed')
            ->whereRaw("booking_date + (time_slot || ' minutes')::interval < ?", [$cutoff])
            ->each(function ($booking) use (&$clinicCount) {
                $booking->update(['status' => 'completed']);
                $clinicCount++;
            });

        $groomingCount = 0;
        BookingGrooming::query()
            ->where('status', 'confirmed')
            ->whereRaw("booking_date + (time_slot || ' minutes')::interval < ?", [$cutoff])
            ->each(function ($booking) use (&$groomingCount) {
                $booking->update(['status' => 'completed']);
                $groomingCount++;
            });

        $hotelCount = 0;
        BookingPetHotel::query()
            ->where('status', 'confirmed')
            ->where('start_date', '<', $cutoff)
            ->each(function ($booking) use (&$hotelCount) {
                $booking->update(['status' => 'completed']);
                $hotelCount++;
            });

        $total = $clinicCount + $groomingCount + $hotelCount;

        $this->info("Marked {$total} bookings as completed (Clinic: {$clinicCount}, Grooming: {$groomingCount}, PetHotel: {$hotelCount})");
    }
}
