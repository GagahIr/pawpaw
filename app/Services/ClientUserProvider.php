<?php

namespace App\Services;

use App\Actions\Client\UpdateClientRememberTokenAction;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class ClientUserProvider extends EloquentUserProvider
{
    public function retrieveByToken(mixed $identifier, #[\SensitiveParameter] mixed $token): ?User
    {
        $user = parent::retrieveByToken($identifier, $token);

        return $user instanceof User && $user->deleted_at === null && $user->role?->name === 'client'
            ? $user : null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] mixed $token): void
    {
        if ($user instanceof User) {
            app(UpdateClientRememberTokenAction::class)->execute($user, $token);
        }
    }
}
