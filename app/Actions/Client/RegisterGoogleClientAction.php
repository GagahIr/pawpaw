<?php

namespace App\Actions\Client;

use App\DTOs\GoogleClientData;
use App\DTOs\RegisterClientData;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterGoogleClientAction
{
    public function execute(GoogleClientData $data, string $name = '', bool $termsAccepted = false): User
    {
        Validator::make([
            'google_id' => $data->googleId, 'email' => $data->email, 'email_verified' => $data->emailVerified,
            'terms' => $termsAccepted,
        ], [
            'google_id' => ['required', 'string', 'max:255', 'unique:users,google_id'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:vendors,email'],
            'email_verified' => ['accepted'],
            'terms' => ['accepted'],
        ], [
            'google_id.unique' => 'Akun Google ini sudah terdaftar. Silakan masuk melalui halaman login.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan akun Google lain.',
            'email_verified.accepted' => 'Email Google belum terverifikasi.',
            'terms.accepted' => 'Anda harus menyetujui Syarat & Ketentuan serta Kebijakan Privasi.',
        ])->validate();

        if (! str_ends_with($data->email, '@gmail.com') && blank($data->hostedDomain)) {
            throw ValidationException::withMessages([
                'email' => 'Gunakan akun Gmail atau Google Workspace untuk registrasi Google.',
            ]);
        }

        $base = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', Str::ascii(Str::before($data->email, '@'))));
        $base = substr(str_pad($base, 5, '_'), 0, 39);
        $username = $base;
        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.strtolower(Str::random(10));
        }

        $name = mb_substr(trim($name), 0, 100);

        return app(RegisterClientAction::class)->execute(new RegisterClientData(
            name: $name !== '' ? $name : Str::before($data->email, '@'),
            username: $username, phoneNumber: null, email: $data->email, password: null,
            googleId: $data->googleId,
        ));
    }
}
