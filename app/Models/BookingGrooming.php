<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingGrooming extends Model
{
    use SoftDeletes;

    protected $table = 'bookings_grooming';

    protected $fillable = [
        'user_client_id',
        'vendor_service_item_id',
        'booking_date',
        'time_slot',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'datetime',
            'time_slot' => 'integer',
        ];
    }

    public function userClient(): BelongsTo
    {
        return $this->belongsTo(UserClient::class);
    }

    public function vendorServiceItem(): BelongsTo
    {
        return $this->belongsTo(VendorServiceItem::class);
    }

    public function scopeForVendor(Builder $query, int $vendorId): Builder
    {
        return $query->whereHas('vendorServiceItem.vendorService', function (Builder $q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope('vendor', function (Builder $query): void {
            if ($vendorId = app('vendor.context')->id()) {
                $query->whereHas('vendorServiceItem.vendorService', fn (Builder $query) => $query->where('vendor_id', $vendorId));
            }
        });
    }
}
