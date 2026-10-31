<?php

namespace App\Actions\Admin\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UpdateVendorManagementAction
{
    /**
     * @param  array<string, mixed>  $vendorData
     * @param  array<int, array<string, mixed>>  $staffData
     * @param  array<int, array<string, mixed>>  $services
     * @param  array<int, array<string, mixed>>  $hours
     */
    public function execute(int $vendorId, array $vendorData, array $staffData, array $services, array $hours): Vendor
    {
        return DB::transaction(function () use ($vendorId, $vendorData, $staffData, $services, $hours): Vendor {
            $vendor = Vendor::query()->with(['vendorStaff.user', 'vendorServices.vendorServiceItems', 'vendorOperatingHours'])->findOrFail($vendorId);
            $vendor->fill($vendorData);

            if ($vendor->isDirty('name')) {
                $baseSlug = Str::slug($vendor->name);
                $slug = $baseSlug;
                $counter = 1;
                while (Vendor::query()->where('slug', $slug)->where('id', '!=', $vendor->id)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }
                $vendor->slug = $slug;
            }

            $vendor->save();

            foreach ($staffData as $staffMember) {
                $user = $vendor->vendorStaff->firstWhere('id', (int) $staffMember['id'])?->user;
                if (! $user instanceof User) {
                    continue;
                }

                $user->fill([
                    'name' => $staffMember['name'],
                    'email' => $staffMember['email'],
                    'phone_number' => $staffMember['phone_number'],
                ]);
                if (! empty($staffMember['password'])) {
                    $user->password = Hash::make($staffMember['password']);
                }
                $user->save();
            }

            foreach ($services as $serviceData) {
                $service = $vendor->vendorServices->firstWhere('id', (int) $serviceData['id']);
                if (! $service) {
                    continue;
                }

                $service->update(['status_service' => (bool) $serviceData['status_service']]);
                foreach ($serviceData['items'] ?? [] as $itemData) {
                    $item = $service->vendorServiceItems->firstWhere('id', (int) ($itemData['id'] ?? 0));
                    if ($item) {
                        $item->update([
                            'name' => $itemData['name'],
                            'price' => $itemData['price'],
                            'duration' => $itemData['duration'],
                            'capacity' => $itemData['capacity'] ?: null,
                        ]);
                    }
                }
            }

            foreach ($hours as $hourData) {
                $vendor->vendorOperatingHours()->updateOrCreate(
                    ['day_of_week' => $hourData['day_of_week']],
                    [
                        'open_time' => $hourData['open_time'],
                        'close_time' => $hourData['close_time'],
                        'is_closed' => (bool) $hourData['is_closed'],
                    ],
                );
            }

            return $vendor->fresh(['vendorStaff.user', 'vendorServices.categoryService', 'vendorServices.vendorServiceItems', 'vendorOperatingHours']);
        });
    }
}
