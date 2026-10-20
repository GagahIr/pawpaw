<?php

namespace App\Actions\Client;

use App\DTOs\GoogleClientData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginGoogleClientAction
{
    public bool $registered = false;

    public function execute(GoogleClientData $data): User
    {
        $this->registered = false;

        Validator::make([
            'google_id' => $data->googleId, 'email' => $data->email, 'email_verified' => $data->emailVerified,
        ], [
            'google_id' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'email_verified' => ['accepted'],
        ], ['email_verified.accepted' => 'Email Google belum terverifikasi.'])->validate();

        $user = DB::transaction(function () use ($data): User {
            $user = User::where('google_id', $data->googleId)->lockForUpdate()->first();

            if (! $user) {
                $authoritativeEmail = str_ends_with($data->email, '@gmail.com') || filled($data->hostedDomain);
                if (! $authoritativeEmail) {
                    throw ValidationException::withMessages([
                        'identifier' => 'Gunakan akun Gmail atau Google Workspace untuk menghubungkan login Google. Anda tetap bisa masuk menggunakan password.',
                    ]);
                }

                $user = User::where('email', $data->email)->lockForUpdate()->first();
            }

            if (! $user) {
                $this->registered = true;

                return app(RegisterGoogleClientAction::class)->execute(
                    $data,
                    $data->name,
                    termsAccepted: true,
                );
            }

            if ($user->deleted_at !== null || $user->role?->name !== 'client') {
                throw ValidationException::withMessages([
                    'identifier' => 'Akun Google ini sudah terhubung dengan akun yang tidak dapat digunakan sebagai pelanggan.',
                ]);
            }

            if ($user->google_id !== null && $user->google_id !== $data->googleId) {
                throw ValidationException::withMessages([
                    'identifier' => 'Akun pelanggan ini sudah terhubung dengan akun Google lain.',
                ]);
            }

            $user->forceFill(['google_id' => $data->googleId])->save();

            return $user;
        });

        Auth::guard('client')->login($user, $data->remember);
        session()->regenerate();

        if ($data->remember) {
            Cookie::queue('pawpaw_client_identifier', $user->email, 60 * 24 * 30);
        } else {
            Cookie::queue(Cookie::forget('pawpaw_client_identifier'));
        }

        return $user;
    }
}
