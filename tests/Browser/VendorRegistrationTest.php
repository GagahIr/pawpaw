<?php

namespace Tests\Browser;

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;

class VendorRegistrationTest extends AuthBrowserTestCase
{
    /** @return array<string, string> */
    private function mitraData(): array
    {
        return ['vendor_name' => 'Abel Grooming', 'contact' => '081216178909', 'email_vendor' => 'amanabiss@gmail.com',
            'address' => 'Jalan Gajahmada No. 42, RT 03/RW 02, Malang', 'description' => 'Salon grooming premium & penitipan kucing anjing higienis ber-AC.'];
    }

    /** @return array<string, string> */
    private function ownerData(): array
    {
        return ['owner_name' => 'Amanda Bella Safira', 'phone_number' => '08292417484', 'email' => 'amandaaaa@gmail.com', 'password' => self::PASSWORD];
    }

    private function stepSelector(int $step): string
    {
        return '[x-show="step === '.$step.'"].space-y-5';
    }

    private function waitForStep(Browser $browser, int $step): void
    {
        $selector = json_encode($this->stepSelector($step), JSON_THROW_ON_ERROR);
        $browser->waitUntil('getComputedStyle(document.querySelector('.$selector.')).display !== "none"');
    }

    /** @param array<string, string> $overrides */
    private function fillMitra(Browser $browser, array $overrides = []): void
    {
        $this->open($browser, '/vendor-register');
        foreach (array_replace($this->mitraData(), $overrides) as $field => $value) {
            $this->fill($browser, $field, $value);
        }
    }

    /** @param array<string, string> $overrides */
    private function fillOwner(Browser $browser, array $overrides = []): void
    {
        $this->waitForStep($browser, 2);
        foreach (array_replace($this->ownerData(), $overrides) as $field => $value) {
            $this->fill($browser, $field, $value);
        }
    }

    private function next(Browser $browser, int $step): void
    {
        $browser->press('Selanjutnya');
        $this->waitForStep($browser, $step);
    }

    private function openOwner(Browser $browser): void
    {
        $this->fillMitra($browser);
        $this->next($browser, 2);
    }

    private function openConfirmation(Browser $browser): void
    {
        $this->openOwner($browser);
        $this->fillOwner($browser);
        $this->next($browser, 3);
        $this->toggle($browser, 'agreePaymentTerms');
        $this->next($browser, 4);
    }

    public function test_tc_reg_mitra_001_registration_navigation(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->open($browser, '/');
            $browser->clickLink('Daftar Mitra')->waitForLocation('/vendor-register')->assertSee('Pendaftaran Mitra');
            $browser->clickLink('Kembali ke Beranda')->waitForLocation('/');
            $this->open($browser, '/vendor-register');
            $browser->clickLink('Masuk melalui domain klinik Anda')->waitForLocation('/vendor-domain');
        });
    }

    /** @return array<string, array{array<string, string>, list<string>, ?string}> */
    public static function invalidMitra(): array
    {
        return [
            'TC_REG_MITRA_002 a semua wajib kosong' => [array_fill_keys(['vendor_name', 'contact', 'email_vendor', 'address'], ''), ['vendor_name', 'contact', 'email_vendor', 'address'], null],
            'TC_REG_MITRA_002 b nama kosong' => [['vendor_name' => ''], ['vendor_name'], null],
            'TC_REG_MITRA_002 c kontak kosong' => [['contact' => ''], ['contact'], null],
            'TC_REG_MITRA_002 d email kosong' => [['email_vendor' => ''], ['email_vendor'], null],
            'TC_REG_MITRA_002 e alamat kosong' => [['address' => ''], ['address'], null],
            'TC_REG_MITRA_003 a format email' => [['email_vendor' => 'amanabissgmail.com'], ['email_vendor'], null],
            'TC_REG_MITRA_003 b format kontak' => [['contact' => 'abc@@'], ['contact'], null],
            'TC_REG_MITRA_005 a nama duplikat' => [[], ['vendor_name'], 'vendor_name'],
            'TC_REG_MITRA_005 b email duplikat' => [[], ['email_vendor'], 'email_vendor'],
            'TC_REG_MITRA_005 c kontak duplikat' => [[], ['contact'], 'contact'],
        ];
    }

    #[DataProvider('invalidMitra')]
    public function test_invalid_mitra_data_cannot_advance(array $overrides, array $fields, ?string $duplicate): void
    {
        if ($duplicate !== null) {
            $column = ['vendor_name' => 'name', 'email_vendor' => 'email', 'contact' => 'contact'][$duplicate];
            Vendor::factory()->create([$column => $this->mitraData()[$duplicate]]);
        }
        $count = Vendor::count();
        $this->browse(function (Browser $browser) use ($overrides, $fields): void {
            $this->fillMitra($browser, $overrides);
            $browser->press('Selanjutnya');
            $this->assertRejected($browser, $fields, '/vendor-register');
            $this->waitForStep($browser, 1);
        });
        $this->assertDatabaseCount('vendors', $count);
        $this->assertDatabaseCount('users', 0);
    }

    /** @return array<string, array{string}> */
    public static function descriptions(): array
    {
        return ['TC_REG_MITRA_004 a dengan deskripsi' => ['Salon grooming premium'], 'TC_REG_MITRA_004 b tanpa deskripsi' => ['']];
    }

    #[DataProvider('descriptions')]
    public function test_valid_mitra_data_opens_owner_step(string $description): void
    {
        $this->browse(function (Browser $browser) use ($description): void {
            $this->fillMitra($browser, ['description' => $description]);
            $this->next($browser, 2);
            $browser->assertSee('Informasi Pemilik & Akun');
            $browser->press('Sebelumnya');
            $this->waitForStep($browser, 1);
            foreach (array_replace($this->mitraData(), ['description' => $description]) as $field => $value) {
                $browser->assertInputValue($this->field($field), $value);
            }
        });
    }

    /** @return array<string, array{array<string, string>, list<string>, ?string}> */
    public static function invalidOwners(): array
    {
        return [
            'TC_REG_MITRA_006 a semua wajib kosong' => [array_fill_keys(['owner_name', 'phone_number', 'email', 'password'], ''), ['owner_name', 'phone_number', 'email', 'password'], null],
            'TC_REG_MITRA_006 b nama kosong' => [['owner_name' => ''], ['owner_name'], null],
            'TC_REG_MITRA_006 c telepon kosong' => [['phone_number' => ''], ['phone_number'], null],
            'TC_REG_MITRA_006 d email kosong' => [['email' => ''], ['email'], null],
            'TC_REG_MITRA_006 e password kosong' => [['password' => ''], ['password'], null],
            'TC_REG_MITRA_007 a format email' => [['email' => 'amanda@@gmail'], ['email'], null],
            'TC_REG_MITRA_007 b format telepon' => [['phone_number' => 'nomorabc'], ['phone_number'], null],
            'TC_REG_MITRA_008 a email duplikat' => [[], ['email'], 'email'],
            'TC_REG_MITRA_008 b telepon duplikat' => [[], ['phone_number'], 'phone_number'],
        ];
    }

    #[DataProvider('invalidOwners')]
    public function test_invalid_owner_data_cannot_advance(array $overrides, array $fields, ?string $duplicate): void
    {
        if ($duplicate !== null) {
            $role = Role::firstOrCreate(['name' => 'owner']);
            User::factory()->afterMaking(function (User $user): void {
                unset($user->email_verified_at);
            })->create([
                'role_id' => $role->id,
                'email' => $duplicate === 'email' ? 'amandaaaa@gmail.com' : 'existing@example.com',
                'phone_number' => $duplicate === 'phone_number' ? '08292417484' : '089999999999',
            ]);
        }
        $count = User::count();
        $this->browse(function (Browser $browser) use ($overrides, $fields): void {
            $this->openOwner($browser);
            $this->fillOwner($browser, $overrides);
            $browser->press('Selanjutnya');
            $this->assertRejected($browser, $fields, '/vendor-register');
            $this->waitForStep($browser, 2);
        });
        $this->assertDatabaseCount('users', $count);
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_tc_reg_mitra_009_third_step_is_confirmation_as_specified(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->openOwner($browser);
            $this->fillOwner($browser);
            $this->next($browser, 3);
            $browser->assertSeeIn($this->stepSelector(3), 'Konfirmasi Data');
        });
    }

    public function test_tc_reg_mitra_010_back_preserves_mitra_and_owner_data(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->openOwner($browser);
            $this->fillOwner($browser);
            $browser->press('Sebelumnya');
            $this->waitForStep($browser, 1);
            foreach ($this->mitraData() as $field => $value) {
                $browser->assertInputValue($this->field($field), $value);
            }
            $this->next($browser, 2);
            foreach ($this->ownerData() as $field => $value) {
                $browser->assertInputValue($this->field($field), $value);
            }
            $this->next($browser, 3);
            $browser->press('Sebelumnya');
            $this->waitForStep($browser, 2);
            foreach ($this->ownerData() as $field => $value) {
                $browser->assertInputValue($this->field($field), $value);
            }
        });
    }

    public function test_tc_reg_mitra_011_summary_matches_input_and_hides_password(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->openConfirmation($browser);
            foreach (array_merge($this->mitraData(), $this->ownerData()) as $field => $value) {
                if ($field !== 'password') {
                    $browser->assertSeeIn($this->stepSelector(4), $value);
                }
            }
            $browser->assertDontSeeIn($this->stepSelector(4), self::PASSWORD)->assertSeeIn($this->stepSelector(4), 'Sudah diisi');
        });
    }

    public function test_tc_reg_mitra_012_edit_links_update_summary(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->openConfirmation($browser);
            $browser->with($this->stepSelector(4), fn (Browser $summary) => $summary->clickLink('Ubah'));
            $this->waitForStep($browser, 1);
            $this->fill($browser, 'vendor_name', 'Abel Grooming Baru');
            $this->next($browser, 2);
            $this->next($browser, 3);
            $this->next($browser, 4);
            $browser->assertSeeIn($this->stepSelector(4), 'Abel Grooming Baru');
            $links = $browser->elements($this->stepSelector(4).' a');
            $links[1]->click();
            $this->waitForStep($browser, 2);
            $this->fill($browser, 'owner_name', 'Amanda Bella');
            $this->next($browser, 3);
            $this->next($browser, 4);
            $browser->assertSeeIn($this->stepSelector(4), 'Amanda Bella');
        });
    }

    public function test_tc_reg_mitra_013_submit_persists_pending_mitra_and_owner(): void
    {
        Role::firstOrCreate(['name' => 'owner']);
        $this->browse(function (Browser $browser): void {
            $this->openConfirmation($browser);
            $this->submit($browser);
            $browser->waitForLocation('/payment/subscription')->assertSee('verifikasi');
            $vendor = Vendor::where('email', 'amanabiss@gmail.com')->firstOrFail();
            $owner = User::where('email', 'amandaaaa@gmail.com')->firstOrFail();
            $this->assertDatabaseHas('vendors', [
                'id' => $vendor->id, 'name' => 'Abel Grooming', 'address' => $this->mitraData()['address'],
                'contact' => '081216178909', 'description' => $this->mitraData()['description'],
                'status_verification' => VendorVerificationStatus::Pending->value,
                'status_subscription' => VendorSubscriptionStatus::Inactive->value,
            ]);
            $this->assertSame('Amanda Bella Safira', $owner->name);
            $this->assertSame('08292417484', $owner->phone_number);
            $this->assertSame('owner', $owner->role->name);
            $this->assertTrue(Hash::check(self::PASSWORD, $owner->password));
            $this->assertDatabaseHas('vendor_staff', ['vendor_id' => $vendor->id, 'user_id' => $owner->id]);
            $this->assertDatabaseCount('vendors', 1);
            $this->assertDatabaseCount('users', 1);
            $this->assertMatchesRegularExpression('/1\\s*[x×]\\s*24/iu', $browser->text('body'));
        });
    }

    /** @return array<string, array{string, string}> */
    public static function safeText(): array
    {
        return [
            'TC_REG_MITRA_014 a karakter khusus' => ['Klinik A&B', 'layanan grooming - premium'],
            'TC_REG_MITRA_014 b script sebagai teks' => ['Klinik Aman', '<script>alert(1)</script>'],
        ];
    }

    #[DataProvider('safeText')]
    public function test_summary_displays_special_text_without_executing_script(string $name, string $description): void
    {
        $this->browse(function (Browser $browser) use ($name, $description): void {
            $this->fillMitra($browser, ['vendor_name' => $name, 'description' => $description]);
            $this->next($browser, 2);
            $this->fillOwner($browser);
            $this->next($browser, 3);
            $this->toggle($browser, 'agreePaymentTerms');
            $this->next($browser, 4);
            $browser->assertSeeIn($this->stepSelector(4), $name)->assertSeeIn($this->stepSelector(4), $description);
            $this->assertFalse($browser->driver->executeScript('return [...document.querySelectorAll('.json_encode($this->stepSelector(4).' script').')].some(el => el.textContent.includes("alert(1)"));'));
        });
    }

    /** @return array<string, array{string, string}> */
    public static function longText(): array
    {
        return [
            'TC_REG_MITRA_014 c nama panjang' => ['vendor_name', str_repeat('A', 300)],
            'TC_REG_MITRA_014 c alamat panjang' => ['address', str_repeat('A', 300)],
            'TC_REG_MITRA_014 c deskripsi panjang' => ['description', str_repeat('A', 1100)],
        ];
    }

    #[DataProvider('longText')]
    public function test_excessive_text_is_limited_or_rejected(string $field, string $value): void
    {
        $this->browse(function (Browser $browser) use ($field, $value): void {
            $this->fillMitra($browser, [$field => $value]);
            $actual = $browser->value($this->field($field));
            if (strlen($actual) < strlen($value)) {
                $this->assertSame('vendor_name', $field);
                $this->assertSame(100, strlen($actual));
            } else {
                $browser->press('Selanjutnya');
                $this->assertRejected($browser, [$field], '/vendor-register');
            }
        });
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_tc_reg_mitra_015_mobile_form_remains_usable(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->resize(375, 812);
            $this->openConfirmation($browser);
            foreach ([4, 3, 2, 1] as $step) {
                $this->waitForStep($browser, $step);
                $selector = json_encode($this->stepSelector($step), JSON_THROW_ON_ERROR);
                $this->assertTrue($browser->driver->executeScript('return [...document.querySelectorAll('.$selector.' + " input, " + '.$selector.' + " textarea")].every(el => {const r = el.getBoundingClientRect(); return r.width > 0 && r.left >= 0 && r.right <= innerWidth;});'));
                $this->assertTrue($browser->driver->executeScript('return document.documentElement.scrollWidth <= innerWidth;'));
                if ($step > 1) {
                    $browser->press('Sebelumnya');
                }
            }
        });
    }
}
