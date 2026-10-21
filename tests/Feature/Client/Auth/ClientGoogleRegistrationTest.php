<?php

namespace Tests\Feature\Client\Auth;

use App\Enums\SystemMessage;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientGoogleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
        Http::preventStrayRequests();
    }

    private function verifyGoogle(array $profile = [], array $query = [], bool $termsAccepted = true): TestResponse
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response(array_replace([
                'sub' => 'registration-google-id', 'email' => 'amanda@gmail.com',
                'email_verified' => true, 'name' => 'Amanda Dealova',
            ], $profile)),
        ]);

        return $this->withSession(['client_google_oauth' => [
            'state' => 'registration-state', 'verifier' => str_repeat('a', 64),
            'started_at' => now()->timestamp, 'intent' => 'register', 'terms_accepted' => $termsAccepted,
        ]])->get(route('client.google.callback', array_replace([
            'state' => 'registration-state', 'code' => 'test-code',
        ], $query)));
    }

    private function existingUser(array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => 'client']);
        $clientAttributes = array_intersect_key($attributes, array_flip(['google_id', 'remember_token']));
        $attributes = array_diff_key($attributes, $clientAttributes);

        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(array_replace([
            'email' => 'existing@example.com', 'username' => 'existing',
            'phone_number' => '081234567890', 'password' => 'password123', 'role_id' => $role->id,
        ], $attributes));

        UserClient::create(['user_id' => $user->id])->forceFill($clientAttributes)->save();

        return $user;
    }

    public function test_google_button_requires_terms_but_no_other_manual_fields(): void
    {
        Livewire::test('client.auth.register')->call('registerWithGoogle')
            ->assertHasErrors(['terms' => 'accepted'])->assertDispatched('toast-show', dataset: ['variant' => 'danger']);
        Livewire::test('client.auth.register')->set('terms', true)->call('registerWithGoogle')
            ->assertHasNoErrors()->assertRedirect(route('client.google.redirect', ['intent' => 'register']));
        $this->get(route('client.google.redirect', ['intent' => 'register']))->assertRedirect();
        $this->assertTrue(session('client_google_oauth.terms_accepted'));
        $this->assertNull(session('google_registration_terms'));
    }

    public function test_registration_cannot_bypass_terms_using_direct_route_or_callback(): void
    {
        $this->get(route('client.google.redirect', ['intent' => 'register']))
            ->assertRedirect(route('client.register'))->assertSessionHas('client_register_error');
        $this->verifyGoogle(termsAccepted: false)->assertRedirect(route('client.register'))->assertSessionHas('client_register_error');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_google_registration_directly_creates_client_and_logs_in(): void
    {
        Role::create(['name' => 'client']);
        $this->verifyGoogle()->assertRedirect(route('client.dashboard'))->assertSessionHas('client_registered', SystemMessage::CLIENT_REGISTER_SUCCESS->value);
        $user = User::where('email', 'amanda@gmail.com')->firstOrFail();
        $this->assertSame('Amanda Dealova', $user->name);
        $this->assertSame('amanda', $user->username);
        $this->assertSame('registration-google-id', $user->userClient?->google_id);
        $this->assertSame('client', $user->role->name);
        $this->assertNull($user->phone_number);
        $this->assertNull($user->password);
        $this->assertDatabaseHas('user_clients', ['user_id' => $user->id]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_clients', 1);
        $this->assertNull(session('google_client_registration'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_account_can_login_with_google_and_later_create_local_password(): void
    {
        Role::create(['name' => 'client']);
        $this->verifyGoogle()->assertRedirect(route('client.dashboard'));
        $user = User::where('email', 'amanda@gmail.com')->firstOrFail();
        $this->post(route('logout'))->assertRedirect('/');
        Livewire::test('client.auth.login')->set('identifier', $user->email)->set('password', 'password123')
            ->call('login')->assertHasErrors(['identifier'])->assertSet('errorMessage', '');
        $this->assertGuest();
        $this->withSession(['client_google_oauth' => [
            'state' => 'login-state', 'verifier' => str_repeat('b', 64), 'started_at' => now()->timestamp,
        ]])->get(route('client.google.callback', ['state' => 'login-state', 'code' => 'new-code']))->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect('/');
        Livewire::test('client.auth.reset-password', ['token' => Password::createToken($user)])
            ->set('email', $user->email)->set('password', 'newpassword123')->set('password_confirmation', 'newpassword123')
            ->call('resetPassword')->assertHasNoErrors()->assertRedirect(route('client.login'));
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
        Livewire::test('client.auth.login')->set('identifier', $user->email)->set('password', 'newpassword123')
            ->call('login')->assertHasNoErrors()->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_generated_username_is_unique_when_email_prefix_is_already_used(): void
    {
        $this->existingUser(['username' => 'amanda']);
        $this->verifyGoogle()->assertRedirect(route('client.dashboard'));
        $username = User::where('email', 'amanda@gmail.com')->firstOrFail()->username;
        $this->assertMatchesRegularExpression('/\Aamanda_[a-z0-9]{10}\z/', $username);
        $this->assertDatabaseCount('users', 2);
    }

    public static function unusualProfiles(): array
    {
        return [
            'short username and missing name' => [['email' => 'a@gmail.com', 'name' => null], 'a____', 'a'],
            'punctuation' => [['email' => 'maria.pet+app@gmail.com', 'name' => 'Maria'], 'maria_pet_app', 'Maria'],
        ];
    }

    #[DataProvider('unusualProfiles')]
    public function test_unusual_profiles_generate_valid_usernames(array $profile, string $username, string $name): void
    {
        Role::create(['name' => 'client']);
        $this->verifyGoogle($profile)->assertRedirect(route('client.dashboard'));
        $this->assertDatabaseHas('users', ['email' => $profile['email'], 'username' => $username, 'name' => $name]);
    }

    public function test_multiple_google_accounts_can_have_null_phone_numbers(): void
    {
        Role::create(['name' => 'client']);
        $this->verifyGoogle()->assertRedirect(route('client.dashboard'));
        $this->post(route('logout'))->assertRedirect('/');
        $this->verifyGoogle(['sub' => 'second-google-id', 'email' => 'second@gmail.com'])->assertRedirect(route('client.dashboard'));
        $this->assertSame(2, User::whereNull('phone_number')->whereNull('password')->count());
    }

    public function test_existing_email_or_google_id_cannot_create_duplicate_account(): void
    {
        $this->existingUser(['email' => 'amanda@gmail.com', 'google_id' => 'registration-google-id']);
        $this->verifyGoogle()->assertRedirect(route('client.register'))->assertSessionHas('client_register_error');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_vendor_email_cannot_be_used_for_google_client_registration(): void
    {
        Vendor::create([
            'name' => 'Vendor', 'address' => 'Jakarta', 'contact' => '089999999999',
            'email' => 'amanda@gmail.com', 'description' => '', 'join_at' => now(),
        ]);
        $this->verifyGoogle()->assertRedirect(route('client.register'))->assertSessionHas('client_register_error');
        $this->assertDatabaseCount('users', 0);
    }

    public static function rejectedGoogleProfiles(): array
    {
        return ['unverified' => [['email_verified' => false]], 'third party' => [['email' => 'amanda@example.com']]];
    }

    #[DataProvider('rejectedGoogleProfiles')]
    public function test_google_profile_must_be_verified_and_authoritative(array $profile): void
    {
        $this->verifyGoogle($profile)->assertRedirect(route('client.register'))->assertSessionHas('client_register_error');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_callback_intent_cannot_override_session_intent(): void
    {
        Role::create(['name' => 'client']);
        $this->verifyGoogle(query: ['intent' => 'login'])->assertRedirect(route('client.dashboard'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_cancelled_google_registration_returns_to_register_with_toast(): void
    {
        $this->verifyGoogle(query: ['error' => 'access_denied'])->assertRedirect(route('client.register'))->assertSessionHas('client_register_error');
        Http::assertNothingSent();
    }

    public function test_missing_configuration_returns_to_register_with_toast(): void
    {
        config(['services.google.client_id' => null]);
        $this->get(route('client.google.redirect', ['intent' => 'register']))->assertRedirect(route('client.register'))->assertSessionHas('client_register_error');
    }

    public function test_profile_write_failure_rolls_back_google_registration(): void
    {
        Role::create(['name' => 'client']);
        UserClient::creating(function (): void {
            throw new \RuntimeException('Simulated profile write failure');
        });
        try {
            $this->verifyGoogle()->assertRedirect(route('client.register'))->assertSessionHas('client_register_error', SystemMessage::ERROR_SYSTEM->value);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('user_clients', 0);
            $this->assertGuest();
        } finally {
            UserClient::flushEventListeners();
        }
    }

    public function test_migration_can_roll_back_without_changing_existing_manual_accounts(): void
    {
        $user = $this->existingUser();
        $password = $user->password;
        $migration = require database_path('migrations/2026_10_15_033717_allow_google_accounts_without_phone_or_password.php');
        $migration->down();
        $migration->up();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone_number' => '081234567890', 'password' => $password]);
    }

    public function test_migration_rollback_refuses_to_discard_google_account_data(): void
    {
        Role::create(['name' => 'client']);
        $this->verifyGoogle()->assertRedirect(route('client.dashboard'));
        $migration = require database_path('migrations/2026_10_15_033717_allow_google_accounts_without_phone_or_password.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rollback dibatalkan');
        $migration->down();
    }
}
