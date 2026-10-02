<?php

namespace App\Models;

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
        'capacity'
    ];

    public function vendorService()
    {
        return $this->belongsTo(VendorService::class);
    }
}
