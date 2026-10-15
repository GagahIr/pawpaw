<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorServiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_service_id',
        'name',
        'description',
        'price',
        'duration',
        'capacity',
    ];

    public function vendorService()
    {
        return $this->belongsTo(VendorService::class);
    }

    public function bookingGrooms()
    {
        return $this->hasMany(BookingGrooming::class);
    }

    public function bookingClinics()
    {
        return $this->hasMany(BookingClinic::class);
    }

    public function bookingPetHotels()
    {
        return $this->hasMany(BookingPetHotel::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('vendor', function (Builder $query) {
            if (app('vendor.context')->check()) {
                $query->whereHas('vendorService');
            }
        });
    }
}
