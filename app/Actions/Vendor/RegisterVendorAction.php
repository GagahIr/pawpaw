<?php

namespace App\Actions\Vendor;

use App\DTOs\RegisterVendorData;
use App\Enums\StaffPosition;
use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\CategoryService;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorService;
use App\Models\VendorStaff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterVendorAction
{
    public function execute(RegisterVendorData $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            $vendor = Vendor::create([
                'name' => $data->vendorName,
                'address' => $data->address,
                'contact' => $data->contact,
                'email' => $data->emailVendor,
                'description' => $data->description,
                'latitude' => 'AAAA',
                'longitude' => 'AAAA',
                'status_verification' => VendorVerificationStatus::Pending,
                'status_subscription' => VendorSubscriptionStatus::Inactive,
                'join_at' => now(),
            ]);

            $owner = User::create([
                'name' => $data->ownerName,
                'email' => $data->email,
                'password' => $data->password,
                'phone_number' => $data->phoneNumber,
                'role_id' => Role::where('name', 'owner')->value('id'),
            ]);

            VendorStaff::create([
                'user_id' => $owner->id,
                'vendor_id' => $vendor->id,
                'position' => StaffPosition::Owner,
            ]);

            foreach (CategoryService::all() as $categoryService) {
                VendorService::create([
                    'vendor_id' => $vendor->id,
                    'category_service_id' => $categoryService->id,
                    'status_service' => false,
                ]);
            }

            Auth::login($owner);

            return $vendor;
        });
    }
}
