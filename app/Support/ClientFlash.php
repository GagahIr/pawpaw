<?php

namespace App\Support;

final class ClientFlash
{
    public static function loginSuccess(string $message): void
    {
        session()->flash('client_logged_in', $message);
    }

    public static function registrationSuccess(string $message): void
    {
        session()->flash('client_registered', $message);
    }

    public static function loginError(string $message): void
    {
        session()->flash('client_login_error', $message);
    }

    public static function registrationError(string $message): void
    {
        session()->flash('client_register_error', $message);
    }
}
