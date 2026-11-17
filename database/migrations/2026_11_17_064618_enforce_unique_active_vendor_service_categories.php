<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $duplicates = DB::table('vendor_services')
                ->whereNull('deleted_at')
                ->select('vendor_id', 'category_service_id')
                ->groupBy('vendor_id', 'category_service_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $duplicate) {
                $services = DB::table('vendor_services')
                    ->where('vendor_id', $duplicate->vendor_id)
                    ->where('category_service_id', $duplicate->category_service_id)
                    ->whereNull('deleted_at')
                    ->orderByDesc('status_service')
                    ->orderBy('id')
                    ->get();
                $retainedService = $services->first();
                $duplicateIds = $services->skip(1)->pluck('id');

                DB::table('vendor_service_items')
                    ->whereIn('vendor_service_id', $duplicateIds)
                    ->update(['vendor_service_id' => $retainedService->id]);

                DB::table('vendor_services')->whereIn('id', $duplicateIds)
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);
            }

            DB::statement('CREATE UNIQUE INDEX vendor_services_active_category_unique ON vendor_services (vendor_id, category_service_id) WHERE deleted_at IS NULL');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX vendor_services_active_category_unique');
    }
};
