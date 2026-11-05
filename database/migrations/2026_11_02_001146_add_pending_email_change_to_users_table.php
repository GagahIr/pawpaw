<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('pending_email')->nullable();
            $table->string('email_change_original_email')->nullable();
            $table->string('email_change_token', 64)->nullable();
            $table->unsignedBigInteger('email_change_vendor_id')->nullable();
            $table->timestamp('email_change_expires_at')->nullable();
            $table->timestamp('email_change_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'pending_email', 'email_change_original_email', 'email_change_token',
                'email_change_vendor_id', 'email_change_expires_at', 'email_change_requested_at',
            ]);
        });
    }
};
