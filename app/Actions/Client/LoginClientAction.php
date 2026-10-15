<?php

namespace App\Actions\Client;

use App\DTOs\LoginClientData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginClientAction
{
    public function execute(LoginClientData $data): User
    {
        $key = 'client-login:'.hash('sha256', $data->identifier.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'identifier' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $field = str_contains($data->identifier, '@') ? 'email' : 'phone_number';
        $authenticated = Auth::attemptWhen(
            [$field => $data->identifier, 'password' => $data->password, 'deleted_at' => null],
            fn (User $user): bool => $user->role?->name === 'client',
            $data->remember,
        );

        if (! $authenticated) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'identifier' => 'Email/nomor telepon atau password tidak sesuai dengan akun pelanggan.',
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        if ($data->remember) {
            Cookie::queue('pawpaw_client_identifier', $data->identifier, 60 * 24 * 30);
        } else {
            Cookie::queue(Cookie::forget('pawpaw_client_identifier'));
        }

        return Auth::user();
    }
}
