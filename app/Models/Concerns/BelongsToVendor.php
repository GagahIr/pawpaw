<?php

namespace App\Models\Concerns;

use App\Models\Scopes\VendorScope;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToVendor
{
    protected static function bootBelongsToVendor(): void
    {
        static::addGlobalScope(new VendorScope);

        static::creating(function (Model $model) {
            if (! $model->vendor_id && $vendorId = app('vendor.context')->id()) {
                $model->vendor_id = $vendorId;
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
