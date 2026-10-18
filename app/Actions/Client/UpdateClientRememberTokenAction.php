<?php

namespace App\Actions\Client;

use App\Models\User;

class UpdateClientRememberTokenAction
{
    public function execute(User $user, #[\SensitiveParameter] string $token): void
    {
        if ($user->role?->name !== 'client') {
            return;
        }

        $client = $user->userClient()->firstOrCreate();
        $client->forceFill(['remember_token' => $token])->save();
        $user->setRelation('userClient', $client);
    }
}
