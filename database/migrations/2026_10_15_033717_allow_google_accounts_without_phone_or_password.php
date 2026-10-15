<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone_number')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('users')->whereNull('phone_number')->orWhereNull('password')->exists()) {
            throw new RuntimeException('Rollback dibatalkan: lengkapi nomor telepon dan password akun Google terlebih dahulu.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone_number')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
