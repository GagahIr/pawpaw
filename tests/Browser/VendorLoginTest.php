<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;

class VendorLoginTest extends AuthBrowserTestCase
{
    public function test_tc_mitra_login_001_valid_owner_opens_dashboard(): void
    {
        [$vendor, $owner] = $this->createMitra();
        $this->browse(function (Browser $browser) use ($vendor, $owner): void {
            $this->open($browser, $this->mitraUrl($vendor));
            $this->fill($browser, 'identifier', $owner->email);
            $this->fill($browser, 'password', self::PASSWORD);
            $this->submit($browser);
            $browser->waitForLocation('/admin');
            $this->assertBrowserUser($browser, $owner, 'vendor');
        });
    }

    /** @return array<string, array{string, string, list<string>}> */
    public static function invalidCredentials(): array
    {
        return [
            'TC_MITRA_LOGIN_002 password salah' => ['owner@example.com', 'WrongPass123!', ['identifier']],
            'TC_MITRA_LOGIN_003 email belum terdaftar' => ['unknown@example.com', self::PASSWORD, ['identifier']],
            'TC_MITRA_LOGIN_004 semua kosong' => ['', '', ['identifier', 'password']],
        ];
    }

    #[DataProvider('invalidCredentials')]
    public function test_invalid_owner_credentials_are_rejected(string $identifier, string $password, array $fields): void
    {
        [$vendor] = $this->createMitra();
        $this->browse(function (Browser $browser) use ($vendor, $identifier, $password, $fields): void {
            $this->open($browser, $this->mitraUrl($vendor));
            $this->fill($browser, 'identifier', $identifier);
            $this->fill($browser, 'password', $password);
            $this->submit($browser);
            $this->assertRejected($browser, $fields, '/login');
            $this->assertBrowserUser($browser, null, 'vendor');
        });
        $this->assertDatabaseCount('users', 1);
    }

    public function test_tc_mitra_login_005_remember_restores_owner_in_new_browser(): void
    {
        [$vendor, $owner] = $this->createMitra();
        $this->browse(function (Browser $browser) use ($vendor, $owner): void {
            $this->open($browser, $this->mitraUrl($vendor));
            $this->fill($browser, 'identifier', $owner->email);
            $this->fill($browser, 'password', self::PASSWORD);
            $this->toggle($browser, 'remember');
            $this->submit($browser);
            $browser->waitForLocation('/admin');
            $this->assertBrowserUser($browser, $owner, 'vendor');
            $reopened = $this->restartBrowser($browser, $this->mitraUrl($vendor, 'vendor.admin.dashboard'));
            $reopened->waitForLocation('/admin');
            $this->assertBrowserUser($reopened, $owner, 'vendor');
        });
    }

    /** @return array<string, array{string, string}> */
    public static function navigation(): array
    {
        return [
            'TC_MITRA_LOGIN_006 a lupa password' => ['Lupa password?', '/forgot-password'],
            'TC_MITRA_LOGIN_006 b daftar mitra' => ['Daftar sebagai Mitra', '/vendor-register'],
        ];
    }

    #[DataProvider('navigation')]
    public function test_owner_login_links_open_expected_page(string $text, string $path): void
    {
        [$vendor] = $this->createMitra();
        $this->browse(function (Browser $browser) use ($vendor, $text, $path): void {
            $this->open($browser, $this->mitraUrl($vendor));
            $browser->assertSeeLink($text)->clickLink($text)->waitForLocation($path);
        });
    }
}
