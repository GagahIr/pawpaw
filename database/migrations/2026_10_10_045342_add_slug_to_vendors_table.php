<?php

use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Populate slugs for existing vendors
        foreach (Vendor::all() as $vendor) {
            $baseSlug = Str::slug($vendor->name ?: 'klinik-'.$vendor->id);
            $slug = $baseSlug;
            $counter = 1;
            while (Vendor::where('slug', $slug)->where('id', '!=', $vendor->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $vendor->slug = $slug;
            $vendor->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
