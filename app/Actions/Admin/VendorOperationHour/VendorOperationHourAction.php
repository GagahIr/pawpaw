<?php

namespace App\Actions\Admin\VendorOperationHour;

use App\Models\VendorOperatingHour;
use Illuminate\Support\Facades\DB;

class VendorOperationHourAction
{
    public function execute(int $vendorId, array $hours): void
    {
        DB::transaction(function () use ($vendorId, $hours) {
            foreach ($hours as $hour) {
                VendorOperatingHour::updateOrCreate(
                    [
                        'vendor_id' => $vendorId,
                        'day_of_week' => $hour['day_of_week'],
                    ],
                    [
                        'open_time' => $hour['is_closed'] ? null : $hour['open_time'],
                        'close_time' => $hour['is_closed'] ? null : $hour['close_time'],
                        'is_closed' => $hour['is_closed'],
                    ]
                );
            }
        });
    }
}
