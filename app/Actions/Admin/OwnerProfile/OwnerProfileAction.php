<?php

namespace App\Actions\Admin\OwnerProfile;

use App\Models\User;

class OwnerProfileAction
{
    /**
     * @param  array{name: string, username: ?string, email: string, phone_number: string, avatar?: string}  $data
     */
    public function execute(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }
}
