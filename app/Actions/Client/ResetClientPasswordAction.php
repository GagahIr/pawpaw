<?php

namespace App\Actions\Client;

use App\DTOs\ResetClientPasswordData;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetClientPasswordAction
{
    public function execute(ResetClientPasswordData $data): void
    {
        $key = 'client-reset-password:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Silakan coba lagi dalam satu menit.']);
        }
        RateLimiter::hit($key, 60);

        DB::transaction(function () use ($data): void {
            $status = Password::broker()->reset([
                'email' => $data->email, 'token' => $data->token, 'password' => $data->password,
                'deleted_at' => null,
                'client' => fn (Builder $query): Builder => $query->whereHas('role', fn (Builder $role): Builder => $role->where('name', 'client')),
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
