<?php

use App\Models\CategoryService;
use App\Models\VendorService;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::admin')] class extends Component {
    public $services = [];

    public function mount()
    {
        $categories = CategoryService::take(3)->get();

        $this->services = $categories
            ->map(function ($category) {
                $vendorService = VendorService::where('category_service_id', $category->id)->first();

                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'vendor_service_id' => $vendorService?->id,
                    'status' => $vendorService?->status_service ?? false,
                ];
            })
            ->toArray();
    }

    public function toggleService($categoryId)
    {
        $vendorService = VendorService::where('category_service_id', $categoryId)->first();

        if ($vendorService) {
            $vendorService->update([
                'status_service' => !$vendorService->status_service,
            ]);
        } else {
            VendorService::create([
                'category_service_id' => $categoryId,
                'status_service' => true,
            ]);
        }

        $this->mount();
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Vendor Services</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Kelola layanan yang tersedia untuk vendor Anda</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($services as $service)
            <flux:card class="space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $service['category_name'] }}
                        </h3>
                        <flux:switch wire:click="toggleService({{ $service['category_id'] }})"
                            :checked="$service['status']" />
                    </div>

                    <flux:badge size="sm" :color="$service['status'] ? 'green' : 'zinc'" class="mt-2">
                        {{ $service['status'] ? 'Aktif' : 'Nonaktif' }}
                    </flux:badge>
                </div>

                <flux:separator />

                @if ($service['status'])
                    <flux:button
                        href="{{ route('vendor.admin.services.manage', ['categoryServiceId' => $service['category_id']]) }}"
                        variant="ghost" class="w-full">
                        Kelola Item Service
                    </flux:button>
                @else
                    <flux:button disabled variant="ghost" class="w-full">
                        Aktifkan untuk kelola
                    </flux:button>
                @endif
            </flux:card>
        @endforeach
    </div>
</div>
