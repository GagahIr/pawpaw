<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_clients', function (Blueprint $table): void {
            $table->string('google_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('user_clients', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};
