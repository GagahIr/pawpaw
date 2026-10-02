<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryService extends Model
{
    protected $fillable = [
        'name'
    ];

    public function vendorServices()
    {
        return $this->hasMany(VendorService::class);
    }
}
