<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConfirmEmailChangeAction
{
    public function execute(int $userId, string $token): User
    {
        return DB::transaction(function () use ($userId, $token): User {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $this->ensureValid($user, $token);
            Validator::make(['email' => $user->pending_email], [
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email'), Rule::unique('vendors', 'email')],
            ], ['email.unique' => 'Email baru sudah digunakan. Silakan ajukan perubahan ke alamat lain.'])->validate();
            $user->forceFill(['email' => $user->pending_email] + User::clearedEmailChangeAttributes())->save();

            return $user;
        });
    }

    public function ensureValid(User $user, string $token): void
    {
        if (blank($user->pending_email) || blank($user->email_change_token)
            || ! hash_equals($user->email_change_token, hash('sha256', $token.'|'.$user->password))
            || $user->email_change_expires_at === null || $user->email_change_expires_at->isPast()
            || $user->email !== $user->email_change_original_email
            || ! $user->canChangeEmail($user->email_change_vendor_id)) {
            throw ValidationException::withMessages(['email' => 'Tautan tidak valid, sudah digunakan, atau kedaluwarsa. Silakan ajukan perubahan email lagi.']);
        }
    }
}
