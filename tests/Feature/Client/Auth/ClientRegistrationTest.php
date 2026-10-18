<?php

namespace Tests\Feature\Client\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ClientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Role::create(['name' => 'client']);
    }

    private function form(array $overrides = [])
    {
        $component = Livewire::test('client.auth.register');

        foreach (array_replace([
            'name' => 'Amanda Dealova',
            'username' => 'amandadealova',
            'phone_number' => '081234567890',
            'email' => 'amanda@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ], $overrides) as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    public function test_registration_creates_client_profile_hashes_password_and_logs_in(): void
    {
        $this->form(['username' => ' AmandaDealova ', 'email' => ' AMANDA@example.com '])
            ->call('register')->assertHasNoErrors()->assertSet('errorMessage', '')->assertRedirect(route('client.dashboard'));

        $user = User::where('email', 'amanda@example.com')->firstOrFail();
        $this->assertSame('amandadealova', $user->username);
        $this->assertSame('client', $user->role->name);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('user_clients', ['user_id' => $user->id, 'count_fake_booking' => 0]);
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
        $this->assertSame(0, Vendor::count());
        $this->withoutVite()->get(route('client.dashboard'))->assertOk()
            ->assertSee('Pendaftaran akun pelanggan berhasil');
        $this->get('/client-register')->assertRedirect(route('client.dashboard'));
    }

    public function test_invalid_inputs_do_not_create_accounts(): void
    {
        $this->form([
            'name' => '', 'username' => 'invalid username', 'phone_number' => 'abc',
            'email' => 'invalid', 'password' => 'short', 'password_confirmation' => 'different', 'terms' => false,
        ])->call('register')->assertHasErrors(['name', 'username', 'phone_number', 'email', 'password', 'terms'])
            ->assertDispatched('toast-show', dataset: ['variant' => 'danger']);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('user_clients', 0);
        $this->assertGuest();
    }

    public function test_password_confirmation_is_checked_on_server(): void
    {
        $this->form(['password_confirmation' => 'otherpassword'])->call('register')
            ->assertHasErrors(['password' => 'confirmed', 'password_confirmation' => 'same']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_all_fields_are_required(): void
    {
        Livewire::test('client.auth.register')->call('register')->assertHasErrors([
            'name' => 'required', 'username' => 'required', 'phone_number' => 'required',
            'email' => 'required', 'password' => 'required', 'password_confirmation' => 'required',
            'terms' => 'accepted',
        ]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_input_length_limits_are_enforced_on_server(): void
    {
        $this->form([
            'name' => str_repeat('a', 101), 'username' => str_repeat('a', 51),
            'phone_number' => str_repeat('1', 21),
            'email' => str_repeat('a', 245).'@example.com',
            'password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73),
        ])->call('register')->assertHasErrors([
            'name' => 'max', 'username' => 'max', 'phone_number' => 'max',
            'email' => 'max', 'password' => 'max', 'password_confirmation' => 'max',
        ]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_phone_formats_are_rejected(): void
    {
        foreach (['++++++++++', '----------', '08123', '08123456789+', '08abc4567890', '+123456789'] as $phone) {
            $this->form(['phone_number' => $phone])->call('register')->assertHasErrors(['phone_number']);
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_phone_formatting_is_removed_before_saving(): void
    {
        $this->form(['phone_number' => '(0812) 3456-7890'])->call('register')
            ->assertHasNoErrors()->assertRedirect(route('client.dashboard'));
        $this->assertDatabaseHas('users', ['phone_number' => '081234567890']);
    }

    public function test_formatted_duplicate_phone_is_rejected(): void
    {
        User::create([
            'name' => 'Existing', 'email' => 'existing@example.com',
            'phone_number' => '081234567890', 'password' => 'password123', 'role_id' => Role::firstOrFail()->id,
        ]);
        $this->form(['phone_number' => '0812-3456-7890'])->call('register')
            ->assertHasErrors(['phone_number' => 'unique']);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_clients', 0);
    }

    public function test_multibyte_password_exceeding_bcrypt_limit_is_rejected(): void
    {
        $password = str_repeat('é', 37);
        $this->form(['password' => $password, 'password_confirmation' => $password])
            ->call('register')->assertHasErrors(['password'])
            ->assertSet('errorMessage', '');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_can_open_registration_page(): void
    {
        $this->withoutVite()->get('/client-register')
            ->assertOk()->assertSee('Daftar Akun Pelanggan');
    }

    public function test_navbar_shows_registration_links_for_guests(): void
    {
        $this->blade('@include("landing_page.components.navbar")')
            ->assertSee('Masuk')->assertSee('Daftar Pelanggan')->assertSee('Daftar Mitra')
            ->assertDontSee('Logout');
    }

    public function test_logged_in_client_can_logout_and_open_registration_again(): void
    {
        $this->form()->call('register')->assertHasNoErrors();
        $this->blade('@include("landing_page.components.navbar")')
            ->assertSee('Logout')->assertDontSee('Daftar Pelanggan')->assertDontSee('Daftar Mitra');

        $this->withSession(['logout_test_marker' => 'old session'])
            ->post(route('logout'))->assertRedirect('/')
            ->assertSessionMissing('logout_test_marker');
        $this->assertGuest();
        $this->withoutVite()->get(route('client.register'))->assertOk();
    }

    public function test_guests_are_redirected_to_client_login_when_attempting_logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('client.login'));
        $this->assertGuest();
    }

    public function test_existing_user_identifiers_are_rejected(): void
    {
        User::create([
            'name' => 'Existing', 'username' => 'amandadealova', 'email' => 'amanda@example.com',
            'phone_number' => '081234567890', 'password' => 'password123', 'role_id' => Role::firstOrFail()->id,
        ]);
        $this->form()->call('register')->assertHasErrors([
            'username' => 'unique', 'email' => 'unique', 'phone_number' => 'unique',
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_clients', 0);
    }

    public function test_vendor_email_and_contact_are_rejected_like_vendor_rules(): void
    {
        Vendor::create([
            'name' => 'Vendor', 'address' => 'Jakarta', 'contact' => '081234567890',
            'email' => 'amanda@example.com', 'description' => '', 'join_at' => now(),
        ]);
        $this->form()->call('register')->assertHasErrors(['email' => 'unique', 'phone_number' => 'unique']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_profile_failure_rolls_back_user_and_shows_error(): void
    {
        UserClient::creating(function () {
            throw new \RuntimeException('Simulated profile write failure');
        });

        try {
            $this->form()->call('register')
                ->assertSet('errorMessage', 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.')
                ->assertDispatched('toast-show', dataset: ['variant' => 'danger'], slots: ['text' => 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.'])
                ->assertNoRedirect();
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('user_clients', 0);
            $this->assertGuest();
        } finally {
            UserClient::flushEventListeners();
        }
    }

    public function test_missing_client_role_does_not_create_partial_account(): void
    {
        Role::query()->delete();
        $this->form()->call('register')->assertSet('errorMessage', 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_vendor_accounts_can_still_be_created_without_username(): void
    {
        $ownerRole = Role::create(['name' => 'owner']);
        $owner = User::create([
            'name' => 'Owner', 'email' => 'owner@example.com', 'phone_number' => '089999999999',
            'password' => 'password123', 'role_id' => $ownerRole->id,
        ]);

        $this->assertNull($owner->username);
        $this->assertSame('owner', $owner->role->name);
    }
}
