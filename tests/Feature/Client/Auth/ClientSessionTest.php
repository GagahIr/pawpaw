<?php

namespace Tests\Feature\Client\Auth;

use App\Actions\Client\LoginClientAction;
use App\DTOs\LoginClientData;
use App\Http\Middleware\ConfigureClientSession;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'session.expire_on_close' => false,
            'session.lifetime' => 120,
        ]);
    }

    private function user(string $roleName = 'client'): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(['role_id' => $role->id, 'password' => 'password123']);

        if ($roleName === 'client') {
            UserClient::create(['user_id' => $user->id]);
        }

        return $user;
    }

    private function registerRoutes(): void
    {
        Route::middleware('web')->post('/_tests/client-session-login', function (Request $request, LoginClientAction $action) {
            $action->execute(new LoginClientData(
                identifier: $request->string('identifier')->toString(),
                password: $request->string('password')->toString(),
                remember: $request->boolean('remember'),
            ));

            return response()->noContent();
        });
        Route::middleware('web')->get('/_tests/session-cookie', fn () => response()->noContent());
    }

    public function test_client_without_remember_receives_session_cookie_and_cannot_restore_login_without_it(): void
    {
        $user = $this->user();
        $this->registerRoutes();

        $response = $this->post('/_tests/client-session-login', [
            'identifier' => $user->email, 'password' => 'password123', 'remember' => false,
        ]);

        $response->assertNoContent()->assertCookie(config('session.cookie'))
            ->assertCookieMissing(Auth::guard()->getRecallerName());
        $this->assertSame(0, $response->getCookie(config('session.cookie'))->getExpiresTime());
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->userClient->remember_token);

        session()->invalidate();
        Auth::forgetGuards();
        $this->get(route('client.dashboard'))->assertRedirect(route('client.login'));
    }

    public function test_client_with_remember_keeps_persistent_recaller_and_can_restore_login(): void
    {
        $user = $this->user();
        $this->registerRoutes();

        $response = $this->post('/_tests/client-session-login', [
            'identifier' => $user->email, 'password' => 'password123', 'remember' => true,
        ]);

        $recallerName = Auth::guard()->getRecallerName();
        $response->assertNoContent()->assertCookie($recallerName);
        $this->assertSame(0, $response->getCookie(config('session.cookie'))->getExpiresTime());
        $this->assertGreaterThan(now()->timestamp, $response->getCookie($recallerName)->getExpiresTime());
        $recaller = $response->getCookie($recallerName)->getValue();

        session()->invalidate();
        Auth::forgetGuards();
        $this->withCookie($recallerName, $recaller)->withoutVite()->get(route('client.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Auth::viaRemember());
    }

    public static function rememberChoices(): array
    {
        return ['without remember' => [false], 'with remember' => [true]];
    }

    #[DataProvider('rememberChoices')]
    public function test_google_login_issues_session_cookie_and_respects_remember_choice(bool $remember): void
    {
        $user = $this->user();
        $user->forceFill(['email' => 'session@gmail.com'])->save();
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'session-google-id', 'email' => $user->email, 'email_verified' => true,
            ]),
        ]);

        $response = $this->withSession(['client_google_oauth' => [
            'state' => 'session-state', 'verifier' => str_repeat('a', 64),
            'started_at' => now()->timestamp, 'remember' => $remember,
        ]])->get(route('client.google.callback', ['state' => 'session-state', 'code' => 'test-code']));

        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, $response->getCookie(config('session.cookie'))->getExpiresTime());
        if ($remember) {
            $response->assertCookie(Auth::guard()->getRecallerName());
            $this->assertGreaterThan(now()->timestamp, $response->getCookie(Auth::guard()->getRecallerName())->getExpiresTime());
        } else {
            $response->assertCookieMissing(Auth::guard()->getRecallerName());
        }
        Http::assertSentCount(2);
    }

    public static function nonClientRoles(): array
    {
        return ['guest' => [null], 'owner' => ['owner'], 'staff' => ['front_office']];
    }

    #[DataProvider('nonClientRoles')]
    public function test_non_client_session_cookie_attributes_are_identical_with_or_without_middleware(?string $roleName): void
    {
        $this->freezeTime();
        $this->registerRoutes();
        if ($roleName !== null) {
            $this->actingAs($this->user($roleName));
        }

        $baseline = $this->withoutMiddleware(ConfigureClientSession::class)
            ->get('/_tests/session-cookie')->assertNoContent()->getCookie(config('session.cookie'));
        $actual = $this->withMiddleware()->get('/_tests/session-cookie')->assertNoContent()
            ->getCookie(config('session.cookie'));

        $this->assertSame((string) $baseline->withValue(null), (string) $actual->withValue(null));
        $this->assertSame(now()->addMinutes(120)->timestamp, $actual->getExpiresTime());
    }

    public function test_client_request_does_not_change_following_vendor_request_or_global_config(): void
    {
        $client = $this->user();
        $vendor = $this->user('owner');
        $this->registerRoutes();
        $this->freezeTime();

        $clientResponse = $this->actingAs($client)->get('/_tests/session-cookie')->assertNoContent();
        $vendorResponse = $this->actingAs($vendor)->get('/_tests/session-cookie')->assertNoContent();

        $this->assertSame(0, $clientResponse->getCookie(config('session.cookie'))->getExpiresTime());
        $this->assertSame(now()->addMinutes(120)->timestamp, $vendorResponse->getCookie(config('session.cookie'))->getExpiresTime());
        $this->assertFalse(config('session.expire_on_close'));
    }
}
