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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OwnerProfileTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

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

    private function profileComponent()
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
        $this->profileComponent()
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
        $this->profileComponent()
            ->call('startEditing')
            ->set('formData.name', 'Rani Pratiwi Putri')
            ->set('formData.username', 'Rani_Putri')
            ->set('formData.email', 'RANI.BARU@klinik.test')
            ->set('formData.phone_number', '081234567891')
            ->set('emailChangePassword', 'password-lama')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isEditing', false);

        $this->owner->refresh();

        $this->assertSame('Rani Pratiwi Putri', $this->owner->name);
        $this->assertSame('rani_putri', $this->owner->username);
        $this->assertSame('rani@klinik.test', $this->owner->email);
        $this->assertSame('rani.baru@klinik.test', $this->owner->pending_email);
        $this->assertSame('081234567891', $this->owner->phone_number);
    }

    public function test_username_is_optional(): void
    {
        $this->profileComponent()
            ->set('formData.username', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->owner->refresh()->username);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->profileComponent()
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

        $this->profileComponent()
            ->set('formData.email', 'fo@klinik.test')
            ->set('formData.username', 'frontoffice')
            ->set('formData.phone_number', '089999999999')
            ->call('save')
            ->assertHasErrors(['formData.email', 'formData.username', 'formData.phone_number']);
    }

    public function test_owner_can_keep_own_unique_values_and_mitra_email(): void
    {
        $this->vendor->update(['email' => 'rani@klinik.test']);

        $this->profileComponent()
            ->set('formData.name', 'Nama Baru Owner')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nama Baru Owner', $this->owner->refresh()->name);
    }

    public function test_email_cannot_match_another_mitra_email(): void
    {
        $other = Vendor::factory()->create(['email' => 'lain@mitra.test']);
        $this->assertNotSame($other->id, $this->vendor->id);

        $this->profileComponent()
            ->set('formData.email', 'lain@mitra.test')
            ->call('save')
            ->assertHasErrors(['formData.email']);
    }

    public function test_cancel_editing_restores_original_values(): void
    {
        $this->profileComponent()
            ->call('startEditing')
            ->set('formData.name', 'Nama Sementara')
            ->call('cancelEditing')
            ->assertSet('formData.name', 'Drh. Rani Pratiwi')
            ->assertSet('isEditing', false);
    }

    public function test_owner_can_upload_avatar(): void
    {
        Storage::fake('public');

        $this->profileComponent()
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

        $this->profileComponent()
            ->set('avatar', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->assertHasErrors(['avatar']);
    }

    public function test_owner_can_change_password(): void
    {
        $this->profileComponent()
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
        $this->profileComponent()
            ->set('currentPassword', 'salah-total')
            ->set('newPassword', 'password-baru-123')
            ->set('newPasswordConfirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $this->assertTrue(Hash::check('password-lama', $this->owner->refresh()->password));
    }

    public function test_password_change_requires_matching_confirmation_and_minimum_length(): void
    {
        $this->profileComponent()
            ->set('currentPassword', 'password-lama')
            ->set('newPassword', 'pendek')
            ->set('newPasswordConfirmation', 'beda')
            ->call('updatePassword')
            ->assertHasErrors(['newPassword', 'newPasswordConfirmation']);
    }

    public function test_owner_profile_has_one_action_pair_in_each_section_header(): void
    {
        $page = $this->profileComponent()->call('startEditing')->call('startChangingPassword');
        $html = $page->html();
        $this->assertSame(1, substr_count($html, 'wire:click="cancelEditing"'));
        $this->assertSame(1, substr_count($html, 'wire:click="cancelChangingPassword"'));
        $this->assertStringContainsString('form="owner-profile-form"', $html);
        $this->assertStringContainsString('form="owner-password-form"', $html);
    }

    public function test_owner_password_remains_available_for_resending_email(): void
    {
        $page = $this->profileComponent()->call('startEditing')->set('formData.email', 'baru@owner.test')
            ->set('emailChangePassword', 'password-lama')->call('save')->assertHasNoErrors()
            ->assertSet('emailChangePassword', 'password-lama');
        $this->travel(61)->seconds();
        $page->call('resendEmailChange')->assertHasNoErrors()->assertSet('emailChangePassword', 'password-lama');
        $page->call('cancelEmailChange')->assertHasNoErrors()->assertSet('emailChangePassword', '');
    }

    public function test_owner_pending_email_notice_uses_full_width_and_restores_countdown(): void
    {
        $this->freezeTime();
        $this->profileComponent()->set('formData.email', 'baru@owner.test')
            ->set('emailChangePassword', 'password-lama')->call('save')->assertHasNoErrors()
            ->assertSee('data-email-change-countdown="60"', false);
        $this->travel(30)->seconds();
        $this->owner->refresh();
        $this->profileComponent()->assertSee('data-email-change-countdown="30"', false);
        $html = $this->profileComponent()->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//div[@data-email-change-countdown]/ancestor::div[contains(@class, "sm:grid-cols-2")]')->length);
    }

    public static function invalidPhones(): array
    {
        return [
            'empty' => [''],
            'letters' => ['abcdefgh'],
            'short' => ['1234567'],
            'long' => ['1234567890123456'],
            'prefix' => ['+62812345678'],
            'spaces' => ['0812 3456 7891'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_owner_phone_requires_eight_to_fifteen_digits(string $phone): void
    {
        $this->profileComponent()->set('formData.phone_number', $phone)->call('save')
            ->assertHasErrors(['formData.phone_number']);
        $this->assertSame('081234567890', $this->owner->fresh()->phone_number);
    }

    public function test_owner_phone_accepts_eight_and_fifteen_digits(): void
    {
        foreach (['12345678', '123456789012345'] as $phone) {
            $this->profileComponent()->set('formData.phone_number', $phone)->call('save')->assertHasNoErrors();
            $this->assertSame($phone, $this->owner->fresh()->phone_number);
        }
    }

    public function test_owner_password_navigation_preserves_draft_and_exposes_accessible_fields(): void
    {
        $this->profileComponent()->set('formData.email', 'baru@owner.test')
            ->call('startChangingPassword')->assertSet('isChangingPassword', true)
            ->assertSet('formData.email', 'baru@owner.test')
            ->assertDispatched('owner-password-form-opened')
            ->assertSee('for="owner-current-password"', false)
            ->assertSee('id="owner-current-password"', false)
            ->assertSee('id="owner-new-password"', false)
            ->assertSee('id="owner-password-confirmation"', false);
    }
}
