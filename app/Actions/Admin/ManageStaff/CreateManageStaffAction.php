<?php

namespace App\Actions\Admin\ManageStaff;

use App\DTOs\ManageStaffData;
use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\VendorStaff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateManageStaffAction
{
    public function create(int $vendorId, ManageStaffData $data): VendorStaff
    {
        try {
            return DB::transaction(function () use ($vendorId, $data) {
                $roleId = $data->position === StaffPosition::Owner->value
                    ? Role::where('name', 'owner')->value('id')
                    : Role::where('name', 'front_office')->value('id');

                $user = User::create([
                    'name' => $data->name,
                    'email' => $data->email,
                    'phone_number' => $data->phone_number,
                    'password' => Hash::make($data->password),
                    'role_id' => $roleId,
                ]);

                return VendorStaff::create([
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'position' => StaffPosition::from($data->position),
                ]);
            });
        } catch (\Exception $e) {
            throw new \Exception('Gagal membuat staff: '.$e->getMessage());
        }
    }
}
