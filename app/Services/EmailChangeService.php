<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class EmailChangeService
{
    public function send(User $user, string $token): void
    {
        $url = URL::temporarySignedRoute('account.email-change.show', $user->email_change_expires_at, [
            'user' => $user->id, 'token' => $token,
        ]);
        Notification::route('mail', $user->pending_email)->notify(new ConfirmEmailChange($url));
    }
}
