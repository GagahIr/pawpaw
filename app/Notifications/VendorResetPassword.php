<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorResetPassword extends Notification
{
    public function __construct(
        public string $token,
        public ?Vendor $vendor = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendor = $this->vendor ?? app('vendor.context')->get() ?? $notifiable->vendorStaff()->with('vendor')->first()?->vendor;
        $vendorSlug = $vendor?->slug;

        $url = route('vendor.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'subdomain' => $vendorSlug,
        ]);

        $vendorName = $vendor?->name ? ' ('.$vendor->name.')' : '';

        return (new MailMessage)
            ->subject('Reset Password Akun Mitra PawPaw')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun mitra PawPaw Anda'.$vendorName.'.')
            ->action('Atur Password Baru', $url)
            ->line('Tautan ini berlaku selama '.config('auth.passwords.vendors.expire', 60).' menit dan hanya dapat digunakan sekali.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini. Password akun Anda tetap aman dan tidak berubah.')
            ->salutation('Salam hangat,')
            ->view([
                'html' => 'template_emails.vendor-reset-password-email',
                'text' => 'template_emails.vendor-reset-password-email-text',
            ]);
    }
}
