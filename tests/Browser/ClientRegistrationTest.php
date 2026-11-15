<?php

namespace Tests\Browser;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;

class ClientRegistrationTest extends AuthBrowserTestCase
{
    /** @return array<string, string> */
    private function validData(): array
    {
        return ['name' => 'Amanda Dealova', 'username' => 'amandadealova', 'phone_number' => '081234567890',
            'email' => 'amanda@example.com', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD];
    }

    /** @param array<string, string> $overrides */
    private function fillRegistration(Browser $browser, array $overrides = [], bool $terms = true): void
    {
        $this->open($browser, '/client-register');
        foreach (array_replace($this->validData(), $overrides) as $field => $value) {
            $this->fill($browser, $field, $value);
        }
        if ($terms) {
            $this->toggle($browser, 'terms');
        }
    }

    public function test_tc_regist_001_valid_data_creates_client_and_logs_in(): void
    {
        Role::firstOrCreate(['name' => 'client']);
        $this->browse(function (Browser $browser): void {
            $this->fillRegistration($browser);
            $this->submit($browser);
            $browser->waitForLocation('/client-dashboard')->assertSee('Pendaftaran akun pelanggan berhasil');
            $user = User::where('email', 'amanda@example.com')->firstOrFail();
            $browser->assertAuthenticatedAs($user, 'client');
            $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
            $this->assertSame('client', $user->role->name);
            $this->assertDatabaseHas('user_clients', ['user_id' => $user->id]);
        });
        $this->assertDatabaseCount('users', 1);
    }

    /** @return array<string, array{array<string, string>, list<string>, bool, ?string}> */
    public static function invalidData(): array
    {
        return [
            'TC_REGIST_002 semua kosong' => [array_fill_keys(['name', 'username', 'phone_number', 'email', 'password', 'password_confirmation'], ''), ['name', 'username', 'phone_number', 'email', 'password', 'password_confirmation'], true, null],
            'TC_REGIST_003 nama kosong' => [['name' => ''], ['name'], true, null],
            'TC_REGIST_004 username kosong' => [['username' => ''], ['username'], true, null],
            'TC_REGIST_005 telepon kosong' => [['phone_number' => ''], ['phone_number'], true, null],
            'TC_REGIST_006 email kosong' => [['email' => ''], ['email'], true, null],
            'TC_REGIST_007 password kosong' => [['password' => ''], ['password'], true, null],
            'TC_REGIST_008 konfirmasi kosong' => [['password_confirmation' => ''], ['password_confirmation'], true, null],
            'TC_REGIST_009 syarat tidak disetujui' => [[], ['terms'], false, null],
            'TC_REGIST_010 nama angka simbol' => [['name' => 'Amanda123 @#$'], ['name'], true, null],
            'TC_REGIST_011 nama spasi' => [['name' => '      '], ['name'], true, null],
            'TC_REGIST_012 username duplikat' => [[], ['username'], true, 'username'],
            'TC_REGIST_013 telepon duplikat' => [[], ['phone_number'], true, 'phone_number'],
            'TC_REGIST_014 telepon huruf' => [['phone_number' => '08abc4567890'], ['phone_number'], true, null],
            'TC_REGIST_015 telepon pendek' => [['phone_number' => '08123'], ['phone_number'], true, null],
            'TC_REGIST_016 telepon panjang' => [['phone_number' => '0812345678901234'], ['phone_number'], true, null],
            'TC_REGIST_017 telepon simbol' => [['phone_number' => '0812-3456-#890'], ['phone_number'], true, null],
            'TC_REGIST_018 email duplikat' => [[], ['email'], true, 'email'],
            'TC_REGIST_019 password pendek' => [['password' => 'Abc1', 'password_confirmation' => 'Abc1'], ['password'], true, null],
            'TC_REGIST_020 konfirmasi berbeda' => [['password_confirmation' => 'Different123!'], ['password_confirmation'], true, null],
            'TC_REGIST_021 konfirmasi beda kapital' => [['password_confirmation' => 'passw0rd123!'], ['password_confirmation'], true, null],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_invalid_registration_does_not_create_account(array $overrides, array $fields, bool $terms, ?string $duplicate): void
    {
        $role = Role::firstOrCreate(['name' => 'client']);
        if ($duplicate !== null) {
            User::factory()->afterMaking(function (User $user): void {
                unset($user->email_verified_at);
            })->create([
                'name' => 'Existing Client', 'role_id' => $role->id,
                'username' => $duplicate === 'username' ? 'amandadealova' : 'existingclient',
                'email' => $duplicate === 'email' ? 'amanda@example.com' : 'existing@example.com',
                'phone_number' => $duplicate === 'phone_number' ? '081234567890' : '089999999999',
            ]);
        }
        $count = User::count();
        $this->browse(function (Browser $browser) use ($overrides, $fields, $terms): void {
            $this->fillRegistration($browser, $overrides, $terms);
            $this->submit($browser);
            $this->assertRejected($browser, $fields, '/client-register');
            $browser->assertGuest('client');
        });
        $this->assertDatabaseCount('users', $count);
        $this->assertDatabaseCount('user_clients', 0);
    }

    public function test_tc_regist_022_password_is_masked_by_default(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/client-register');
            $this->fill($browser, 'password', self::PASSWORD);
            $browser->assertAttribute($this->field('password'), 'type', 'password');
        });
    }

    public function test_tc_regist_023_eye_reveals_password(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/client-register');
            $this->fill($browser, 'password', self::PASSWORD);
            $this->togglePassword($browser, 'password');
            $browser->assertAttribute($this->field('password'), 'type', 'text')->assertInputValue($this->field('password'), self::PASSWORD);
        });
    }

    public function test_tc_regist_024_eye_hides_password_again(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/client-register');
            $this->fill($browser, 'password', self::PASSWORD);
            $this->togglePassword($browser, 'password');
            $this->togglePassword($browser, 'password');
            $browser->assertAttribute($this->field('password'), 'type', 'password')->assertInputValue($this->field('password'), self::PASSWORD);
        });
    }

    public function test_tc_regist_025_terms_checkbox_toggles(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/client-register');
            $this->assertCheckbox($browser, 'terms', false);
            $this->toggle($browser, 'terms');
            $this->assertCheckbox($browser, 'terms', true);
            $this->toggle($browser, 'terms');
            $this->assertCheckbox($browser, 'terms', false);
        });
    }

    /** @return array<string, array{string, string}> */
    public static function legalLinks(): array
    {
        return ['TC_REGIST_026 syarat' => ['Syarat & Ketentuan', 'terms'], 'TC_REGIST_027 privasi' => ['Kebijakan Privasi', 'privacy']];
    }

    #[DataProvider('legalLinks')]
    public function test_legal_link_opens_new_tab_without_losing_form(string $text, string $tab): void
    {
        $this->browse(function (Browser $browser) use ($text, $tab): void {
            $this->open($browser, '/client-register');
            $this->fill($browser, 'name', 'Amanda Dealova');
            $original = $browser->driver->getWindowHandle();
            $browser->clickLink($text)->waitUsing(8, 100, fn (): bool => count($browser->driver->getWindowHandles()) === 2);
            $handles = $browser->driver->getWindowHandles();
            $browser->driver->switchTo()->window($handles[1]);
            try {
                $browser->waitForLocation('/terms-and-privacy')->assertQueryStringHas('tab', $tab)->assertSee($text);
            } finally {
                $browser->driver->close();
                $browser->driver->switchTo()->window($original);
            }
            $browser->assertInputValue($this->field('name'), 'Amanda Dealova');
        });
    }

    /** @return array<string, array{string, string}> */
    public static function navigation(): array
    {
        return [
            'TC_REGIST_030 beranda' => ['Kembali ke Beranda', '/'],
            'TC_REGIST_031 masuk' => ['Masuk di sini', '/client-login'],
            'TC_REGIST_032 mitra' => ['Daftar sebagai Mitra', '/vendor-register'],
        ];
    }

    #[DataProvider('navigation')]
    public function test_registration_navigation_opens_expected_page(string $text, string $path): void
    {
        $this->browse(function (Browser $browser) use ($text, $path): void {
            $this->open($browser, '/client-register');
            $browser->clickLink($text)->waitForLocation($path);
        });
    }

    /** @return array<string, array{string}> */
    public static function googleCases(): array
    {
        return ['TC_REGIST_028 Google baru' => ['TC_REGIST_028'], 'TC_REGIST_029 Google terdaftar' => ['TC_REGIST_029']];
    }

    #[DataProvider('googleCases')]
    public function test_google_registration_requires_controlled_oauth_accounts(string $case): void
    {
        $this->markTestSkipped($case.': perlu akun Google uji dan OAuth eksternal; callback diuji pada ClientGoogleRegistrationTest (Feature). Bukan PASS Dusk.');
    }
}
