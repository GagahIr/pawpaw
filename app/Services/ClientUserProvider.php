<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;

class ClientUserProvider extends EloquentUserProvider
{
    public function retrieveByToken(mixed $identifier, #[\SensitiveParameter] mixed $token): ?User
    {
        $user = parent::retrieveByToken($identifier, $token);

        return $user instanceof User && $user->deleted_at === null && $user->role?->name === 'client'
            ? $user : null;
    }
}
