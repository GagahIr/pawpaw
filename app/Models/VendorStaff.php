<?php

namespace App\Models;

use App\Enums\StaffPosition;
use Illuminate\Database\Eloquent\Model;

class VendorStaff extends Model
{
    protected $fillable = [
        'user_id',
        'vendor_id',
        'position'
    ];

    protected function casts(): array
    {
        return ['position' => StaffPosition::class];
    }

    public function vendor()
    {
       return $this->belongsTo(Vendor::class);
    }

    // public function user()
    // {
    //     $this->hasOne(User::class);
    // }
}
