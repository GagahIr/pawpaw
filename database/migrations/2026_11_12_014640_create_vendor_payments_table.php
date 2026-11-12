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
        if (! Schema::hasTable('vendor_payments')) {
            Schema::create('vendor_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->string('midtrans_order_id')->unique();
                $table->string('midtrans_transaction_id')->nullable()->unique();
                $table->unsignedBigInteger('amount');
                $table->string('currency', 3)->default('IDR');
                $table->string('status')->default('pending');
                $table->dateTime('paid_at')->nullable();
                $table->json('payment_details')->nullable();
                $table->timestamps();

                $table->index(['vendor_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
    }
};
