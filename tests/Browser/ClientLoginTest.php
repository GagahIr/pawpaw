<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;

class ClientLoginTest extends AuthBrowserTestCase
{
    /** @return array<string, array{string}> */
    public static function validIdentifiers(): array
    {
        return ['TC_LOGIN_001 email' => ['amanda@example.com'], 'TC_LOGIN_002 telepon' => ['081234567890']];
    }

    #[DataProvider('validIdentifiers')]
    public function test_valid_credentials_open_client_dashboard(string $identifier): void
    {
        $user = $this->createClient();
        $this->browse(function (Browser $browser) use ($identifier, $user): void {
            $this->open($browser, '/client-login');
            $this->fill($browser, 'identifier', $identifier);
            $this->fill($browser, 'password', self::PASSWORD);
            $this->submit($browser);
            $browser->waitForLocation('/client-dashboard')->assertAuthenticatedAs($user, 'client');
        });
        $this->assertDatabaseCount('users', 1);
    }

    /** @return array<string, array{string, string, list<string>}> */
    public static function rejectedCredentials(): array
    {
        return [
            'TC_LOGIN_003 password salah' => ['amanda@example.com', 'WrongPass123!', ['identifier']],
            'TC_LOGIN_004 email salah' => ['salah@example.com', self::PASSWORD, ['identifier']],
            'TC_LOGIN_005 keduanya salah' => ['salah@example.com', 'WrongPass123!', ['identifier']],
            'TC_LOGIN_006 email belum terdaftar' => ['baru@example.com', self::PASSWORD, ['identifier']],
            'TC_LOGIN_007 telepon belum terdaftar' => ['081111111111', self::PASSWORD, ['identifier']],
            'TC_LOGIN_008 keduanya kosong' => ['', '', ['identifier', 'password']],
            'TC_LOGIN_009 identifier kosong' => ['', self::PASSWORD, ['identifier']],
            'TC_LOGIN_010 password kosong' => ['amanda@example.com', '', ['password']],
            'TC_LOGIN_011 email tidak valid' => ['nama@', self::PASSWORD, ['identifier']],
            'TC_LOGIN_012 telepon tidak valid' => ['0812abc', self::PASSWORD, ['identifier']],
            'TC_LOGIN_013 password pendek' => ['amanda@example.com', 'Abc1', ['password']],
        ];
    }

    #[DataProvider('rejectedCredentials')]
    public function test_invalid_credentials_do_not_authenticate(string $identifier, string $password, array $fields): void
    {
        $this->createClient();
        $this->browse(function (Browser $browser) use ($identifier, $password, $fields): void {
            $this->open($browser, '/client-login');
            $this->fill($browser, 'identifier', $identifier);
            $this->fill($browser, 'password', $password);
            $this->submit($browser);
            $this->assertRejected($browser, $fields, '/client-login');
            $browser->assertGuest('client');
        });
        $this->assertDatabaseCount('users', 1);
    }

    public function test_tc_login_014_password_is_masked_by_default(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/client-login');
            $this->fill($browser, 'password', self::PASSWORD);
            $browser->assertAttribute($this->field('password'), 'type', 'password');
        });
    }

    public function test_tc_login_015_eye_displays_password(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/client-login');
            $this->fill($browser, 'password', self::PASSWORD);
            $this->togglePassword($browser, 'password');
            $browser->assertAttribute($this->field('password'), 'type', 'text')->assertInputValue($this->field('password'), self::PASSWORD);
        });
    }

    public function test_tc_login_016_remember_checkbox_toggles(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/client-login');
            $this->assertCheckbox($browser, 'remember', false);
            $this->toggle($browser, 'remember');
            $this->assertCheckbox($browser, 'remember', true);
            $this->toggle($browser, 'remember');
            $this->assertCheckbox($browser, 'remember', false);
        });
    }

    /** @return array<string, array{bool}> */
    public static function rememberChoices(): array
    {
        return ['TC_LOGIN_017 ingat sesi' => [true], 'TC_LOGIN_018 tanpa ingat sesi' => [false]];
    }

    #[DataProvider('rememberChoices')]
    public function test_browser_restart_respects_remember_choice(bool $remember): void
    {
        $user = $this->createClient();
        $this->browse(function (Browser $browser) use ($remember, $user): void {
            $this->open($browser, '/client-login');
            $this->fill($browser, 'identifier', $user->email);
            $this->fill($browser, 'password', self::PASSWORD);
            if ($remember) {
                $this->toggle($browser, 'remember');
            }
            $this->submit($browser);
            $browser->waitForLocation('/client-dashboard');
            $reopened = $this->restartBrowser($browser, route('client.dashboard'));
            if ($remember) {
                $reopened->waitForLocation('/client-dashboard')->assertAuthenticatedAs($user, 'client');
            } else {
                $reopened->waitForLocation('/client-login')->assertGuest('client');
            }
        });
    }

    /** @return array<string, array{string, string}> */
    public static function navigation(): array
    {
        return [
            'TC_LOGIN_019 lupa password' => ['Lupa Password?', '/client-forgot-password'],
            'TC_LOGIN_022 daftar' => ['Daftar di sini', '/client-register'],
            'TC_LOGIN_023 mitra klinik' => ['Mitra Klinik', '/vendor-domain'],
            'TC_LOGIN_024 bantuan FAQ' => ['Bantuan & FAQ', ''],
        ];
    }

    #[DataProvider('navigation')]
    public function test_navigation_opens_expected_page(string $link, string $path): void
    {
        $this->browse(function (Browser $browser) use ($link, $path): void {
            $this->open($browser, '/client-login');
            $browser->assertSeeLink($link)->clickLink($link);
            if ($path === '') {
                $browser->waitForText('FAQ');
            } else {
                $browser->waitForLocation($path);
            }
        });
    }

    /** @return array<string, array{string}> */
    public static function googleCases(): array
    {
        return ['TC_LOGIN_020 Google terdaftar' => ['TC_LOGIN_020'], 'TC_LOGIN_021 Google belum terdaftar' => ['TC_LOGIN_021']];
    }

    #[DataProvider('googleCases')]
    public function test_google_login_requires_controlled_oauth_accounts(string $case): void
    {
        $this->markTestSkipped($case.': perlu akun Google uji dan OAuth eksternal; callback diuji pada ClientGoogleLoginTest (Feature). Bukan PASS Dusk.');
    }

    public function test_tc_login_025_authenticated_client_cannot_reopen_login(): void
    {
        $user = $this->createClient();
        $this->browse(function (Browser $browser) use ($user): void {
            $this->open($browser, '/client-login');
            $this->fill($browser, 'identifier', $user->email);
            $this->fill($browser, 'password', self::PASSWORD);
            $this->submit($browser);
            $browser->waitForLocation('/client-dashboard')->visit('/client-login')->waitForLocation('/client-dashboard');
        });
    }
}
