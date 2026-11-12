<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'payment_details',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'payment_details' => 'array',
        ];
    }
}
