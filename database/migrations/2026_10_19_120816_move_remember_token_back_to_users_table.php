<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::table('users', function (Blueprint $table): void {
        //     $table->rememberToken();
        // });

        // DB::table('user_clients')->whereNotNull('remember_token')
        //     ->orderBy('id')->chunkById(100, function (Collection $clients): void {
        //         foreach ($clients as $client) {
        //             DB::table('users')->where('id', $client->user_id)->update([
        //                 'remember_token' => $client->remember_token,
        //             ]);
        //         }
        //     });

        // Schema::table('user_clients', function (Blueprint $table): void {
        //     $table->dropColumn('remember_token');
        // });
    }

    public function down(): void
    {
        // Schema::table('user_clients', function (Blueprint $table): void {
        //     $table->rememberToken();
        // });

        // DB::table('users')->whereNotNull('remember_token')
        //     ->orderBy('id')->chunkById(100, function (Collection $users): void {
        //         foreach ($users as $user) {
        //             DB::table('user_clients')->where('user_id', $user->id)->update([
        //                 'remember_token' => $user->remember_token,
        //             ]);
        //         }
        //     });

        // Schema::table('users', function (Blueprint $table): void {
        //     $table->dropColumn('remember_token');
        // });
    }
};
