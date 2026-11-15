<?php

namespace App\Actions\Admin\ProfileVendor;

use App\Models\Vendor;
use Illuminate\Support\Str;

class ProfileVendorAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(int $vendorId, array $data): Vendor
    {
        try {
            $vendor = Vendor::findOrFail($vendorId);

            if (isset($data['name']) && filled($data['name'])) {
                $trimmedName = trim((string) $data['name']);
                $data['name'] = $trimmedName;

                if ($vendor->name !== $trimmedName || empty($vendor->slug)) {
                    $baseSlug = Str::slug($trimmedName) ?: 'mitra';
                    $slug = $baseSlug;
                    $counter = 1;

                    while (Vendor::query()->where('slug', $slug)->where('id', '!=', $vendor->id)->exists()) {
                        $slug = $baseSlug.'-'.$counter++;
                    }

                    $data['slug'] = $slug;
                }
            }

            $vendor->update($data);

            return $vendor->refresh();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update vendor profile: '.$e->getMessage(), 0, $e);
        }
    }
}
