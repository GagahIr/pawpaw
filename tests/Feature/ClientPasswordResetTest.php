<?php

namespace Tests\Feature;

use App\Enums\SystemMessage;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ClientResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Notification::fake();
    }

    private function user(string $roleName = 'client', array $overrides = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(array_replace([
            'email' => 'amanda@example.com', 'phone_number' => '081234567890',
            'role_id' => $role->id, 'password' => 'password123', 'remember_token' => 'old-remember-token',
        ], $overrides));
    }

    private function form(User $user, string $token, array $overrides = []): Testable
    {
        $form = Livewire::test('client.client-reset-password-form', ['token' => $token]);
        foreach (array_replace([
            'email' => $user->email, 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ], $overrides) as $field => $value) {
            $form->set($field, $value);
        }

        return $form;
    }

    public function test_client_receives_notification_with_valid_link_and_hashed_token(): void
    {
        $user = $this->user();
        Livewire::test('client.client-forgot-password-form')->set('email', ' AMANDA@example.com ')
            ->call('sendResetLink')->assertHasNoErrors()
            ->assertDispatched('client-registration-notification', type: 'success');

        Notification::assertSentTo($user, ClientResetPassword::class, function (ClientResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $this->assertSame(rtrim(config('app.url'), '/').route('client.password.reset', [
                'token' => $notification->token, 'email' => $user->email,
            ], false), $mail->actionUrl);
            $this->assertTrue(Password::tokenExists($user, $notification->token));
            $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
            $this->assertNotSame($notification->token, $stored);
            $this->assertStringContainsString('Atur Password Baru', $mail->render());

            return true;
        });
    }

    public function test_unknown_email_shows_same_success_without_sending_email(): void
    {
        Livewire::test('client.client-forgot-password-form')->set('email', 'unknown@example.com')
            ->call('sendResetLink')->assertHasNoErrors()->assertDispatched('client-registration-notification',
                type: 'success', message: 'Jika email terdaftar sebagai pelanggan, tautan reset password akan dikirim. Periksa email Anda.');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public static function invalidEmails(): array
    {
        return ['blank' => ['', 'required'], 'invalid' => ['invalid', 'email'], 'too long' => [str_repeat('a', 245).'@example.com', 'max']];
    }

    #[DataProvider('invalidEmails')]
    public function test_invalid_email_does_not_send_notification(string $email, string $rule): void
    {
        Livewire::test('client.client-forgot-password-form')->set('email', $email)->call('sendResetLink')
            ->assertHasErrors(['email' => $rule])->assertDispatched('client-registration-notification', type: 'error');
        Notification::assertNothingSent();
    }

    public static function rejectedAccounts(): array
    {
        return ['owner' => ['owner', []], 'front office' => ['front_office', []], 'deleted client' => ['client', ['deleted_at' => '2026-01-01 00:00:00']]];
    }

    #[DataProvider('rejectedAccounts')]
    public function test_other_roles_and_deleted_accounts_cannot_receive_or_use_reset(string $role, array $attributes): void
    {
        $user = $this->user($role, $attributes);
        Livewire::test('client.client-forgot-password-form')->set('email', $user->email)->call('sendResetLink')
            ->assertHasNoErrors()->assertDispatched('client-registration-notification', type: 'success');
        Notification::assertNothingSent();

        $token = Password::createToken($user);
        $this->form($user, $token)->call('resetPassword')->assertHasErrors(['email'])->assertNoRedirect();
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_repeat_requests_do_not_send_duplicate_emails_and_ip_is_throttled(): void
    {
        $user = $this->user();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            Livewire::test('client.client-forgot-password-form')->set('email', $user->email)->call('sendResetLink')->assertHasNoErrors();
        }
        Notification::assertSentToTimes($user, ClientResetPassword::class, 1);
        Livewire::test('client.client-forgot-password-form')->set('email', $user->email)->call('sendResetLink')
            ->assertHasErrors(['email'])->assertDispatched('client-registration-notification', type: 'error');
    }

    public function test_reset_updates_password_revokes_sessions_and_token_cannot_be_reused(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        DB::table('sessions')->insert([
            ['id' => 'old-client-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'guest-session', 'user_id' => null, 'payload' => '', 'last_activity' => now()->timestamp],
        ]);
        $this->form($user, $token)->call('resetPassword')->assertHasNoErrors()->assertSet('password', '')
            ->assertRedirect(route('client.login'));
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
        $this->assertFalse(Hash::check('password123', $user->fresh()->password));
        $this->assertNotSame('old-remember-token', $user->fresh()->remember_token);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-client-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'guest-session']);
        $this->assertGuest();

        $this->form($user, $token)->call('resetPassword')->assertHasErrors(['email']);
        Livewire::test('client.client-login-form')->set('identifier', $user->email)->set('password', 'newpassword123')
            ->call('login')->assertHasNoErrors()->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_old_remember_cookie_cannot_restore_login_after_reset(): void
    {
        $user = $this->user();
        $cookieName = Auth::guard()->getRecallerName();
        $cookieValue = $user->id.'|old-remember-token|'.$user->password;
        $this->form($user, Password::createToken($user))->call('resetPassword')->assertHasNoErrors();
        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookieValue)->withoutVite()->get('/')->assertOk();
        $this->assertGuest();
    }

    public function test_expired_and_wrong_tokens_are_rejected(): void
    {
        $this->freezeTime();
        $user = $this->user();
        $token = Password::createToken($user);
        $this->form($user, 'wrong-token')->call('resetPassword')->assertHasErrors(['email']);
        $this->travel(61)->minutes();
        $this->form($user, $token)->call('resetPassword')->assertHasErrors(['email'])
            ->assertDispatched('client-registration-notification', type: 'error');
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_token_cannot_reset_a_different_email(): void
    {
        $user = $this->user();
        $this->form($user, Password::createToken($user), ['email' => 'other@example.com'])->call('resetPassword')->assertHasErrors(['email']);
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public static function invalidPasswords(): array
    {
        return [
            'short' => ['short', 'short', 'min'],
            'mismatch' => ['newpassword123', 'differentpassword', 'confirmed'],
            'too long' => [str_repeat('a', 73), str_repeat('a', 73), 'max'],
        ];
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_passwords_leave_existing_password_unchanged(string $password, string $confirmation, string $rule): void
    {
        $user = $this->user();
        $this->form($user, Password::createToken($user), ['password' => $password, 'password_confirmation' => $confirmation])
            ->call('resetPassword')->assertHasErrors(['password' => $rule]);
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_multibyte_password_over_72_bytes_is_rejected(): void
    {
        $user = $this->user();
        $password = str_repeat('é', 37);
        $this->form($user, Password::createToken($user), ['password' => $password, 'password_confirmation' => $password])
            ->call('resetPassword')->assertHasErrors(['password']);
    }

    public function test_failed_email_delivery_shows_error_and_allows_retry_with_new_token(): void
    {
        $user = $this->user();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Simulated SMTP failure'));
        Livewire::test('client.client-forgot-password-form')->set('email', $user->email)->call('sendResetLink')
            ->assertDispatched('client-registration-notification', type: 'error', message: SystemMessage::ERROR_SYSTEM->value);
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_password_pages_render_and_login_link_is_connected(): void
    {
        $this->withoutVite()->get(route('client.login'))->assertOk()->assertSee(route('client.password.request'), false);
        $this->get(route('client.password.request'))->assertOk()->assertSee('Kirim Tautan Reset Password');
        $this->get(route('client.password.reset', ['token' => 'test-token', 'email' => 'amanda@example.com']))
            ->assertOk()->assertSee('Atur Password Baru');
        $this->actingAs($this->user())->get(route('client.password.request'))->assertRedirect('/');
    }
}
