<?php

namespace App\Actions\Client;

use App\DTOs\SendClientPasswordResetLinkData;
use App\Models\User;
use App\Notifications\ClientResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SendClientPasswordResetLinkAction
{
    public function execute(SendClientPasswordResetLinkData $data): void
    {
        $key = 'client-forgot-password:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.']);
        }
        RateLimiter::hit($key, 60);

        Password::broker()->sendResetLink([
            'email' => $data->email, 'deleted_at' => null,
            'client' => fn (Builder $query): Builder => $query->whereHas('role', fn (Builder $role): Builder => $role->where('name', 'client')),
        ], function (User $user, string $token): void {
            try {
                $user->notify(new ClientResetPassword($token));
            } catch (\Throwable $e) {
                Password::broker()->deleteToken($user);

                throw $e;
            }
        });
    }
}
