<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('address');
            $table->string('contact')->unique();
            $table->string('email')->unique();
            $table->string('description');
            $table->string('logo')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->enum('status_verification', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('status_subscription', ['inactive', 'active', 'suspended'])->default('inactive');
            $table->dateTime('join_at');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
