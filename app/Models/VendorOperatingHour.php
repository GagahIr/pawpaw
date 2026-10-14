<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorOperatingHour extends Model
{
    use BelongsToVendor;

    protected $fillable = [
        'vendor_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
        ];
    }
}
