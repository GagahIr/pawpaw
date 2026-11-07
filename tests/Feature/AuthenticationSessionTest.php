<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.domain' => 'localhost', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    public static function localAddresses(): array
    {
        return ['home' => ['/'], 'login' => ['/client-login?from=local'], 'register' => ['/client-register']];
    }

    #[DataProvider('localAddresses')]
    public function test_loopback_address_redirects_to_the_local_domain_with_the_same_port_and_path(string $path): void
    {
        $this->get('http://127.0.0.1:8000'.$path)->assertRedirect('http://localhost:8000'.$path);
        $this->withoutVite()->get('http://localhost:8000'.$path)->assertOk();
    }

    private function client(?string $password = null): User
    {
        $user = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'client'])->id,
            'phone_number' => null, 'password' => $password,
        ]);
        UserClient::create(['user_id' => $user->id, 'google_id' => 'google-identity']);

        return $user;
    }

    public function test_passwordless_google_client_can_restore_login_from_remember_cookie(): void
    {
        $user = $this->client();
        Auth::guard('client')->login($user, true);
        $name = Auth::guard('client')->getRecallerName();
        $cookie = Cookie::queued($name);
        $this->assertNotNull($cookie);
        session()->invalidate();
        Auth::forgetGuards();

        $this->withCookie($name, $cookie->getValue())->withoutVite()
            ->get(route('client.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($user, 'client');
        $this->assertTrue(Auth::guard('client')->viaRemember());
        $this->assertNull($user->fresh()->password);
    }

    public static function invalidRememberAccounts(): array
    {
        return ['deleted' => ['deleted'], 'wrong role' => ['role'], 'revoked token' => ['token'], 'new password' => ['password'], 'unlinked Google' => ['google'], 'tampered hash' => ['hash']];
    }

    #[DataProvider('invalidRememberAccounts')]
    public function test_passwordless_remember_cookie_cannot_restore_an_ineligible_account(string $change): void
    {
        $user = $this->client();
        Auth::guard('client')->login($user, true);
        $name = Auth::guard('client')->getRecallerName();
        $cookie = Cookie::queued($name);
        match ($change) {
            'deleted' => $user->forceFill(['deleted_at' => now()])->save(),
            'role' => $user->update(['role_id' => Role::firstOrCreate(['name' => 'owner'])->id]),
            'token' => $user->forceFill(['remember_token' => 'replacement-token'])->save(),
            'password' => $user->update(['password' => 'new-password']),
            'google' => $user->userClient()->update(['google_id' => null]),
            'hash' => null,
        };
        session()->invalidate();
        Auth::forgetGuards();

        $value = $change === 'hash' ? preg_replace('/[^|]+$/', 'invalid-hash', $cookie->getValue()) : $cookie->getValue();
        $this->withCookie($name, $value)->get(route('client.dashboard'))
            ->assertRedirect(route('client.login'));
        $this->assertGuest('client');
    }

    public function test_super_admin_and_client_pages_use_different_session_cookies(): void
    {
        $this->withoutVite()->get(route('client.login'))->assertOk()
            ->assertCookie('pawpaw-client-session')->assertCookieMissing('pawpaw-super-admin-session');
        Auth::forgetGuards();
        session()->flush();

        $this->get(route('super_admin.login'))->assertOk()
            ->assertCookie('pawpaw-super-admin-session')->assertCookieMissing('pawpaw-client-session');
    }

    public function test_super_admin_livewire_requests_use_the_admin_session_context(): void
    {
        $response = $this->withoutVite()->get(route('super_admin.login'))->assertOk();
        preg_match('/data-update-uri="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $uri = html_entity_decode($matches[1]);
        $this->assertStringEndsWith('/super-admin', $uri);
        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $snapshots);
        $this->assertNotEmpty($snapshots[1] ?? null);
        $this->postJson($uri, ['components' => [[
            'snapshot' => html_entity_decode($snapshots[1]), 'updates' => [], 'calls' => [],
        ]]], ['X-Livewire' => 'true'])
            ->assertOk()->assertCookie('pawpaw-super-admin-session');
    }

    public function test_admin_livewire_requires_the_csrf_token_from_its_own_database_session(): void
    {
        config(['session.driver' => 'database']);
        $this->resetRequestState();
        $response = $this->withoutVite()->get(route('super_admin.login'))->assertOk();
        preg_match('/data-update-uri="([^"]+)"/', $response->getContent(), $uris);
        preg_match('/data-csrf="([^"]+)"/', $response->getContent(), $tokens);
        preg_match('/wire:snapshot="([^"]+)"/', $response->getContent(), $snapshots);
        $cookie = $response->getCookie('pawpaw-super-admin-session');
        $this->assertNotNull($cookie);
        $payload = ['_token' => html_entity_decode($tokens[1]), 'components' => [[
            'snapshot' => html_entity_decode($snapshots[1]), 'updates' => [], 'calls' => [],
        ]]];
        $this->app['env'] = 'local';
        $this->resetRequestState();
        $this->withCredentials();
        $this->withCookie('pawpaw-super-admin-session', $cookie->getValue())
            ->postJson(html_entity_decode($uris[1]), $payload, ['X-Livewire' => 'true'])->assertOk();
        $this->resetRequestState();
        $this->withCookie('pawpaw-super-admin-session', $cookie->getValue())
            ->postJson(str_replace('/super-admin', '/site', html_entity_decode($uris[1])), $payload, ['X-Livewire' => 'true'])
            ->assertStatus(419);
    }

    public function test_client_authentication_routes_do_not_match_vendor_subdomains(): void
    {
        $this->get('http://clinic.localhost/client-login')->assertNotFound();
        $this->get('http://clinic.localhost/client-register')->assertNotFound();
        $this->get('http://clinic.localhost/auth/google')->assertNotFound();
    }

    public static function logoutContexts(): array
    {
        return ['client logout' => ['client'], 'admin logout' => ['web']];
    }

    #[DataProvider('logoutContexts')]
    public function test_logout_only_invalidates_the_selected_database_session(string $guard): void
    {
        config(['session.driver' => 'database']);
        $this->resetRequestState();
        $client = $this->client('password123');
        $admin = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'super_admin'])->id]);
        Route::middleware('web')->post('/_tests/client-login', function (Request $request) use ($client) {
            Auth::guard('client')->login($client);

            return response()->noContent();
        });
        Route::middleware('web')->post('/super-admin/_tests/login', function (Request $request) use ($admin) {
            Auth::guard('web')->login($admin);

            return response()->noContent();
        });
        $clientCookie = $this->post('/_tests/client-login')->assertNoContent()->getCookie('pawpaw-client-session');
        $this->resetRequestState();
        $adminCookie = $this->post('/super-admin/_tests/login')->assertNoContent()->getCookie('pawpaw-super-admin-session');
        $this->assertNotNull($clientCookie);
        $this->assertNotNull($adminCookie);
        $this->assertNotSame($clientCookie->getValue(), $adminCookie->getValue());
        $this->resetRequestState();
        $this->withCookies(['pawpaw-client-session' => $clientCookie->getValue(), 'pawpaw-super-admin-session' => $adminCookie->getValue()])
            ->post(route($guard === 'client' ? 'client.logout' : 'super_admin.logout'))->assertRedirect();
        $this->resetRequestState();
        $this->withCookies(['pawpaw-client-session' => $clientCookie->getValue(), 'pawpaw-super-admin-session' => $adminCookie->getValue()])
            ->withoutVite()->get(route($guard === 'client' ? 'super_admin.dashboard' : 'client.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($guard === 'client' ? $admin : $client, $guard === 'client' ? 'web' : 'client');
        $this->resetRequestState();
        $this->get(route($guard === 'client' ? 'client.dashboard' : 'super_admin.dashboard'))
            ->assertRedirect(route($guard === 'client' ? 'client.login' : 'super_admin.login'));
    }

    private function resetRequestState(): void
    {
        Auth::forgetGuards();
        app('session')->forgetDrivers();
        $this->app->forgetInstance('session.store');
    }

    public static function legacyPhoneNumbers(): array
    {
        return ['eight digits' => ['12345678'], 'nine digits' => ['123456789'], 'international' => ['+123456789012345'], 'old maximum' => [str_repeat('1', 20)]];
    }

    #[DataProvider('legacyPhoneNumbers')]
    public function test_existing_clients_can_login_with_legacy_phone_numbers(string $phone): void
    {
        $user = $this->client('password123');
        $user->update(['phone_number' => $phone]);
        Livewire::test('client.auth.login')->set('identifier', $phone)->set('password', 'password123')
            ->call('login')->assertHasNoErrors()->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($user, 'client');
    }
}
