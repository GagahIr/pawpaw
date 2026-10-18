<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $nonClientCredentials = DB::table('users')->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', '!=', 'client')
            ->where(fn (Builder $query): Builder => $query->whereNotNull('google_id')->orWhereNotNull('remember_token'))
            ->exists();

        if ($nonClientCredentials) {
            throw new RuntimeException('Migrasi dibatalkan: akun non-client masih memiliki Google ID atau remember token. Data harus ditinjau sebelum pemindahan.');
        }

        Schema::table('user_clients', function (Blueprint $table): void {
            $table->string('google_id')->nullable()->unique();
            $table->rememberToken();
        });

        DB::table('users')->whereIn('role_id', DB::table('roles')->select('id')->where('name', 'client'))
            ->orderBy('id')->chunkById(100, function (Collection $users): void {
                foreach ($users as $user) {
                    $profile = DB::table('user_clients')->where('user_id', $user->id)->first();

                    if ($profile === null) {
                        DB::table('user_clients')->insert([
                            'user_id' => $user->id, 'count_fake_booking' => 0,
                            'google_id' => $user->google_id, 'remember_token' => $user->remember_token,
                            'created_at' => $user->created_at, 'updated_at' => $user->updated_at,
                        ]);
                    } else {
                        DB::table('user_clients')->where('id', $profile->id)->update([
                            'google_id' => $user->google_id, 'remember_token' => $user->remember_token,
                        ]);
                    }
                }
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'remember_token']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->rememberToken();
            $table->string('google_id')->nullable()->unique();
        });

        DB::table('user_clients')->orderBy('id')->chunkById(100, function (Collection $clients): void {
            foreach ($clients as $client) {
                DB::table('users')->where('id', $client->user_id)->update([
                    'google_id' => $client->google_id, 'remember_token' => $client->remember_token,
                ]);
            }
        });

        Schema::table('user_clients', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'remember_token']);
        });
    }
};
