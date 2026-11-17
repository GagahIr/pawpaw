<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // bookings_clinic - remove old check constraint and add new one
        DB::statement('ALTER TABLE bookings_clinic DROP CONSTRAINT IF EXISTS bookings_clinic_status_check');
        DB::statement("ALTER TABLE bookings_clinic ADD CONSTRAINT bookings_clinic_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text, 'no_show'::text]))");

        // bookings_grooming
        DB::statement('ALTER TABLE bookings_grooming DROP CONSTRAINT IF EXISTS bookings_grooming_status_check');
        DB::statement("ALTER TABLE bookings_grooming ADD CONSTRAINT bookings_grooming_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text, 'no_show'::text]))");

        // bookings_pet_hotel
        DB::statement('ALTER TABLE bookings_pet_hotel DROP CONSTRAINT IF EXISTS bookings_pet_hotel_status_check');
        DB::statement("ALTER TABLE bookings_pet_hotel ADD CONSTRAINT bookings_pet_hotel_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text, 'no_show'::text]))");
    }

    public function down(): void
    {
        // bookings_clinic - revert to old check constraint
        DB::statement('ALTER TABLE bookings_clinic DROP CONSTRAINT IF EXISTS bookings_clinic_status_check');
        DB::statement("ALTER TABLE bookings_clinic ADD CONSTRAINT bookings_clinic_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text]))");

        // bookings_grooming
        DB::statement('ALTER TABLE bookings_grooming DROP CONSTRAINT IF EXISTS bookings_grooming_status_check');
        DB::statement("ALTER TABLE bookings_grooming ADD CONSTRAINT bookings_grooming_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text]))");

        // bookings_pet_hotel
        DB::statement('ALTER TABLE bookings_pet_hotel DROP CONSTRAINT IF EXISTS bookings_pet_hotel_status_check');
        DB::statement("ALTER TABLE bookings_pet_hotel ADD CONSTRAINT bookings_pet_hotel_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'confirmed'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text]))");
    }
};
