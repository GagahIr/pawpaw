<?php

namespace Tests\Feature\Vendor\OwnerProfile;

use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerProfileTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'app.domain' => 'localhost']);

        $this->vendor = Vendor::factory()->create();
        app('vendor.context')->set($this->vendor);
        URL::defaults(['subdomain' => $this->vendor->slug]);

        $this->owner = $this->staff('owner', StaffPosition::Owner, [
            'name' => 'Drh. Rani Pratiwi',
            'email' => 'rani@klinik.test',
            'username' => 'rani',
            'phone_number' => '081234567890',
            'password' => 'password-lama',
        ]);
    }

    private function staff(string $roleName, StaffPosition $position, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(array_replace(['role_id' => $role->id], $attributes));

        VendorStaff::create([
            'user_id' => $user->id,
            'vendor_id' => $this->vendor->id,
            'position' => $position,
        ]);

        return $user;
    }

    protected function livewireComponent()
    {
        return Livewire::actingAs($this->owner, 'vendor')->test('vendor.owner_profile.owner-profile');
    }

    private function profileUrl(): string
    {
        return 'http://'.$this->vendor->slug.'.localhost/admin/my-profile';
    }

    public function test_guest_cannot_open_owner_profile(): void
    {
        $this->get($this->profileUrl())->assertRedirect();
    }

    public function test_owner_sees_profile_page_and_menu_link_in_admin_layout(): void
    {
        $this->actingAs($this->owner, 'vendor')
            ->withoutVite()
            ->get($this->profileUrl())
            ->assertOk()
            ->assertSee('Profil Owner')
            ->assertSee('Profil Saya')
            ->assertSee('Drh. Rani Pratiwi');
    }

    public function test_form_is_prefilled_with_owner_data(): void
    {
        $this->livewireComponent()
            ->assertSet('formData.name', 'Drh. Rani Pratiwi')
            ->assertSet('formData.username', 'rani')
            ->assertSet('formData.email', 'rani@klinik.test')
            ->assertSet('formData.phone_number', '081234567890')
            ->assertSet('isEditing', false)
            ->assertSee('Profil Owner')
            ->assertSee($this->vendor->name);
    }

    public function test_owner_can_update_profile_information(): void
    {
        $this->livewireComponent()
            ->call('startEditing')
            ->set('formData.name', 'Rani Pratiwi Putri')
            ->set('formData.username', 'Rani_Putri')
            ->set('formData.email', 'RANI.BARU@klinik.test')
            ->set('formData.phone_number', '0812 3456 7891')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isEditing', false);

        $this->owner->refresh();

        $this->assertSame('Rani Pratiwi Putri', $this->owner->name);
        $this->assertSame('rani_putri', $this->owner->username);
        $this->assertSame('rani.baru@klinik.test', $this->owner->email);
        $this->assertSame('081234567891', $this->owner->phone_number);
    }

    public function test_username_is_optional(): void
    {
        $this->livewireComponent()
            ->set('formData.username', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->owner->refresh()->username);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->livewireComponent()
            ->set('formData.name', '')
            ->set('formData.email', 'bukan-email')
            ->set('formData.phone_number', '123')
            ->call('save')
            ->assertHasErrors(['formData.name', 'formData.email', 'formData.phone_number']);

        $this->assertSame('Drh. Rani Pratiwi', $this->owner->refresh()->name);
    }

    public function test_email_username_and_phone_must_be_unique_against_other_users(): void
    {
        $this->staff('front_office', StaffPosition::FrontOffice, [
            'email' => 'fo@klinik.test',
            'username' => 'frontoffice',
            'phone_number' => '089999999999',
        ]);

        $this->livewireComponent()
            ->set('formData.email', 'fo@klinik.test')
            ->set('formData.username', 'frontoffice')
            ->set('formData.phone_number', '089999999999')
            ->call('save')
            ->assertHasErrors(['formData.email', 'formData.username', 'formData.phone_number']);
    }

    public function test_owner_can_keep_own_unique_values_and_mitra_email(): void
    {
        $this->vendor->update(['email' => 'rani@klinik.test']);

        $this->livewireComponent()
            ->set('formData.name', 'Nama Baru Owner')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nama Baru Owner', $this->owner->refresh()->name);
    }

    public function test_email_cannot_match_another_mitra_email(): void
    {
        $other = Vendor::factory()->create(['email' => 'lain@mitra.test']);
        $this->assertNotSame($other->id, $this->vendor->id);

        $this->livewireComponent()
            ->set('formData.email', 'lain@mitra.test')
            ->call('save')
            ->assertHasErrors(['formData.email']);
    }

    public function test_cancel_editing_restores_original_values(): void
    {
        $this->livewireComponent()
            ->call('startEditing')
            ->set('formData.name', 'Nama Sementara')
            ->call('cancelEditing')
            ->assertSet('formData.name', 'Drh. Rani Pratiwi')
            ->assertSet('isEditing', false);
    }

    public function test_owner_can_upload_avatar(): void
    {
        Storage::fake('public');

        $this->livewireComponent()
            ->set('avatar', UploadedFile::fake()->image('foto.png', 200, 200))
            ->assertHasNoErrors()
            ->call('save')
            ->assertHasNoErrors();

        $path = $this->owner->refresh()->avatar;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_avatar_must_be_an_image(): void
    {
        Storage::fake('public');

        $this->livewireComponent()
            ->set('avatar', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->assertHasErrors(['avatar'])
            ->assertSet('avatar', null);
    }

    public function test_avatar_cannot_exceed_max_file_size(): void
    {
        Storage::fake('public');

        $this->livewireComponent()
            ->set('avatar', UploadedFile::fake()->image('foto-besar.png')->size(2049))
            ->assertHasErrors(['avatar'])
            ->assertSet('avatar', null);
    }

    public function test_avatar_validation_error_message_is_displayed_for_invalid_mime(): void
    {
        Storage::fake('public');

        $this->livewireComponent()
            ->call('startEditing')
            ->set('avatar', UploadedFile::fake()->create('dokumen.txt', 50, 'text/plain'))
            ->assertHasErrors(['avatar' => 'mimes'])
            ->assertSee('Format gambar harus JPEG, PNG, atau WebP')
            ->assertDontSee('Foto siap disimpan');
    }

    public function test_avatar_validation_error_message_is_displayed_for_oversized_file(): void
    {
        Storage::fake('public');

        $this->livewireComponent()
            ->call('startEditing')
            ->set('avatar', UploadedFile::fake()->image('gambar-berat.jpg')->size(3000))
            ->assertHasErrors(['avatar' => 'max'])
            ->assertSee('Ukuran gambar maksimal 2 MB')
            ->assertDontSee('Foto siap disimpan');
    }

    public function test_owner_sees_delete_photo_button_when_avatar_exists(): void
    {
        $this->owner->update(['avatar' => 'avatars/existing-photo.webp']);

        $this->livewireComponent()
            ->assertDontSee('Ubah foto')
            ->assertDontSee('Hapus foto')
            ->call('startEditing')
            ->assertSee('Ubah foto')
            ->assertSee('Hapus foto');
    }

    public function test_avatar_action_buttons_are_hidden_in_view_mode_and_appear_in_edit_mode(): void
    {
        $this->livewireComponent()
            ->assertDontSee('Ubah foto')
            ->assertDontSee('JPG, PNG, WebP (Maks. 2 MB)')
            ->call('startEditing')
            ->assertSee('Ubah foto')
            ->assertSee('JPG, PNG, WebP (Maks. 2 MB)')
            ->call('cancelEditing')
            ->assertDontSee('Ubah foto');
    }

    public function test_owner_can_delete_existing_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/existing-photo.webp', 'fake-image-content');

        $this->owner->update(['avatar' => 'avatars/existing-photo.webp']);
        Storage::disk('public')->assertExists('avatars/existing-photo.webp');

        $this->livewireComponent()
            ->call('deleteAvatar')
            ->assertHasNoErrors()
            ->assertSet('avatar', null);

        $this->assertNull($this->owner->refresh()->avatar);
        Storage::disk('public')->assertMissing('avatars/existing-photo.webp');
    }

    public function test_owner_can_cancel_staged_avatar_before_saving(): void
    {
        Storage::fake('public');

        $this->livewireComponent()
            ->set('avatar', UploadedFile::fake()->image('staged.png', 100, 100))
            ->assertSet('avatar', fn ($avatar) => $avatar !== null)
            ->call('confirmDeleteAvatar')
            ->assertSet('avatar', null);
    }

    public function test_owner_can_change_password(): void
    {
        $this->livewireComponent()
            ->call('startChangingPassword')
            ->set('currentPassword', 'password-lama')
            ->set('newPassword', 'password-baru-123')
            ->set('newPasswordConfirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('isChangingPassword', false);

        $this->assertTrue(Hash::check('password-baru-123', $this->owner->refresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $this->livewireComponent()
            ->set('currentPassword', 'salah-total')
            ->set('newPassword', 'password-baru-123')
            ->set('newPasswordConfirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password-lama', $this->owner->refresh()->password));
    }

    public function test_password_change_requires_matching_confirmation_and_minimum_length(): void
    {
        $this->livewireComponent()
            ->set('currentPassword', 'password-lama')
            ->set('newPassword', 'pendek')
            ->set('newPasswordConfirmation', 'beda')
            ->call('updatePassword')
            ->assertHasErrors(['newPassword', 'newPasswordConfirmation']);
    }
}
