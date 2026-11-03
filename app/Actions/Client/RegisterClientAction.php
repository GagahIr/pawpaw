<?php

namespace App\Actions\Client;

use App\DTOs\RegisterClientData;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterClientAction
{
    public function recordRegistrationAttempt(string $ipAddress): void
    {
        $key = 'client-register:'.hash('sha256', $ipAddress);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan pendaftaran. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }
        RateLimiter::hit($key, 60);
    }

    public function execute(RegisterClientData $data): User
    {
        try {
            $user = DB::transaction(function () use ($data): User {
                $this->validateUniqueIdentifiers($data);
                $role = Role::where('name', 'client')->firstOrFail();

                $user = User::create([
                    'name' => $data->name,
                    'username' => $data->username,
                    'phone_number' => $data->phoneNumber,
                    'email' => $data->email,
                    'password' => $data->password,
                    'role_id' => $role->id,
                ]);

                UserClient::create(['user_id' => $user->id, 'google_id' => $data->googleId]);

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->validateUniqueIdentifiers($data);

            throw $exception;
        }

        Auth::guard('client')->login($user);
        session()->regenerate();

        return $user;
    }

    private function validateUniqueIdentifiers(RegisterClientData $data): void
    {
        Validator::make([
            'username' => $data->username, 'email' => $data->email, 'phone_number' => $data->phoneNumber,
        ], [
            'username' => ['unique:users,username'],
            'email' => ['unique:users,email', 'unique:vendors,email'],
            'phone_number' => ['nullable', 'unique:users,phone_number', 'unique:vendors,contact'],
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'email.unique' => 'Email sudah digunakan.',
            'phone_number.unique' => 'Nomor telepon sudah digunakan.',
        ])->validate();
    }
}
