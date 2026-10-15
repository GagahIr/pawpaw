<?php

namespace App\Models;

use App\Enums\StaffPosition;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'phone_number', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function vendorStaff(): HasMany
    {
        return $this->hasMany(VendorStaff::class);
    }

    public function userClient()
    {
        return $this->hasOne(UserClient::class);
    }

    public function staffPosition(): ?StaffPosition
    {
        return $this->vendorStaff()
            ->where('vendor_id', app('vendor.context')->id())
            ->value('position');
    }

    public function vendor()
    {
        return $this->hasOneThrough(
            Vendor::class,
            VendorStaff::class,
            'user_id',
            'id',
            'id',
            'vendor_id'
        );
    }
}
