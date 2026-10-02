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
        Schema::create('bookings_grooming', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_client_id')->constrained();
            $table->foreignId('vendor_service_item_id')->constrained();
            $table->dateTime('booking_date');
            $table->integer('time_slot');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings_grooming');
    }
};
