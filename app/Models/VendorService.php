<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorService extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'vendor_id',
        'category_service_id',
        'status_service',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function categoryService()
    {
        return $this->belongsTo(CategoryService::class);
    }

    public function vendorServiceItems()
    {
        return $this->hasMany(VendorServiceItem::class);
    }
}
