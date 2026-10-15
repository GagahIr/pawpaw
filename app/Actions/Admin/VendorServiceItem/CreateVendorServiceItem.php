<?php

namespace App\Actions\Admin\VendorServiceItem;

use App\Models\VendorService;
use App\Models\VendorServiceItem;
use Exception;
use Illuminate\Support\Facades\Log;

class CreateVendorServiceItem
{
    public function execute(int $categoryServiceId, array $data): VendorServiceItem
    {
        try {
            $vendorService = VendorService::where('category_service_id', $categoryServiceId)->firstOrFail();

            if (isset($data['capacity']) && $data['capacity'] === '') {
                $data['capacity'] = null;
            }

            return VendorServiceItem::create([
                'vendor_service_id' => $vendorService->id,
                ...$data,
            ]);

        } catch (Exception $e) {
            Log::error('Gagal membuat VendorServiceItem: '.$e->getMessage(), [
                'category_service_id' => $categoryServiceId,
                'data' => $data,
            ]);

            throw $e;
        }
    }
}
