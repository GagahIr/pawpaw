<?php

namespace App\Actions\Client\ProfileClient;

use App\Models\User;

class ProfileClientAction
{
    public function execute(int $userId, array $data): User
    {
        try {
            $user = User::findOrFail($userId);

            $user->update($data);

            return $user;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update client profile: ' . $e->getMessage());
        }
    }
}

