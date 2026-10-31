<?php

namespace App\Actions\Admin\Booking;

use App\Models\BookingClinic;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateClinicBookingStatusAction
{
    /**
     * @throws ValidationException
     */
    public function execute(int $bookingId, int $vendorId, string $status): BookingClinic
    {
        return DB::transaction(function () use ($bookingId, $vendorId, $status): BookingClinic {
            $booking = BookingClinic::query()
                ->whereHas('vendorServiceItem.vendorService', fn ($query) => $query->where('vendor_id', $vendorId))
                ->lockForUpdate()
                ->findOrFail($bookingId);

            $allowedTransitions = [
                'pending' => ['confirmed', 'rejected'],
                'confirmed' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
                'rejected' => [],
            ];

            if (! in_array($status, array_keys($allowedTransitions), true)) {
                throw ValidationException::withMessages(['status' => 'Status booking tidak valid.']);
            }

            if (! in_array($status, $allowedTransitions[$booking->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Booking berstatus {$booking->status} tidak dapat diubah menjadi {$status}.",
                ]);
            }

            $booking->update(['status' => $status]);

            return $booking->refresh();
        });
    }
}
