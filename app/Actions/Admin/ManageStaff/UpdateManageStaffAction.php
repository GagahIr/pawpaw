<?php

namespace App\Actions\Admin\ManageStaff;

use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\VendorStaff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateManageStaffAction
{

    public function update(int $staffId, array $data): VendorStaff
    {
        try {
            return DB::transaction(function () use ($staffId, $data) {
                $vendorStaff = VendorStaff::with('user')->findOrFail($staffId);

                $userData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone_number' => $data['phone_number'],
                ];

                if (! empty($data['password'])) {
                    $userData['password'] = Hash::make($data['password']);
                }

                $vendorStaff->user->update($userData);

                if (isset($data['position']) && $data['position'] !== $vendorStaff->position->value) {
                    $roleId = $data['position'] === StaffPosition::Owner->value
                        ? Role::where('name', 'owner')->value('id')
                        : Role::where('name', 'front_office')->value('id');

                    $vendorStaff->user->update(['role_id' => $roleId]);
                    $vendorStaff->update(['position' => StaffPosition::from($data['position'])]);
                }

                return $vendorStaff->fresh();
            });
        } catch (\Exception $e) {
            throw new \Exception('Gagal mengupdate staff: '.$e->getMessage());
        }
    }
}