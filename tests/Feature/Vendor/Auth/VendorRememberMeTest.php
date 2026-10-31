<?php

namespace Tests\Feature\Vendor\Auth;

use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStaff;
use App\Services\VendorUserProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VendorRememberMeTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $this->vendor = Vendor::factory()->create();
        app('vendor.context')->set($this->vendor);
    }

    private function staff(string $roleName = 'owner', array $overrides = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create(array_replace([
            'email' => 'owner@klinik.test', 'password' => 'password123', 'role_id' => $role->id,
        ], $overrides));
        VendorStaff::create([
            'user_id' => $user->id,
            'vendor_id' => $this->vendor->id,
            'position' => $roleName === 'owner' ? StaffPosition::Owner : StaffPosition::FrontOffice,
        ]);

        return $user;
    }

    public function test_login_with_remember_queues_identifier_cookie(): void
    {
        $this->staff();

        Livewire::test('vendor.auth.login')
            ->set('email', 'owner@klinik.test')->set('password', 'password123')->set('remember', true)
            ->call('login')->assertHasNoErrors();

        $cookie = Cookie::getQueuedCookies()['pawpaw_vendor_identifier'] ?? null;
        $this->assertNotNull($cookie);
        $this->assertSame('owner@klinik.test', $cookie->getValue());
        $this->assertGreaterThan(now()->timestamp, $cookie->getExpiresTime());
    }

    public function test_login_without_remember_forgets_identifier_cookie(): void
    {
        $this->staff();

        Livewire::test('vendor.auth.login')
            ->set('email', 'owner@klinik.test')->set('password', 'password123')->set('remember', false)
            ->call('login')->assertHasNoErrors();

        $cookie = Cookie::getQueuedCookies()['pawpaw_vendor_identifier'] ?? null;
        $this->assertNotNull($cookie);
        $this->assertLessThan(now()->timestamp, $cookie->getExpiresTime());
    }

    public function test_login_form_prefills_email_and_checks_remember_from_cookie(): void
    {
        Livewire::withCookie('pawpaw_vendor_identifier', 'owner@klinik.test')
            ->test('vendor.auth.login')
            ->assertSet('email', 'owner@klinik.test')
            ->assertSet('remember', true);
    }

    public function test_login_form_is_empty_without_cookie(): void
    {
        Livewire::test('vendor.auth.login')->assertSet('email', '')->assertSet('remember', false);
    }

    public function test_provider_restores_active_staff_by_remember_token(): void
    {
        $user = $this->staff();
        $user->forceFill(['remember_token' => 'valid-token'])->save();

        $provider = Auth::createUserProvider('vendors');

        $this->assertInstanceOf(VendorUserProvider::class, $provider);
        $this->assertTrue($provider->retrieveByToken($user->id, 'valid-token')->is($user));
        $this->assertNull($provider->retrieveByToken($user->id, 'wrong-token'));
    }

    public static function ineligibleAccounts(): array
    {
        return [
            'deleted staff' => ['owner', ['deleted_at' => '2026-10-01 00:00:00']],
            'client role' => ['client', []],
        ];
    }

    #[DataProvider('ineligibleAccounts')]
    public function test_provider_does_not_restore_ineligible_accounts(string $roleName, array $overrides): void
    {
        $user = $this->staff($roleName, $overrides);
        $user->forceFill(['remember_token' => 'valid-token'])->save();

        $this->assertNull(Auth::createUserProvider('vendors')->retrieveByToken($user->id, 'valid-token'));
    }
}
