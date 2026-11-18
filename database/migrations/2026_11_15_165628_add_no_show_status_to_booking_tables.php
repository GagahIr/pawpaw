<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLES = ['bookings_clinic', 'bookings_grooming', 'bookings_pet_hotel'];

    private const OLD_STATUSES = ['pending', 'confirmed', 'completed', 'cancelled', 'rejected'];

    /**
     * CHECK constraint ini hanya ada di PostgreSQL. Di SQLite (database test) kolom status
     * berupa string biasa sehingga status no_show sudah bisa dipakai tanpa constraint.
     */
    public function up(): void
    {
        $this->replaceStatusConstraint([...self::OLD_STATUSES, 'no_show']);
    }

    public function down(): void
    {
        $this->replaceStatusConstraint(self::OLD_STATUSES);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function replaceStatusConstraint(array $statuses): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $allowed = implode(', ', array_map(fn (string $status): string => "'{$status}'::text", $statuses));

        foreach (self::TABLES as $table) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_status_check");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_status_check CHECK (status::text = ANY (ARRAY[{$allowed}]))");
        }
    }
};