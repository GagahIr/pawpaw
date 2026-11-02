<?php

namespace Tests\Feature\Client\Profile;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'app.domain' => 'localhost']);

        $this->client = $this->makeClient([
            'name' => 'Amanda Dealova Putri',
            'username' => 'amanda',
            'email' => 'amanda@example.test',
            'phone_number' => '081234567890',
            'password' => 'password-lama',
        ]);
    }

    private function makeClient(array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => 'client']);

        return User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(array_replace(['role_id' => $role->id], $attributes));
    }

    private function profileComponent()
    {
        $this->client->refresh();

        return Livewire::actingAs($this->client, 'client')->test('client.profile_client.profile-client');
    }

    public function test_guest_is_redirected_to_client_login(): void
    {
        $this->get(route('client.profile'))->assertRedirect(route('client.login'));
    }

    public function test_client_sees_profile_page_in_client_layout(): void
    {
        $this->actingAs($this->client, 'client')
            ->withoutVite()
            ->get(route('client.profile'))
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Amanda Dealova Putri');
    }

    public function test_client_can_update_profile_information(): void
    {
        $this->profileComponent()
            ->call('startEditing')
            ->set('formData.name', 'Amanda Putri')
            ->set('formData.username', 'Amanda_P')
            ->set('formData.email', 'AMANDA.BARU@example.test')
            ->set('formData.phone_number', '081234567891')
            ->set('emailChangePassword', 'password-lama')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isEditing', false);

        $this->client->refresh();

        $this->assertSame('Amanda Putri', $this->client->name);
        $this->assertSame('amanda_p', $this->client->username);
        $this->assertSame('amanda@example.test', $this->client->email);
        $this->assertSame('amanda.baru@example.test', $this->client->pending_email);
        $this->assertSame('081234567891', $this->client->phone_number);
    }

    public function test_phone_number_is_optional(): void
    {
        $this->profileComponent()
            ->set('formData.phone_number', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->client->refresh()->phone_number);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->profileComponent()
            ->set('formData.name', '')
            ->set('formData.username', '')
            ->set('formData.email', 'bukan-email')
            ->call('save')
            ->assertHasErrors(['formData.name', 'formData.username', 'formData.email']);
    }

    public function test_email_username_and_phone_must_be_unique(): void
    {
        $this->makeClient([
            'email' => 'lain@example.test',
            'username' => 'lainnya',
            'phone_number' => '089999999999',
        ]);

        $this->profileComponent()
            ->set('formData.email', 'lain@example.test')
            ->set('formData.username', 'lainnya')
            ->set('formData.phone_number', '089999999999')
            ->call('save')
            ->assertHasErrors(['formData.email', 'formData.username', 'formData.phone_number']);
    }

    public function test_client_can_upload_avatar(): void
    {
        Storage::fake('public');

        $this->profileComponent()
            ->set('avatar', UploadedFile::fake()->image('foto.png', 200, 200))
            ->assertHasNoErrors()
            ->call('save')
            ->assertHasNoErrors();

        $path = $this->client->refresh()->avatar;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_client_can_change_password(): void
    {
        $this->profileComponent()
            ->call('startChangingPassword')
            ->set('currentPassword', 'password-lama')
            ->set('newPassword', 'password-baru-123')
            ->set('newPasswordConfirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('isChangingPassword', false);

        $this->assertTrue(Hash::check('password-baru-123', $this->client->refresh()->password));
    }

    public function test_client_with_password_does_not_see_password_reminder(): void
    {
        $this->profileComponent()->assertDontSee('Tambahkan kata sandi PawPaw');
    }

    public function test_password_form_navigation_opens_form_without_discarding_email_draft(): void
    {
        $this->client->update(['password' => null]);

        $this->profileComponent()
            ->assertSee('href="#keamanan-akun"', false)
            ->set('formData.email', 'email.baru@example.test')
            ->call('startChangingPassword')
            ->assertSet('isChangingPassword', true)
            ->assertSet('formData.email', 'email.baru@example.test')
            ->assertDispatched('client-password-form-opened')
            ->assertSee('id="client-new-password"', false)
            ->assertDontSee('id="client-current-password"', false);
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $this->profileComponent()
            ->set('currentPassword', 'salah-total')
            ->set('newPassword', 'password-baru-123')
            ->set('newPasswordConfirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password-lama', $this->client->refresh()->password));
    }

    public function test_client_profile_002_keeps_password_for_resending_confirmation(): void
    {
        $page = $this->profileComponent()->call('startEditing')
            ->set('formData.email', 'baru@example.test')
            ->set('emailChangePassword', 'password-lama')
            ->call('save')->assertHasNoErrors()
            ->assertSet('emailChangePassword', 'password-lama');
        $this->travel(61)->seconds();
        $page->call('resendEmailChange')->assertHasNoErrors()
            ->assertSet('emailChangePassword', 'password-lama');
        $page->call('cancelEmailChange')->assertSet('emailChangePassword', '');
    }

    public function test_client_profile_003_countdown_tracks_server_cooldown_after_reload(): void
    {
        $this->freezeTime();
        $this->profileComponent()->set('formData.email', 'baru@example.test')
            ->set('emailChangePassword', 'password-lama')->call('save')
            ->assertSee('data-email-change-countdown="60"', false);
        $this->travel(30)->seconds();
        $this->profileComponent()->assertSee('data-email-change-countdown="30"', false);
        $this->travel(31)->seconds();
        $this->profileComponent()->assertSee('data-email-change-countdown="0"', false);
    }

    public function test_client_profile_004_password_actions_have_single_cancel_and_header_submit(): void
    {
        $page = $this->profileComponent()->call('startChangingPassword');
        $section = substr($page->html(), strpos($page->html(), 'id="keamanan-akun"'));
        $this->assertSame(1, substr_count($section, 'wire:click="cancelChangingPassword"'));
        $this->assertStringContainsString('form="client-password-form"', $section);
        $this->assertLessThan(strpos($section, '<form'), strpos($section, 'form="client-password-form"'));
    }

    public static function invalidProfilePhones(): array
    {
        return [
            'letters' => ['abcdefgh'],
            'too short' => ['1234567'],
            'zero' => ['0'],
            'too long' => ['1234567890123456'],
            'prefix' => ['+62812345678'],
            'spaces' => ['0812 3456 7891'],
        ];
    }

    #[DataProvider('invalidProfilePhones')]
    public function test_client_profile_005_rejects_non_digits_and_invalid_length(string $phone): void
    {
        $this->profileComponent()->set('formData.phone_number', $phone)->call('save')
            ->assertHasErrors(['formData.phone_number']);
        $this->assertSame('081234567890', $this->client->fresh()->phone_number);
    }

    public function test_client_profile_005_accepts_eight_and_fifteen_digits(): void
    {
        foreach (['12345678', '123456789012345'] as $phone) {
            $this->profileComponent()->set('formData.phone_number', $phone)->call('save')->assertHasNoErrors();
            $this->assertSame($phone, $this->client->fresh()->phone_number);
        }
    }

    public function test_client_profile_006_empty_password_validation_is_in_indonesian(): void
    {
        $this->profileComponent()->call('startChangingPassword')->call('updatePassword')
            ->assertHasErrors(['currentPassword', 'newPassword', 'newPasswordConfirmation'])
            ->assertSee('Kata sandi saat ini harus diisi.')
            ->assertSee('Kata sandi baru harus diisi.')
            ->assertSee('Konfirmasi kata sandi baru harus diisi.')
            ->assertDontSee('field is required');
    }

    public function test_both_profile_sections_have_one_header_action_pair(): void
    {
        $page = $this->profileComponent()->call('startEditing')->call('startChangingPassword');
        $html = $page->html();
        $this->assertSame(1, substr_count($html, 'wire:click="cancelEditing"'));
        $this->assertSame(1, substr_count($html, 'wire:click="cancelChangingPassword"'));
        $this->assertSame(1, substr_count($html, 'form="client-profile-form"'));
        $this->assertSame(1, substr_count($html, 'form="client-password-form"'));
    }
}
