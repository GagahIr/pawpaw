<?php

namespace Tests\Feature\Client\Profile;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    private function component()
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
            ->assertSee('Amanda Dealova Putri');
    }

    public function test_client_can_update_profile_information(): void
    {
        $this->component()
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
        $this->component()
            ->set('formData.phone_number', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->client->refresh()->phone_number);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->component()
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

        $this->component()
            ->set('formData.email', 'lain@example.test')
            ->set('formData.username', 'lainnya')
            ->set('formData.phone_number', '089999999999')
            ->call('save')
            ->assertHasErrors(['formData.email', 'formData.username', 'formData.phone_number']);
    }

    public function test_client_can_upload_avatar(): void
    {
        Storage::fake('public');

        $this->component()
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
        $this->component()
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
        $this->component()
            ->set('currentPassword', 'salah-total')
            ->set('newPassword', 'password-baru-123')
            ->set('newPasswordConfirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password-lama', $this->client->refresh()->password));
    }
}
