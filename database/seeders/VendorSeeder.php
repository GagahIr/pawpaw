<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Enums\StaffPosition;
use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOperatingHour;
use App\Models\VendorService;
use App\Models\VendorServiceItem;
use App\Models\VendorStaff;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil Role Owner (pastikan RoleSeeder sudah jalan)
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);

        $vendors = Vendor::factory(10)->create();
        $photos = config('clinic-demo-photos');
        $activeVendors = Vendor::factory(15)->sequence(
            fn ($sequence): array => ['logo' => $photos[$sequence->index % count($photos)]['image_url']],
        )->create([
            'status_verification' => VendorVerificationStatus::Approved,
            'status_subscription' => VendorSubscriptionStatus::Active,
            'subscription_started_at' => now(),
            'subscription_expired_at' => now()->addDays(30),
        ]);

        $vendors->concat($activeVendors)->each(function (Vendor $vendor) use ($ownerRole): void {

            // A. Buat User & Jadikan Staff (Owner) untuk vendor ini
            $user = User::factory()->create(['role_id' => $ownerRole->id]);
            VendorStaff::create([
                'user_id' => $user->id,
                'vendor_id' => $vendor->id,
                'position' => StaffPosition::Owner,
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
            $serviceFactory = VendorService::factory(2);

            if ($vendor->status_subscription === VendorSubscriptionStatus::Active) {
                $serviceFactory = $serviceFactory->state(['status_service' => true]);
            }

            $serviceFactory->create(['vendor_id' => $vendor->id])->each(function (VendorService $vendorService): void {

                // D. Buat 3 Item Layanan untuk tiap kategori
                VendorServiceItem::factory(3)->create([
                    'vendor_service_id' => $vendorService->id,
                ]);
            });
        });
    }
}
