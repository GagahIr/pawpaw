<?php

namespace App\Actions\Vendor;

use App\DTOs\ResetVendorPasswordData;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetVendorPasswordAction
{
    public function execute(ResetVendorPasswordData $data): void
    {
        $key = 'vendor-reset-password:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Silakan coba lagi dalam satu menit.']);
        }
        RateLimiter::hit($key, 60);

        $vendorId = $data->vendorId ?? app('vendor.context')->id();

        DB::transaction(function () use ($data, $vendorId): void {
            $status = Password::broker('vendors')->reset([
                'email' => $data->email,
                'token' => $data->token,
                'password' => $data->password,
                'deleted_at' => null,
                'vendor' => function (Builder $query) use ($vendorId): Builder {
                    return $query->whereHas('role', fn (Builder $role): Builder => $role->whereIn('name', ['owner', 'front_office']))
                        ->when($vendorId, fn (Builder $q): Builder => $q->whereHas('vendorStaff', fn (Builder $staff): Builder => $staff->where('vendor_id', $vendorId)));
                },
            ], function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
                $user->setRememberToken(Str::random(60));
                $user->save();
                DB::table('sessions')->where('user_id', $user->getAuthIdentifier())->delete();
                event(new PasswordReset($user));
            });

            if ($status !== Password::PASSWORD_RESET) {
                throw ValidationException::withMessages([
                    'email' => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
                ]);
            }
        });
    }
}
