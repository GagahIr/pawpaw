<?php

namespace App\Models;

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

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
        'subscription_started_at',
        'subscription_expired_at',
        'current_plan',
        'last_payment_date',
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

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    protected function casts(): array
    {
        return [
            'status_verification' => VendorVerificationStatus::class,
            'status_subscription' => VendorSubscriptionStatus::class,
            'subscription_started_at' => 'datetime',
            'subscription_expired_at' => 'datetime',
            'last_payment_date' => 'datetime',
            'join_at' => 'datetime',
        ];
    }
}
