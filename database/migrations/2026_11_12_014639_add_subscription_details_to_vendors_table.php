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
        $columns = [
            'subscription_started_at' => fn (Blueprint $table) => $table->dateTime('subscription_started_at')->nullable(),
            'subscription_expired_at' => fn (Blueprint $table) => $table->dateTime('subscription_expired_at')->nullable(),
            'current_plan' => fn (Blueprint $table) => $table->string('current_plan')->nullable(),
            'last_payment_date' => fn (Blueprint $table) => $table->dateTime('last_payment_date')->nullable(),
        ];

        foreach ($columns as $column => $addColumn) {
            if (! Schema::hasColumn('vendors', $column)) {
                Schema::table('vendors', $addColumn);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['subscription_started_at', 'subscription_expired_at', 'current_plan', 'last_payment_date'] as $column) {
            if (Schema::hasColumn('vendors', $column)) {
                Schema::table('vendors', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
