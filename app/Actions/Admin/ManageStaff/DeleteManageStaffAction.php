<?php

namespace App\Actions\Admin\ManageStaff;

use App\Models\VendorStaff;
use Illuminate\Support\Facades\DB;

class DeleteManageStaffAction
{

    public function delete(int $staffId): bool
    {
        try {
            return DB::transaction(function () use ($staffId) {
                $vendorStaff = VendorStaff::with('user')->findOrFail($staffId);

                $user = $vendorStaff->user;

                $vendorStaff->delete();

                if ($user) {
                    $user->delete();
                }

                return true;
            });
        } catch (\Exception $e) {
            throw new \Exception('Gagal menghapus staff: ' . $e->getMessage());
        }
    }
}