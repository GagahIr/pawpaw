<?php

namespace App\Actions\Account;

use App\DTOs\RequestEmailChangeData;
use App\Models\User;
use App\Services\EmailChangeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RequestEmailChangeAction
{
    public function __construct(private EmailChangeService $emailChangeService) {}

    public function execute(User $user, RequestEmailChangeData $data): void
    {
        $this->validatePassword($user, $data);
        $key = 'email-change-password:'.$user->id;
        DB::transaction(function () use ($user, $data, $key): void {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($account->canChangeEmail($data->vendorId), 403);
            if (blank($account->password)) {
                throw ValidationException::withMessages(['formData.email' => 'Buat kata sandi PawPaw di bagian Keamanan Akun terlebih dahulu sebelum mengganti email.']);
            }
            if ($data->password === '' || ! Hash::check($data->password, $account->password)) {
                throw ValidationException::withMessages(['emailChangePassword' => 'Kata sandi saat ini tidak sesuai.']);
            }
            $email = strtolower(trim($data->email));
            Validator::make(['formData' => ['email' => $email]], [
                'formData.email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email'), Rule::unique('vendors', 'email')],
            ], ['formData.email.unique' => 'Email sudah terdaftar.'])->validate();
            if ($account->email_change_requested_at?->gt(now()->subSeconds(60))) {
                throw ValidationException::withMessages(['formData.email' => 'Tunggu 60 detik sebelum mengirim permintaan lagi.']);
            }
            if (RateLimiter::tooManyAttempts('email-change-send:'.$account->id, 1)) {
                throw ValidationException::withMessages(['formData.email' => 'Tunggu 60 detik sebelum mengirim permintaan lagi.']);
            }
            $token = Str::random(64);
            $account->forceFill([
                'pending_email' => $email,
                'email_change_original_email' => $account->email,
                'email_change_token' => hash('sha256', $token.'|'.$account->password),
                'email_change_vendor_id' => $data->vendorId,
                'email_change_expires_at' => now()->addMinutes(60),
                'email_change_requested_at' => now(),
            ])->save();
            try {
                $this->emailChangeService->send($account, $token);
            } catch (\Throwable $exception) {
                Log::warning('Pengiriman konfirmasi perubahan email gagal.', ['exception' => $exception::class]);
                throw ValidationException::withMessages(['formData.email' => 'Email konfirmasi gagal dikirim. Silakan coba lagi.']);
            }
            RateLimiter::clear($key);
            RateLimiter::hit('email-change-send:'.$account->id, 60);
        });
        $user->refresh();
    }

    public function validatePassword(User $user, RequestEmailChangeData $data): void
    {
        $user->refresh();
        abort_unless($user->canChangeEmail($data->vendorId), 403);
        $key = 'email-change-password:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['emailChangePassword' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
        }
        if (blank($user->password)) {
            throw ValidationException::withMessages(['formData.email' => 'Buat kata sandi PawPaw di bagian Keamanan Akun terlebih dahulu sebelum mengganti email.']);
        }
        if ($data->password === '' || ! Hash::check($data->password, $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['emailChangePassword' => 'Kata sandi saat ini tidak sesuai.']);
        }
    }
}
