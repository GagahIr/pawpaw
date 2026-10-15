<?php

namespace App\Actions\Admin\VendorServiceItem;

use App\Models\VendorServiceItem;
use Illuminate\Support\Facades\Log;

class UpdateVendorServiceItem
{
    public function execute(int $id, array $data): VendorServiceItem
    {
        try {
            $item = VendorServiceItem::findOrFail($id);

            if (isset($data['capacity']) && $data['capacity'] === '') {
                $data['capacity'] = null;
            }

            $item->update($data);

            return $item;
        } catch (\Exception $e) {
            Log::error('Failed to update vendor service item: '.$e->getMessage(), [
                'id' => $id,
                'data' => $data,
            ]);

            throw new \RuntimeException('Failed to update vendor service item: '.$e->getMessage());
        }
    }
}
