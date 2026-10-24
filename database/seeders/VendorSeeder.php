<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOperatingHour;
use App\Models\VendorService;
use App\Models\VendorServiceItem;
use App\Models\VendorStaff;
use App\Enums\DayOfWeek;
use App\Enums\StaffPosition;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Role Owner (pastikan RoleSeeder sudah jalan)
        $ownerRole = Role::where('name', 'owner')->first();

        // 2. Buat 10 Vendor beserta relasinya
        Vendor::factory(10)->create()->each(function ($vendor) use ($ownerRole) {

            // A. Buat User & Jadikan Staff (Owner) untuk vendor ini
            $user = User::factory()->create(['role_id' => $ownerRole->id]);
            VendorStaff::create([
                'user_id' => $user->id,
                'vendor_id' => $vendor->id,
                'position' => StaffPosition::cases()[0] ?? 'owner', // Sesuaikan Enum
            ]);

            // B. Buat Operating Hours (Misal buka Senin-Jumat)
            $days = DayOfWeek::cases();
            foreach (array_slice($days, 0, 5) as $day) {
                VendorOperatingHour::factory()->create([
                    'vendor_id' => $vendor->id,
                    'day_of_week' => $day,
                ]);
            }

            // C. Buat 2 Kategori Service (Grooming / Clinic / Pet Hotel)
            VendorService::factory(2)->create(['vendor_id' => $vendor->id])->each(function ($vendorService) {

                // D. Buat 3 Item Layanan untuk tiap kategori
                VendorServiceItem::factory(3)->create([
                    'vendor_service_id' => $vendorService->id
                ]);
            });
        });
    }
}
