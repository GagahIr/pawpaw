<?php

namespace App\Models;

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'contact',
        'email',
        'description',
        'logo',
        'latitude',
        'longitude',
        'status_verification',
        'status_subscription',
        'join_at',
    ];

    public function vendorStaff()
    {
        return $this->hasMany(VendorStaff::class);
    }

    public function vendorServices()
    {
        return $this->hasMany(VendorService::class);
    }

    public function vendorOperatingHours()
    {
        return $this->hasMany(VendorOperatingHour::class);
    }

    protected function casts(): array
    {
        return [
            'status_verification' => VendorVerificationStatus::class,
            'status_subscription' => VendorSubscriptionStatus::class,
            'join_at' => 'datetime',
        ];
    }
}
