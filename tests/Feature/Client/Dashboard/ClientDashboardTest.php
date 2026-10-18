<?php

namespace Tests\Feature\Client\Dashboard;

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'app.domain' => 'localhost']);
    }

    private function user(string $roleName = 'client', array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(array_replace([
            'name' => 'Amanda Dealova Putri',
            'phone_number' => '081234567890',
            'role_id' => $role->id,
        ], $attributes));
    }

    public function test_guest_is_redirected_to_client_login(): void
    {
        $this->get(route('client.dashboard'))->assertRedirect(route('client.login'));
    }

    public function test_client_dashboard_displays_full_name_and_logout_form(): void
    {
        $user = $this->user();

        $this->actingAs($user)->withoutVite()->get(route('client.dashboard'))->assertOk()
            ->assertSee('Halo, Amanda Dealova Putri!')
            ->assertSee('Dashboard Pelanggan')
            ->assertSee('Logout')
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('logout').'"', false);
    }

    public function test_client_name_is_escaped_on_dashboard(): void
    {
        $name = '<script>alert("name")</script>';

        $this->actingAs($this->user(attributes: ['name' => $name]))->withoutVite()
            ->get(route('client.dashboard'))->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    public static function nonClientRoles(): array
    {
        return ['owner' => ['owner'], 'front office' => ['front_office']];
    }

    #[DataProvider('nonClientRoles')]
    public function test_vendor_accounts_cannot_access_client_dashboard(string $role): void
    {
        $this->actingAs($this->user($role))->get(route('client.dashboard'))->assertForbidden();
    }

    public function test_deleted_client_cannot_access_dashboard(): void
    {
        $this->actingAs($this->user(attributes: ['deleted_at' => now()]))
            ->get(route('client.dashboard'))->assertForbidden();
    }

    public static function guestPages(): array
    {
        return [
            'login' => ['/client-login'],
            'registration' => ['/client-register'],
            'forgot password' => ['/client-forgot-password'],
            'reset password' => ['/client-reset-password/example-token'],
            'google redirect' => ['/auth/google'],
            'google callback' => ['/auth/google/callback'],
        ];
    }

    #[DataProvider('guestPages')]
    public function test_authenticated_client_is_redirected_to_dashboard_from_guest_pages(string $path): void
    {
        $this->actingAs($this->user())->get($path)->assertRedirect(route('client.dashboard'));
    }

    public function test_logout_clears_session_and_dashboard_requires_login_again(): void
    {
        $this->actingAs($this->user())->withSession(['dashboard_marker' => 'old session'])
            ->post(route('logout'))->assertRedirect('/')->assertSessionMissing('dashboard_marker');

        $this->assertGuest();
        $this->get(route('client.dashboard'))->assertRedirect(route('client.login'));
    }

    public function test_vendor_login_redirects_still_use_vendor_subdomain(): void
    {
        $vendor = Vendor::create([
            'name' => 'Paws Clinic', 'slug' => 'paws', 'address' => 'Jakarta',
            'email' => 'paws@example.com', 'contact' => '089876543210',
            'description' => 'Pet clinic', 'join_at' => now(),
        ]);

        $this->get('http://paws.localhost/admin')
            ->assertRedirect(route('vendor.login', ['subdomain' => 'paws']));

        $owner = $this->user('owner');
        VendorStaff::create(['user_id' => $owner->id, 'vendor_id' => $vendor->id, 'position' => 'owner']);

        $this->actingAs($owner)->get(route('client.login'))
            ->assertRedirect(route('vendor.admin.dashboard', ['subdomain' => 'paws']));
        Route::getRoutes()->getByName('vendor.login')->uses(fn (): string => 'Vendor login');
        $this->get('http://paws.localhost/login')
            ->assertRedirect(route('vendor.admin.dashboard', ['subdomain' => 'paws']));
    }
}
