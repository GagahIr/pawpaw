<?php

namespace App\Actions\Vendor;

use App\DTOs\SendVendorPasswordResetLinkData;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\VendorResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SendVendorPasswordResetLinkAction
{
    public function execute(SendVendorPasswordResetLinkData $data): void
    {
        $key = 'vendor-forgot-password:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.']);
        }
        RateLimiter::hit($key, 60);

        $vendorId = $data->vendorId ?? app('vendor.context')->id();
        $vendor = $vendorId ? Vendor::find($vendorId) : app('vendor.context')->get();

        Password::broker('vendors')->sendResetLink([
            'email' => $data->email,
            'deleted_at' => null,
            'vendor' => function (Builder $query) use ($vendorId): Builder {
                return $query->whereHas('role', fn (Builder $role): Builder => $role->whereIn('name', ['owner', 'front_office']))
                    ->when($vendorId, fn (Builder $q): Builder => $q->whereHas('vendorStaff', fn (Builder $staff): Builder => $staff->where('vendor_id', $vendorId)));
            },
        ], function (User $user, string $token) use ($vendor, $vendorId): void {
            try {
                $targetVendor = $vendor ?? ($vendorId ? Vendor::find($vendorId) : null) ?? app('vendor.context')->get();
                $user->notify(new VendorResetPassword($token, $targetVendor));
            } catch (\Throwable $e) {
                Password::broker('vendors')->deleteToken($user);

                throw $e;
            }
        });
    }
}
