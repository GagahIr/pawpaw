<?php

namespace Tests\Feature\Vendor\Auth;

use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VendorLoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

        $this->vendor = Vendor::factory()->create();
        app('vendor.context')->set($this->vendor);
        URL::defaults(['subdomain' => $this->vendor->slug]);

        $this->owner = $this->staff($this->vendor, 'owner', StaffPosition::Owner, [
            'email' => 'owner@klinik.test',
            'username' => 'drh_rani',
            'phone_number' => '081234567890',
        ]);
    }

    private function staff(Vendor $vendor, string $roleName, StaffPosition $position, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        $user = User::factory()->create(array_replace([
            'password' => 'password123',
            'role_id' => $role->id,
        ], $attributes));

        VendorStaff::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'position' => $position,
        ]);

        return $user;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validIdentifiers(): array
    {
        return [
            'email' => ['owner@klinik.test'],
            'username' => ['drh_rani'],
            'username with other casing' => ['Drh_Rani'],
            'phone as stored' => ['081234567890'],
            'phone with spaces and dashes' => ['0812-3456 7890'],
            'phone with country code' => ['6281234567890'],
            'phone with plus country code' => ['+6281234567890'],
            'identifier with surrounding spaces' => ['  drh_rani  '],
        ];
    }

    #[DataProvider('validIdentifiers')]
    public function test_owner_can_login_with_email_username_or_phone_number(string $identifier): void
    {
        Livewire::test('vendor.auth.login')
            ->set('identifier', $identifier)
            ->set('password', 'password123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($this->owner, 'vendor');
    }

    public function test_front_office_can_login_with_username(): void
    {
        $frontOffice = $this->staff($this->vendor, 'front_office', StaffPosition::FrontOffice, [
            'email' => 'fo@klinik.test',
            'username' => 'front_desk',
            'phone_number' => '089999999999',
        ]);

        Livewire::test('vendor.auth.login')
            ->set('identifier', 'front_desk')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($frontOffice, 'vendor');
    }

    public function test_phone_number_stored_with_country_code_matches_local_format(): void
    {
        $this->owner->update(['phone_number' => '+6281234567890']);

        Livewire::test('vendor.auth.login')
            ->set('identifier', '081234567890')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($this->owner, 'vendor');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unknownIdentifiers(): array
    {
        return [
            'unknown email' => ['nobody@klinik.test'],
            'unknown username' => ['nobody'],
            'unknown phone number' => ['081111111111'],
            'partial username' => ['drh'],
            'partial phone number' => ['0812345678'],
        ];
    }

    #[DataProvider('unknownIdentifiers')]
    public function test_unknown_identifier_is_rejected(string $identifier): void
    {
        Livewire::test('vendor.auth.login')
            ->set('identifier', $identifier)
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['identifier']);

        $this->assertGuest('vendor');
    }

    public function test_wrong_password_is_rejected_for_every_identifier_type(): void
    {
        foreach (['owner@klinik.test', 'drh_rani', '081234567890'] as $identifier) {
            Livewire::test('vendor.auth.login')
                ->set('identifier', $identifier)
                ->set('password', 'salah-total')
                ->call('login')
                ->assertHasErrors(['identifier']);

            $this->assertGuest('vendor');
        }
    }

    public function test_staff_of_another_mitra_cannot_login_here_by_username_or_phone(): void
    {
        $otherVendor = Vendor::factory()->create();

        $this->staff($otherVendor, 'owner', StaffPosition::Owner, [
            'email' => 'owner@lain.test',
            'username' => 'owner_lain',
            'phone_number' => '087777777777',
        ]);

        foreach (['owner@lain.test', 'owner_lain', '087777777777'] as $identifier) {
            Livewire::test('vendor.auth.login')
                ->set('identifier', $identifier)
                ->set('password', 'password123')
                ->call('login')
                ->assertHasErrors(['identifier']);

            $this->assertGuest('vendor');
        }
    }

    public function test_identifier_and_password_are_required(): void
    {
        Livewire::test('vendor.auth.login')
            ->set('identifier', '')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['identifier' => 'required', 'password' => 'required']);
    }

    public function test_remember_cookie_stores_the_identifier_that_was_typed(): void
    {
        Livewire::test('vendor.auth.login')
            ->set('identifier', 'drh_rani')
            ->set('password', 'password123')
            ->set('remember', true)
            ->call('login')
            ->assertHasNoErrors();

        $cookie = Cookie::getQueuedCookies()['pawpaw_vendor_identifier'] ?? null;

        $this->assertNotNull($cookie);
        $this->assertSame('drh_rani', $cookie->getValue());
    }

    public function test_login_page_shows_the_identifier_field(): void
    {
        $this->withoutVite()
            ->get('http://'.$this->vendor->slug.'.'.config('app.domain').'/login')
            ->assertOk()
            ->assertSee('Email, username, atau nomor HP');
    }
}
