<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class VendorScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($vendorId = app('vendor.context')->id()) {
            $builder->where($model->qualifyColumn('vendor_id'), $vendorId);
        }
    }
}
