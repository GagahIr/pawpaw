<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserClient extends Model
{
    protected $fillable = [
        'user_id',
        'count_fake_booking',
        'google_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
