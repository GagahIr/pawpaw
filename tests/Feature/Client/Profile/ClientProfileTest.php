<?php

namespace Tests\Feature\Client\Profile;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function profileComponent(): Testable
    {
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
            ->assertSee('Amanda Dealova Putri')
            ->assertSee('clinic-shell', false)
            ->assertSee('profile-page', false)
            ->assertSee('profile-data', false)
            ->assertSee('Bergabung sejak');
    }

    public function test_client_can_update_profile_information(): void
    {
        $this->profileComponent()
            ->call('startEditing')
            ->set('formData.name', 'Amanda Putri')
            ->set('formData.username', 'Amanda_P')
            ->set('formData.email', 'AMANDA.BARU@example.test')
            ->set('formData.phone_number', '0812 3456 7891')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isEditing', false);

        $this->client->refresh();

        $this->assertSame('Amanda Putri', $this->client->name);
        $this->assertSame('amanda_p', $this->client->username);
        $this->assertSame('amanda.baru@example.test', $this->client->email);
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

    public function test_cancelling_profile_edits_restores_saved_information(): void
    {
        $this->profileComponent()
            ->call('startEditing')
            ->assertSee('for="profile-name"', false)
            ->assertSee('autocomplete="tel"', false)
            ->set('formData.name', 'Nama belum disimpan')
            ->set('formData.phone_number', '081111111111')
            ->call('cancelEditing')
            ->assertSet('isEditing', false)
            ->assertSet('formData.name', 'Amanda Dealova Putri')
            ->assertSet('formData.phone_number', '081234567890')
            ->assertDontSee('id="client-profile-form"', false);

        $this->assertSame('Amanda Dealova Putri', $this->client->refresh()->name);
        $this->assertSame('081234567890', $this->client->phone_number);
    }

    public function test_formatted_duplicate_phone_is_rejected_without_saving(): void
    {
        $this->makeClient(['phone_number' => '089999999999']);

        $this->profileComponent()
            ->call('startEditing')
            ->set('formData.name', 'Nama belum disimpan')
            ->set('formData.phone_number', '(0899) 9999-9999')
            ->call('save')
            ->assertHasErrors(['formData.phone_number' => 'unique'])
            ->assertSet('isEditing', true);

        $this->assertSame('Amanda Dealova Putri', $this->client->refresh()->name);
        $this->assertSame('081234567890', $this->client->phone_number);
    }

    public function test_cancelling_password_edit_clears_password_fields(): void
    {
        $this->profileComponent()
            ->call('startChangingPassword')
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('aria-controls="profile-newPassword"', false)
            ->set('currentPassword', 'password-lama')
            ->set('newPassword', 'password-baru-123')
            ->set('newPasswordConfirmation', 'password-baru-123')
            ->call('cancelChangingPassword')
            ->assertSet('isChangingPassword', false)
            ->assertSet('currentPassword', '')
            ->assertSet('newPassword', '')
            ->assertSet('newPasswordConfirmation', '');

        $this->assertTrue(Hash::check('password-lama', $this->client->refresh()->password));
    }

    public function test_invalid_avatar_shows_validation_without_rendering_a_preview(): void
    {
        Storage::fake('public');

        $this->profileComponent()
            ->set('avatar', UploadedFile::fake()->create('foto.pdf', 10, 'application/pdf'))
            ->assertHasErrors(['avatar'])
            ->assertDontSee('Pratinjau foto profil baru')
            ->assertDontSee('Foto siap.');

        $this->assertNull($this->client->refresh()->avatar);
    }
}
