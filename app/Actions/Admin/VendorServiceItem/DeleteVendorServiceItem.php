<?php

namespace App\Actions\Admin\VendorServiceItem;

use App\Models\VendorServiceItem;
use Illuminate\Support\Facades\Log;

class DeleteVendorServiceItem
{
    public function execute(int $id): bool
    {

        try {
            $item = VendorServiceItem::findOrFail($id);

            return $item->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete vendor service item: '.$e->getMessage(), ['id' => $id]);

            return false;
        }
    }
}
