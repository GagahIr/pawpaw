<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;

class VendorUserProvider extends EloquentUserProvider
{
    /**
     * Restore a "remember me" session only for active owner or front office accounts.
     */
    public function retrieveByToken(mixed $identifier, #[\SensitiveParameter] mixed $token): ?User
    {
        $user = parent::retrieveByToken($identifier, $token);

        return $user instanceof User
            && $user->deleted_at === null
            && in_array($user->role?->name, ['owner', 'front_office'], true)
            ? $user : null;
    }
}
