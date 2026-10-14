<?php

namespace App\Models;

use App\Models\Concerns\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorService extends Model
{
    use BelongsToVendor, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'category_service_id',
        'status_service',
    ];

    public function categoryService(): BelongsTo
    {
        return $this->belongsTo(CategoryService::class);
    }

    public function vendorServiceItems(): HasMany
    {
        return $this->hasMany(VendorServiceItem::class);
    }
}
