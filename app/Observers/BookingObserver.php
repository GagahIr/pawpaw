<?php

namespace App\Observers;

use App\Models\UserClient;

class BookingObserver
{
    /**
     * Handle any booking model "updated" event.
     */
    public function updated($booking): void
    {
        $this->checkNoShow($booking);
    }

    /**
     * Check if booking status changed to no_show and increment fake_booking count.
     */
    private function checkNoShow($booking): void
    {
        // Get original status (before save)
        $originalStatus = $booking->getOriginal('status');

        // Only increment when status changed to 'no_show' and was 'confirmed'
        if ($booking->status === 'no_show' && $originalStatus === 'confirmed') {
            UserClient::where('id', $booking->user_client_id)->increment('count_fake_booking');
        }
    }
}
