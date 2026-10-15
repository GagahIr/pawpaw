<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientResetPassword extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.url'), '/').route('client.password.reset', [
            'token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset(),
        ], false);

        return (new MailMessage)
            ->subject('Reset Password Akun Pelanggan PawPaw')
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun pelanggan PawPaw Anda.')
            ->action('Atur Password Baru', $url)
            ->line('Tautan ini berlaku selama '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' menit dan hanya dapat digunakan sekali.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.');
    }
}
