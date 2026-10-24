<?php

namespace App\Models;

use App\Enums\StaffPosition;
use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorStaff extends Model
{
    use HasFactory, BelongsToVendor;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'position',
    ];

    protected function casts(): array
    {
        return ['position' => StaffPosition::class];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
