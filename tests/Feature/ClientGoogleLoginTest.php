<?php

namespace Tests\Feature;

use App\Enums\SystemMessage;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientGoogleLoginTest extends TestCase
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

    private function user(string $roleName = 'client', array $overrides = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(array_replace([
            'email' => 'amanda@gmail.com', 'phone_number' => '081234567890', 'role_id' => $role->id,
            'remember_token' => null,
        ], $overrides));
        if ($roleName === 'client') {
            UserClient::create(['user_id' => $user->id]);
        }

        return $user;
    }

    private function fakeGoogle(array $profile = []): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response(array_replace([
                'sub' => 'google-subject-123', 'email' => 'amanda@gmail.com', 'email_verified' => true,
            ], $profile)),
        ]);
    }

    private function googleCallback(array $query = [], array $context = []): TestResponse
    {
        return $this->withSession(['client_google_oauth' => array_replace([
            'state' => 'test-state', 'verifier' => str_repeat('a', 64),
            'started_at' => now()->timestamp, 'remember' => false,
        ], $context)])->get(route('client.google.callback', array_replace([
            'state' => 'test-state', 'code' => 'test-code',
        ], $query)));
    }

    public function test_existing_button_redirects_to_google_route_and_keeps_remember_choice(): void
    {
        Livewire::test('client.client-login-form')->set('remember', true)->call('loginWithGoogle')
            ->assertRedirect(route('client.google.redirect', ['remember' => 1]));
    }

    public function test_google_redirect_has_state_pkce_and_limited_scopes(): void
    {
        $response = $this->get(route('client.google.redirect', ['remember' => 1]));
        $response->assertRedirect();
        $this->assertSame('accounts.google.com', parse_url($response->headers->get('Location'), PHP_URL_HOST));
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $parameters);
        $this->assertSame(session('client_google_oauth.state'), $parameters['state']);
        $this->assertSame('S256', $parameters['code_challenge_method']);
        $this->assertSame('openid email profile', $parameters['scope']);
        $this->assertTrue(session('client_google_oauth.remember'));
        $this->assertArrayNotHasKey('client_secret', $parameters);
        Http::assertNothingSent();
    }

    public function test_missing_credentials_show_error_toast_instead_of_server_error(): void
    {
        config(['services.google.client_secret' => null]);
        $this->get(route('client.google.redirect'))->assertRedirect(route('client.login'))
            ->assertSessionHas('client_login_error', 'Login Google belum dikonfigurasi. Silakan gunakan email/nomor telepon dan password.');
        $this->withoutVite()->get(route('client.login'))->assertOk()->assertSee('Login Google belum dikonfigurasi.');
        Http::assertNothingSent();
    }

    public function test_verified_google_account_links_and_logs_in_without_creating_user(): void
    {
        $user = $this->user();
        $password = $user->password;
        $this->fakeGoogle();
        $sessionId = session()->getId();

        $this->googleCallback()->assertRedirect(route('client.dashboard'))->assertSessionHas('client_logged_in', SystemMessage::CLIENT_LOGIN_SUCCESS->value)
            ->assertSessionMissing('client_google_oauth');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'google_id' => 'google-subject-123', 'password' => $password]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_clients', 1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'authorization_code' && $request['code_verifier'] === str_repeat('a', 64));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://openidconnect.googleapis.com/v1/userinfo'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_google_login_respects_remember_me(): void
    {
        $user = $this->user();
        $this->fakeGoogle();
        $this->googleCallback(context: ['remember' => true])->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotEmpty($user->fresh()->remember_token);
        $this->assertSame('amanda@gmail.com', Cookie::queued('pawpaw_client_identifier')->getValue());
    }

    public function test_linked_identity_is_used_after_google_email_changes(): void
    {
        $user = $this->user(overrides: ['google_id' => 'google-subject-123']);
        $this->fakeGoogle(['email' => 'changed@gmail.com']);
        $this->googleCallback()->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('amanda@gmail.com', $user->fresh()->email);
    }

    public function test_verified_workspace_account_can_link(): void
    {
        $user = $this->user(overrides: ['email' => 'amanda@company.test']);
        $this->fakeGoogle(['email' => 'amanda@company.test', 'hd' => 'company.test']);
        $this->googleCallback()->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public static function rejectedProfiles(): array
    {
        return [
            'unverified email' => [['email_verified' => false]],
            'invalid email' => [['email' => 'invalid']],
            'missing subject' => [['sub' => '']],
            'malformed verification' => [['email_verified' => 'true']],
            'unknown client' => [['email' => 'unknown@gmail.com']],
            'third party email' => [['email' => 'amanda@example.com']],
        ];
    }

    #[DataProvider('rejectedProfiles')]
    public function test_invalid_or_unmatched_profiles_do_not_login_or_link(array $profile): void
    {
        $user = $this->user();
        $this->fakeGoogle($profile);
        $this->googleCallback()->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public static function otherRoles(): array
    {
        return ['owner' => ['owner'], 'front office' => ['front_office']];
    }

    #[DataProvider('otherRoles')]
    public function test_non_client_accounts_are_rejected(string $role): void
    {
        $user = $this->user($role);
        $this->fakeGoogle();
        $this->googleCallback()->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_deleted_client_is_rejected(): void
    {
        $user = $this->user(overrides: ['deleted_at' => now()]);
        $this->fakeGoogle();
        $this->googleCallback()->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_google_identity_cannot_replace_an_existing_link(): void
    {
        $user = $this->user(overrides: ['google_id' => 'another-google-subject']);
        $this->fakeGoogle();
        $this->googleCallback()->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        $this->assertGuest();
        $this->assertSame('another-google-subject', $user->fresh()->google_id);
    }

    public function test_state_is_required_and_cannot_be_replayed(): void
    {
        $this->get(route('client.google.callback', ['code' => 'forged-code', 'state' => 'forged-state']))
            ->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        $this->googleCallback(['state' => 'wrong-state'])->assertRedirect(route('client.login'))
            ->assertSessionMissing('client_google_oauth');
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_successful_callback_cannot_be_replayed_after_logout(): void
    {
        $this->user();
        $this->fakeGoogle();
        $this->googleCallback()->assertRedirect(route('client.dashboard'));
        $this->post(route('logout'))->assertRedirect('/');
        $this->get(route('client.google.callback', ['state' => 'test-state', 'code' => 'test-code']))
            ->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        Http::assertSentCount(2);
        $this->assertGuest();
    }

    public function test_expired_oauth_session_is_rejected(): void
    {
        $this->googleCallback(context: ['started_at' => now()->subMinutes(11)->timestamp])
            ->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        Http::assertNothingSent();
    }

    public static function malformedOAuthContexts(): array
    {
        return [
            'non-string state' => [['state' => ['test-state']]],
            'non-string verifier' => [['verifier' => ['invalid-verifier']]],
            'non-integer start time' => [['started_at' => 'invalid-time']],
        ];
    }

    #[DataProvider('malformedOAuthContexts')]
    public function test_malformed_oauth_context_is_rejected_without_contacting_google(array $context): void
    {
        $this->googleCallback(context: $context)->assertRedirect(route('client.login'))
            ->assertSessionHas('client_login_error', 'Sesi login Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.')
            ->assertSessionMissing('client_google_oauth');

        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_google_cancellation_displays_friendly_toast(): void
    {
        $this->googleCallback(['error' => 'access_denied'])->assertRedirect(route('client.login'))
            ->assertSessionHas('client_login_error', 'Login Google dibatalkan atau izin akses tidak diberikan.');
        Http::assertNothingSent();
    }

    public function test_missing_code_does_not_contact_google(): void
    {
        $this->googleCallback(['code' => null])->assertRedirect(route('client.login'))->assertSessionHas('client_login_error');
        Http::assertNothingSent();
    }

    public function test_google_outage_does_not_expose_response_or_login(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['error' => 'private-provider-error'], 500)]);
        $this->googleCallback()->assertRedirect(route('client.login'))
            ->assertSessionHas('client_login_error', SystemMessage::ERROR_SYSTEM->value);
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_restart_google_login(): void
    {
        $this->actingAs($this->user())->get(route('client.google.redirect'))->assertRedirect(route('client.dashboard'));
        Http::assertNothingSent();
    }
}
