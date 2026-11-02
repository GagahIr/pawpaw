<?php

namespace App\Models;

use App\Enums\StaffPosition;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'phone_number', 'role_id', 'avatar'])]
#[Hidden(['password', 'remember_token', 'email_change_token', 'email_change_original_email'])]
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
            'email_change_expires_at' => 'datetime',
            'email_change_requested_at' => 'datetime',
            'email_change_vendor_id' => 'integer',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function canChangeEmail(?int $vendorId = null): bool
    {
        return $this->role?->name !== 'front_office' && $this->canManageProfile($vendorId);
    }

    public function canManageProfile(?int $vendorId = null): bool
    {
        if ($this->deleted_at !== null) {
            return false;
        }

        if ($this->role?->name === 'client') {
            return $vendorId === null;
        }

        return in_array($this->role?->name, ['owner', 'front_office'], true) && $vendorId !== null
            && $this->vendorStaff()->withoutGlobalScopes()
                ->where('vendor_id', $vendorId)
                ->where('position', $this->role->name)
                ->exists();
    }

    /** @return array<string, null> */
    public static function clearedEmailChangeAttributes(): array
    {
        return array_fill_keys([
            'pending_email', 'email_change_original_email', 'email_change_token',
            'email_change_vendor_id', 'email_change_expires_at', 'email_change_requested_at',
        ], null);
    }

    public function vendorStaff(): HasMany
    {
        return $this->hasMany(VendorStaff::class);
    }

    public function userClient(): HasOne
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
