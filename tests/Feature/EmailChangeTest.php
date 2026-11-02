<?php

namespace Tests\Feature;

use App\Actions\Account\CancelEmailChangeAction;
use App\Actions\Account\RequestEmailChangeAction;
use App\Actions\Account\ResendEmailChangeAction;
use App\Actions\Account\UpdatePasswordAction;
use App\Actions\Client\LoginGoogleClientAction;
use App\DTOs\GoogleClientData;
use App\DTOs\RequestEmailChangeData;
use App\DTOs\UpdatePasswordData;
use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use App\Models\Vendor;
use App\Models\VendorStaff;
use App\Notifications\ClientResetPassword;
use App\Notifications\ConfirmEmailChange;
use App\Services\EmailChangeService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    private ?Vendor $vendor = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'app.domain' => 'localhost']);
        Notification::fake();
        $this->withoutVite();
    }

    private function account(string $roleName = 'client', ?string $password = 'password-lama'): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'akun_'.fake()->unique()->numberBetween(10000, 99999),
            'password' => $password,
            'phone_number' => fake()->unique()->numerify('08##########'),
        ]);

        if ($roleName === 'owner' || $roleName === 'front_office') {
            $this->vendor = Vendor::factory()->create();
            app('vendor.context')->set($this->vendor);
            URL::defaults(['subdomain' => $this->vendor->slug]);
            VendorStaff::create([
                'user_id' => $user->id, 'vendor_id' => $this->vendor->id,
                'position' => $roleName === 'owner' ? StaffPosition::Owner : StaffPosition::FrontOffice,
            ]);
        } else {
            UserClient::create(['user_id' => $user->id, 'google_id' => $password === null ? 'google-account-id' : null]);
        }

        return $user;
    }

    private function requestChange(User $user, string $email = 'baru@example.test'): string
    {
        app(RequestEmailChangeAction::class)->execute($user, new RequestEmailChangeData($email, 'password-lama', $this->vendor?->id));

        return Notification::sent(new AnonymousNotifiable, ConfirmEmailChange::class)->last()->url;
    }

    private function assertValidationFailure(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail('Operasi seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    public static function accountRoles(): array
    {
        return [['client'], ['owner']];
    }

    #[DataProvider('accountRoles')]
    public function test_email_stays_active_until_explicit_confirmation_and_link_is_single_use(string $role): void
    {
        $user = $this->account($role);
        $oldEmail = $user->email;
        $vendorEmail = $this->vendor?->email;
        $url = $this->requestChange($user, 'BARU@example.test');

        $this->assertSame($oldEmail, $user->fresh()->email);
        $this->assertSame('baru@example.test', $user->fresh()->pending_email);
        $this->assertStringNotContainsString($user->email_change_token, $url);
        Notification::assertSentOnDemand(ConfirmEmailChange::class, function ($notification, $channels, $notifiable): bool {
            return $notifiable->routes['mail'] === 'baru@example.test'
                && str_contains($notification->toMail($notifiable)->render(), 'Konfirmasi Email Baru');
        });

        $this->get($url)->assertOk()->assertSee('Konfirmasi perubahan email');
        $this->assertSame($oldEmail, $user->fresh()->email);
        $this->post($url)->assertOk()->assertSee('Email akun berhasil diperbarui');
        $this->assertSame('baru@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->pending_email);
        $this->post($url)->assertGone();
        if ($this->vendor) {
            $this->assertSame($vendorEmail, $this->vendor->fresh()->email);
        }
    }

    #[DataProvider('accountRoles')]
    public function test_profile_form_requires_password_for_email_change_only(string $role): void
    {
        $user = $this->account($role);
        $guard = $role === 'client' ? 'client' : 'vendor';
        $page = $role === 'client' ? 'client.profile_client.profile-client' : 'vendor.owner_profile.owner-profile';
        $oldEmail = $user->email;

        Livewire::actingAs($user, $guard)->test($page)
            ->set('formData.email', 'baru@example.test')
            ->set('emailChangePassword', 'salah')
            ->call('save')
            ->assertHasErrors(['emailChangePassword']);
        $this->assertSame($oldEmail, $user->fresh()->email);
        $this->assertNull($user->fresh()->pending_email);
        Notification::assertNothingSent();

        Livewire::actingAs($user, $guard)->test($page)
            ->set('formData.name', 'Nama Profil Baru')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Nama Profil Baru', $user->fresh()->name);
        Notification::assertNothingSent();
    }

    public function test_google_client_must_create_password_then_can_request_email_change(): void
    {
        $user = $this->account(password: null);
        $page = Livewire::actingAs($user, 'client')->test('client.profile_client.profile-client')
            ->assertSee('Tambahkan kata sandi PawPaw')
            ->assertSee('Buat kata sandi PawPaw terlebih dahulu')
            ->set('formData.email', 'baru@example.test')
            ->call('save')->assertHasErrors(['formData.email'])->assertSet('isChangingPassword', true)
            ->assertSet('formData.email', 'baru@example.test')
            ->assertDispatched('client-password-form-opened')
            ->assertDispatched('toast-show', fn (string $event, array $params): bool => $params['dataset']['variant'] === 'warning'
                && $params['slots']['text'] === 'Buat kata sandi PawPaw terlebih dahulu sebelum mengganti email.');
        Notification::assertNothingSent();

        $page->set('newPassword', 'password-lama')->set('newPasswordConfirmation', 'password-lama')
            ->call('updatePassword')->assertHasNoErrors()
            ->assertDontSee('Tambahkan kata sandi PawPaw')
            ->assertSet('formData.email', 'baru@example.test')
            ->assertDispatched('toast-show', fn (string $event, array $params): bool => $params['slots']['text'] === 'Kata sandi berhasil dibuat. Kamu sekarang dapat melanjutkan perubahan email.');
        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
        $page->set('emailChangePassword', 'password-lama')->call('save')->assertHasNoErrors();
        $this->assertSame('baru@example.test', $user->fresh()->pending_email);
        $this->assertSame('google-account-id', $user->userClient->google_id);
    }

    public function test_google_client_without_password_is_also_rejected_by_action(): void
    {
        $user = $this->account(password: null);
        $this->assertValidationFailure(fn () => $this->requestChange($user), 'formData.email');
        $this->assertNull($user->fresh()->pending_email);
        Notification::assertNothingSent();
    }

    #[DataProvider('accountRoles')]
    public function test_resend_is_limited_and_invalidates_previous_link(string $role): void
    {
        $user = $this->account($role);
        $oldUrl = $this->requestChange($user);
        $this->assertValidationFailure(fn () => app(ResendEmailChangeAction::class)->execute($user, 'password-lama', $this->vendor?->id), 'formData.email');
        $this->travel(61)->seconds();
        app(ResendEmailChangeAction::class)->execute($user, 'password-lama', $this->vendor?->id);
        $newUrl = Notification::sent(new AnonymousNotifiable, ConfirmEmailChange::class)->last()->url;
        $this->assertNotSame($oldUrl, $newUrl);
        $this->get($oldUrl)->assertGone();
        $this->post($newUrl)->assertOk();
    }

    #[DataProvider('accountRoles')]
    public function test_cancel_invalidates_pending_request(string $role): void
    {
        $user = $this->account($role);
        $oldEmail = $user->email;
        $url = $this->requestChange($user);
        app(CancelEmailChangeAction::class)->execute($user, $this->vendor?->id);
        $this->post($url)->assertGone();
        $this->assertSame($oldEmail, $user->fresh()->email);
        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_new_request_replaces_previous_pending_email(): void
    {
        $user = $this->account();
        $oldUrl = $this->requestChange($user);
        $this->travel(61)->seconds();
        $newUrl = $this->requestChange($user, 'kedua@example.test');
        $this->post($oldUrl)->assertGone();
        $this->post($newUrl)->assertOk();
        $this->assertSame('kedua@example.test', $user->fresh()->email);
    }

    public function test_expired_and_tampered_links_cannot_change_email(): void
    {
        $user = $this->account();
        $oldEmail = $user->email;
        $url = $this->requestChange($user);
        $this->post($url.'&tampered=1')->assertGone();
        $this->travel(61)->minutes();
        $this->get($url)->assertGone();
        $this->post($url)->assertGone();
        $this->assertSame($oldEmail, $user->fresh()->email);
    }

    #[DataProvider('accountRoles')]
    public function test_email_uniqueness_is_rechecked_at_confirmation(string $role): void
    {
        $user = $this->account($role);
        $oldEmail = $user->email;
        $url = $this->requestChange($user);
        User::factory()->create(['role_id' => $user->role_id, 'email' => 'baru@example.test']);
        $this->post($url)->assertGone()->assertSee('Email baru sudah digunakan');
        $this->assertSame($oldEmail, $user->fresh()->email);
    }

    public function test_mitra_email_cannot_be_used_in_request_or_confirmation(): void
    {
        $user = $this->account();
        Vendor::factory()->create(['email' => 'mitra@example.test']);
        $this->assertValidationFailure(fn () => $this->requestChange($user, 'mitra@example.test'), 'formData.email');
        $url = $this->requestChange($user);
        Vendor::factory()->create(['email' => 'baru@example.test']);
        $this->post($url)->assertGone();
    }

    public function test_delivery_failure_preserves_previous_request_and_allows_retry(): void
    {
        $user = $this->account();
        $url = $this->requestChange($user);
        $oldToken = $user->email_change_token;
        $this->travel(61)->seconds();
        $service = $this->mock(EmailChangeService::class);
        $service->shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->assertValidationFailure(fn () => $this->requestChange($user, 'kedua@example.test'), 'formData.email');
        $this->assertSame($oldToken, $user->fresh()->email_change_token);
        $this->get($url)->assertOk();
        $service->shouldReceive('send')->once()->andReturnNull();
        $this->requestChange($user, 'kedua@example.test');
        $this->assertSame('kedua@example.test', $user->fresh()->pending_email);
    }

    public function test_wrong_password_attempts_are_rate_limited(): void
    {
        $user = $this->account();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->assertValidationFailure(fn () => app(RequestEmailChangeAction::class)->execute($user, new RequestEmailChangeData('baru@example.test', 'salah')), 'emailChangePassword');
        }
        $this->assertValidationFailure(fn () => $this->requestChange($user), 'emailChangePassword');
        Notification::assertNothingSent();
    }

    public function test_front_office_cannot_request_owner_email_change(): void
    {
        $user = $this->account('front_office');
        $this->expectException(HttpException::class);
        $this->requestChange($user);
    }

    public function test_owner_cannot_request_change_under_another_tenant(): void
    {
        $user = $this->account('owner');
        $other = Vendor::factory()->create();
        $this->expectException(HttpException::class);
        app(RequestEmailChangeAction::class)->execute($user, new RequestEmailChangeData('baru@example.test', 'password-lama', $other->id));
    }

    public function test_owner_link_becomes_invalid_if_membership_is_removed(): void
    {
        $user = $this->account('owner');
        $url = $this->requestChange($user);
        VendorStaff::where('user_id', $user->id)->delete();
        $this->post($url)->assertGone();
    }

    public function test_password_change_invalidates_pending_email_change(): void
    {
        $user = $this->account();
        $url = $this->requestChange($user);
        app(UpdatePasswordAction::class)->execute($user, new UpdatePasswordData('password-lama', 'password-baru', 'password-baru'));
        $this->post($url)->assertGone();
    }

    public function test_changed_or_deleted_account_cannot_confirm_stale_request(): void
    {
        $user = $this->account();
        $url = $this->requestChange($user);
        $user->update(['email' => 'diubah-admin@example.test']);
        $this->post($url)->assertGone();
        $user->forceFill(['email' => $user->email_change_original_email, 'deleted_at' => now()])->save();
        $this->post($url)->assertGone();
    }

    public function test_google_login_still_finds_same_account_after_email_change(): void
    {
        $user = $this->account();
        $user->userClient->update(['google_id' => 'same-google-id']);
        $googleEmail = $user->email;
        $url = $this->requestChange($user);
        $this->post($url)->assertOk();

        $loggedIn = app(LoginGoogleClientAction::class)->execute(new GoogleClientData(
            googleId: 'same-google-id', email: $googleEmail, emailVerified: true,
        ));
        $this->assertSame($user->id, $loggedIn->id);
        $this->assertSame('baru@example.test', $loggedIn->email);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_cancellation_does_not_bypass_delivery_cooldown(): void
    {
        $user = $this->account();
        $this->requestChange($user);
        app(CancelEmailChangeAction::class)->execute($user);
        $this->assertValidationFailure(fn () => $this->requestChange($user), 'formData.email');
        Notification::assertSentOnDemandTimes(ConfirmEmailChange::class, 1);
    }

    public function test_password_reset_outside_profile_also_invalidates_link(): void
    {
        $user = $this->account();
        $url = $this->requestChange($user);
        $user->update(['password' => 'password-setelah-reset']);
        $this->post($url)->assertGone();
    }

    public function test_front_office_can_keep_editing_other_profile_fields(): void
    {
        $user = $this->account('front_office');
        Livewire::actingAs($user, 'vendor')->test('vendor.owner_profile.owner-profile')
            ->set('formData.name', 'Nama Front Office')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Nama Front Office', $user->fresh()->name);
        Notification::assertNothingSent();
    }

    public function test_invalid_email_request_does_not_delete_existing_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/lama.webp', 'existing-image');
        $user = $this->account();
        $user->update(['avatar' => 'avatars/lama.webp']);

        Livewire::actingAs($user, 'client')->test('client.profile_client.profile-client')
            ->set('avatar', UploadedFile::fake()->image('baru.png'))
            ->set('formData.email', 'baru@example.test')
            ->set('emailChangePassword', 'salah')
            ->call('save')->assertHasErrors(['emailChangePassword']);
        $this->assertSame('avatars/lama.webp', $user->fresh()->avatar);
        Storage::disk('public')->assertExists('avatars/lama.webp');
        $this->assertCount(1, Storage::disk('public')->files('avatars'));
    }

    #[DataProvider('accountRoles')]
    public function test_pending_controls_can_resend_and_cancel_from_profile(string $role): void
    {
        $user = $this->account($role);
        $this->requestChange($user);
        $this->travel(61)->seconds();
        $guard = $role === 'client' ? 'client' : 'vendor';
        $page = $role === 'client' ? 'client.profile_client.profile-client' : 'vendor.owner_profile.owner-profile';
        Livewire::actingAs($user, $guard)->test($page)
            ->assertSee('Menunggu konfirmasi ke')
            ->set('emailChangePassword', 'password-lama')
            ->call('resendEmailChange')->assertHasNoErrors()
            ->call('cancelEmailChange')->assertHasNoErrors()
            ->assertDontSee('Menunggu konfirmasi ke');
        $this->assertNull($user->fresh()->pending_email);
        Notification::assertSentOnDemandTimes(ConfirmEmailChange::class, 2);
    }

    #[DataProvider('accountRoles')]
    public function test_password_throttle_survives_profile_transaction_with_database_cache(string $role): void
    {
        \Illuminate\Support\Facades\RateLimiter::swap(new RateLimiter(
            Cache::store('database'),
        ));
        $user = $this->account($role);
        $guard = $role === 'client' ? 'client' : 'vendor';
        $page = $role === 'client' ? 'client.profile_client.profile-client' : 'vendor.owner_profile.owner-profile';
        $form = Livewire::actingAs($user, $guard)->test($page)->call('startEditing')->set('formData.email', 'baru@example.test');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $form->set('emailChangePassword', 'salah')->call('save')->assertHasErrors(['emailChangePassword']);
        }
        $form->set('emailChangePassword', 'password-lama')->call('save')
            ->assertHasErrors(['emailChangePassword'])->assertSee('Terlalu banyak percobaan');
        Notification::assertNothingSent();
    }

    public function test_client_email_001_reset_email_uses_transparent_png_header(): void
    {
        $user = $this->account();
        $mail = (new ClientResetPassword('test-reset-token'))->toMail($user);
        $html = (string) $mail->render();
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Atur ulang password Anda', $html);
    }

    public function test_client_email_002_confirmation_uses_pawpaw_email_theme(): void
    {
        $url = 'https://example.test/account/email-change/1/test-token';
        $mail = (new ConfirmEmailChange($url))->toMail(new AnonymousNotifiable);
        $html = (string) $mail->render();
        $this->assertStringContainsString('Konfirmasi perubahan email Anda', $html);
        $this->assertStringContainsString('background-color:#fff8f5', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Tim PawPaw', (string) view($mail->view['text'], $mail->data())->render());
        $this->assertStringContainsString($url, $html);
    }

    public function test_email_logo_is_inline_png_with_transparent_background(): void
    {
        config(['mail.default' => 'array']);
        $user = $this->account();
        app(MailChannel::class)->send($user, new ClientResetPassword('test-reset-token'));

        $email = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $header = collect($email->getAttachments())->first(fn ($attachment): bool => $attachment->getFilename() === 'pawpaw-email-logo.png');
        $this->assertNotNull($header);
        $this->assertSame('image', $header->getMediaType());
        $this->assertSame('png', $header->getMediaSubtype());
        $this->assertSame('inline', $header->getDisposition());
        $this->assertStringContainsString('cid:'.$header->getContentId(), $email->getHtmlBody());

        $image = imagecreatefrompng(public_path('assets/img/pawpaw-email-logo.png'));
        $corner = imagecolorsforindex($image, imagecolorat($image, 0, 0));
        $this->assertSame(127, $corner['alpha']);
    }
}
