<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientToastTest extends TestCase
{
    public static function flashMessages(): array
    {
        return [
            'registration success' => ['/', 'client_registered', 'Pendaftaran berhasil.', 'success'],
            'login success' => ['/', 'client_logged_in', 'Login berhasil.', 'success'],
            'password reset success' => ['/client-login', 'client_password_reset_success', 'Password diperbarui.', 'success'],
            'google login error' => ['/client-login', 'client_login_error', 'Login Google dibatalkan.', 'danger'],
            'google registration error' => ['/client-register', 'client_register_error', 'Registrasi Google dibatalkan.', 'danger'],
        ];
    }

    #[DataProvider('flashMessages')]
    public function test_redirect_messages_use_vendor_toast_format(string $path, string $key, string $message, string $variant): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

        $this->withoutVite()->withSession([$key => $message])->get($path)
            ->assertOk()
            ->assertSee('position="top end"', false)
            ->assertSee('data-flux-toast-dialog', false)
            ->assertSee('$flux.toast(', false)
            ->assertSee($message)
            ->assertSee("variant: '{$variant}'", false)
            ->assertDontSee('client-registration-notification');
    }
}
