<?php

namespace App\Actions\Account;

use App\DTOs\UpdatePasswordData;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdatePasswordAction
{
    public function execute(User $user, UpdatePasswordData $data, ?int $vendorId = null): void
    {
        $key = 'profile-password:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['currentPassword' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
        }
        RateLimiter::hit($key, 60);
        DB::transaction(function () use ($user, $data, $vendorId, $key): void {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($account->canManageProfile($vendorId), 403);
            Validator::make([
                'currentPassword' => $data->currentPassword,
                'newPassword' => $data->password, 'newPasswordConfirmation' => $data->confirmation,
            ], [
                'currentPassword' => [filled($account->password) ? 'required' : 'nullable', 'string'],
                'newPassword' => ['required', 'string', 'min:8', 'max:72', function (string $attribute, mixed $value, Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('Kata sandi terlalu panjang. Maksimal 72 byte.');
                    }
                }],
                'newPasswordConfirmation' => ['required', 'same:newPassword'],
            ], [
                'currentPassword.required' => 'Kata sandi saat ini harus diisi.',
                'currentPassword.string' => 'Kata sandi saat ini harus berupa teks.',
                'newPassword.required' => 'Kata sandi baru harus diisi.',
                'newPassword.string' => 'Kata sandi baru harus berupa teks.',
                'newPassword.max' => 'Kata sandi baru maksimal 72 karakter.',
                'newPasswordConfirmation.required' => 'Konfirmasi kata sandi baru harus diisi.',
                'newPassword.min' => 'Kata sandi baru minimal 8 karakter.',
                'newPasswordConfirmation.same' => 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.',
            ])->validate();
            if (filled($account->password)) {
                if (! Hash::check($data->currentPassword, $account->password)) {
                    throw ValidationException::withMessages(['currentPassword' => 'Kata sandi saat ini tidak sesuai.']);
                }
                if (Hash::check($data->password, $account->password)) {
                    throw ValidationException::withMessages(['newPassword' => 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.']);
                }
            }
            $account->forceFill(['password' => $data->password] + User::clearedEmailChangeAttributes())->save();
            RateLimiter::clear($key);
        });
        $user->refresh();
    }
}
