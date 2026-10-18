<?php

namespace Tests\Feature\Client\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientAuthenticationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_credentials_survive_migration_and_rollback(): void
    {
        $role = Role::create(['name' => 'client']);
        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(['role_id' => $role->id, 'phone_number' => '081234567890']);
        UserClient::create(['user_id' => $user->id, 'google_id' => 'legacy-google-id'])
            ->forceFill(['remember_token' => 'legacy-remember-token'])->save();
        $migration = require database_path('migrations/2026_10_18_071615_move_client_authentication_fields_to_user_clients.php');

        $migration->down();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'google_id' => 'legacy-google-id', 'remember_token' => 'legacy-remember-token']);
        $this->assertFalse(Schema::hasColumn('user_clients', 'google_id'));

        $migration->up();
        $this->assertDatabaseHas('user_clients', ['user_id' => $user->id, 'google_id' => 'legacy-google-id', 'remember_token' => 'legacy-remember-token']);
        $this->assertFalse(Schema::hasColumn('users', 'google_id'));
        $this->assertFalse(Schema::hasColumn('users', 'remember_token'));
        $this->assertSame('legacy-remember-token', $user->fresh()->getRememberToken());
        $this->assertArrayNotHasKey('remember_token', $user->fresh()->userClient->toArray());
    }

    public function test_non_client_credentials_stop_migration_without_losing_data(): void
    {
        $role = Role::create(['name' => 'owner']);
        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create(['role_id' => $role->id, 'phone_number' => '081234567890']);
        $migration = require database_path('migrations/2026_10_18_071615_move_client_authentication_fields_to_user_clients.php');
        $migration->down();
        DB::table('users')->where('id', $user->id)->update(['remember_token' => 'vendor-token']);

        try {
            $migration->up();
            $this->fail('Migrasi seharusnya menolak kredensial vendor.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('akun non-client', $exception->getMessage());
            $this->assertDatabaseHas('users', ['id' => $user->id, 'remember_token' => 'vendor-token']);
            $this->assertFalse(Schema::hasColumn('user_clients', 'remember_token'));
        }
    }
}
