<?php

use Filament\Notifications\Notification;

if (!function_exists('notify')) {
    /**
     * @param string|null $body Pesan detail (opsional)
     * @param string $status Tipe: 'success', 'danger', 'warning', 'info'
     */
    function notify(string $title, ?string $body = null, string $status = 'success'): void
    {
        $notification = Notification::make()
            ->title($title)
            ->status($status);

        if ($body) {
            $notification->body($body);
        }

        $notification->send();
    }
}
