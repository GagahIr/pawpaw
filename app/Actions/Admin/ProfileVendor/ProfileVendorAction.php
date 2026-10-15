<?php

namespace App\Actions\Admin\ProfileVendor;

use App\Models\Vendor;

class ProfileVendorAction
{

    public function execute(int $vendorId, array $data): Vendor
    {
        try {
            $vendor = Vendor::findOrFail($vendorId);

            $vendor->update($data);

            return $vendor;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update vendor profile: ' . $e->getMessage());
        }
    }
}
