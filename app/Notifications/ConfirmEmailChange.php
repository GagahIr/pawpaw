<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmEmailChange extends Notification
{
    public function __construct(public string $url) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Konfirmasi Perubahan Email PawPaw')
            ->greeting('Halo!')
            ->line('Kami menerima permintaan perubahan email akun PawPaw Anda ke alamat ini.')
            ->line('Email lama tetap aktif sampai Anda mengonfirmasi perubahan.')
            ->action('Konfirmasi Email Baru', $this->url)
            ->line('Tautan ini berlaku selama 60 menit dan hanya dapat digunakan sekali.')
            ->line('Jika Anda tidak meminta perubahan ini, abaikan email ini.')
            ->salutation('Salam hangat,')
            ->view([
                'html' => 'template_emails.confirm-email-change',
                'text' => 'template_emails.reset-password-email-text',
            ]);
    }
}
