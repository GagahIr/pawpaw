<?php

namespace Tests\Feature;

use App\Actions\Client\LoginClientAction;
use App\Enums\SystemMessage;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    private function user(string $roleName = 'client'): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create([
            'name' => 'Amanda Dealova', 'username' => 'amandadealova',
            'email' => 'amanda@example.com', 'phone_number' => '081234567890',
            'password' => 'password123', 'role_id' => $role->id, 'remember_token' => null,
        ]);

        if ($roleName === 'client') {
            UserClient::create(['user_id' => $user->id]);
        }

        return $user;
    }

    private function form(array $overrides = []): Testable
    {
        $component = Livewire::test('client.client-login-form');
        foreach (array_replace([
            'identifier' => 'amanda@example.com', 'password' => 'password123', 'remember' => false,
        ], $overrides) as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    public static function validIdentifiers(): array
    {
        return [
            'email' => ['amanda@example.com'],
            'email with uppercase and spaces' => [' AMANDA@example.com '],
            'phone' => ['081234567890'],
            'formatted phone' => [' (0812) 3456-7890 '],
        ];
    }

    #[DataProvider('validIdentifiers')]
    public function test_client_can_login_without_creating_another_account(string $identifier): void
    {
        $user = $this->user();
        $oldSession = session()->getId();

        $this->form(['identifier' => $identifier])->call('login')
            ->assertHasNoErrors()->assertSet('password', '')->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_clients', 1);
        $this->assertNull($user->fresh()->remember_token);
        $this->withoutVite()->get('/')->assertOk()->assertSee(SystemMessage::CLIENT_LOGIN_SUCCESS->value);
    }

    public function test_blank_fields_are_rejected_with_toast(): void
    {
        Livewire::test('client.client-login-form')->call('login')
            ->assertHasErrors(['identifier' => 'required', 'password' => 'required'])
            ->assertDispatched('client-registration-notification', type: 'error');
        $this->assertGuest();
    }

    public static function invalidInputs(): array
    {
        return [
            'invalid email' => [['identifier' => 'amanda@'], 'identifier', 'email'],
            'email too long' => [['identifier' => str_repeat('a', 245).'@example.com'], 'identifier', 'max'],
            'phone too short' => [['identifier' => '08123'], 'identifier', 'min'],
            'phone too long' => [['identifier' => str_repeat('1', 16)], 'identifier', 'max'],
            'phone only symbols' => [['identifier' => '++++++++++'], 'identifier', 'regex'],
            'phone contains letters' => [['identifier' => '08abc4567890'], 'identifier', 'regex'],
            'short password' => [['password' => 'short'], 'password', 'min'],
            'long password' => [['password' => str_repeat('a', 73)], 'password', 'max'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_inputs_are_rejected(array $inputs, string $field, string $rule): void
    {
        $this->form($inputs)->call('login')->assertHasErrors([$field => $rule])
            ->assertDispatched('client-registration-notification', type: 'error');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_multibyte_password_over_72_bytes_is_rejected(): void
    {
        $this->form(['password' => str_repeat('é', 37)])->call('login')
            ->assertHasErrors(['password'])->assertSet('errorMessage', '');
        $this->assertGuest();
    }

    public static function invalidCredentials(): array
    {
        return [
            'wrong password' => [['password' => 'wrongpassword']],
            'unknown email' => [['identifier' => 'unknown@example.com']],
            'unknown phone' => [['identifier' => '089999999999']],
        ];
    }

    #[DataProvider('invalidCredentials')]
    public function test_invalid_credentials_show_same_error_without_logging_in(array $inputs): void
    {
        $this->user();
        $this->form($inputs)->call('login')->assertHasErrors(['identifier'])
            ->assertDispatched('client-registration-notification', type: 'error',
                message: 'Email/nomor telepon atau password tidak sesuai dengan akun pelanggan.')
            ->assertSet('password', '')->assertNoRedirect();
        $this->assertGuest();
    }

    public static function nonClientRoles(): array
    {
        return ['owner' => ['owner'], 'front office' => ['front_office']];
    }

    #[DataProvider('nonClientRoles')]
    public function test_other_roles_cannot_login_using_client_form(string $role): void
    {
        $this->user($role);
        $this->form()->call('login')->assertHasErrors(['identifier'])->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_deleted_client_cannot_login(): void
    {
        $user = $this->user();
        $user->forceFill(['deleted_at' => now()])->save();
        $this->form()->call('login')->assertHasErrors(['identifier']);
        $this->assertGuest();
    }

    public function test_remember_me_issues_cookie_and_restores_login_without_session(): void
    {
        $user = $this->user();
        $this->form(['remember' => true])->call('login')->assertHasNoErrors()->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotEmpty($user->fresh()->remember_token);

        $guard = Auth::guard();
        $cookieName = $guard->getRecallerName();
        $cookie = collect(Cookie::getQueuedCookies())->first(fn ($cookie): bool => $cookie->getName() === $cookieName);
        $this->assertNotNull($cookie);

        session()->invalidate();
        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookie->getValue())->withoutVite()->get('/')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Auth::viaRemember());
    }

    public function test_remembered_identifier_is_restored_after_logout_without_storing_password(): void
    {
        $this->user();
        $this->form(['remember' => true])->call('login')->assertHasNoErrors();
        $cookie = Cookie::queued('pawpaw_client_identifier');
        $this->assertNotNull($cookie);
        $this->assertSame('amanda@example.com', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertGreaterThan(now()->addDays(29)->timestamp, $cookie->getExpiresTime());

        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
        Livewire::withCookie('pawpaw_client_identifier', $cookie->getValue())
            ->test('client.client-login-form')->assertSet('identifier', 'amanda@example.com')
            ->assertSet('remember', true)->assertSet('password', '');
    }

    public function test_remembered_phone_is_normalized_and_login_without_remember_removes_saved_identifier(): void
    {
        $this->user();
        $this->form(['identifier' => '(0812) 3456-7890', 'remember' => true])->call('login')->assertHasNoErrors();
        $this->assertSame('081234567890', Cookie::queued('pawpaw_client_identifier')->getValue());
        $this->post(route('logout'));
        $this->form(['remember' => false])->call('login')->assertHasNoErrors();
        $cookie = Cookie::queued('pawpaw_client_identifier');
        $this->assertLessThan(now()->timestamp, $cookie->getExpiresTime());
    }

    public function test_failed_login_does_not_save_or_replace_identifier(): void
    {
        $this->user();
        $this->form(['remember' => true, 'password' => 'wrongpassword'])->call('login')->assertHasErrors(['identifier']);
        $this->assertNull(Cookie::queued('pawpaw_client_identifier'));
    }

    public function test_too_many_failures_are_throttled_and_allow_retry_after_timeout(): void
    {
        $this->freezeTime();
        $user = $this->user();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->form(['password' => 'wrongpassword'])->call('login')->assertHasErrors(['identifier']);
        }

        $this->form()->call('login')->assertHasErrors(['identifier'])
            ->assertDispatched('client-registration-notification', type: 'error',
                message: 'Terlalu banyak percobaan login. Coba lagi dalam 60 detik.');
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->form()->call('login')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_system_failure_shows_generic_toast_and_does_not_login(): void
    {
        $this->mock(LoginClientAction::class, function ($mock): void {
            $mock->shouldReceive('execute')->once()->andThrow(new \RuntimeException('Simulated failure'));
        });
        $this->form()->call('login')->assertHasNoErrors()
            ->assertSet('errorMessage', SystemMessage::ERROR_SYSTEM->value)
            ->assertDispatched('client-registration-notification', type: 'error', message: SystemMessage::ERROR_SYSTEM->value)
            ->assertSet('password', '')->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_login_page_is_guest_only(): void
    {
        $this->withoutVite()->get(route('client.login'))->assertOk()->assertSee('Masuk Akun Pelanggan');
        $this->actingAs($this->user())->get(route('client.login'))->assertRedirect('/');
    }
}
