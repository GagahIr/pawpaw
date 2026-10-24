<?php

use App\Services\VendorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.client')] class extends Component {
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function with(VendorService $vendorService): array
    {
        $vendor = $vendorService->getVendorDetail($this->slug);

        abort_if(!$vendor, 404);

        return ['vendor' => $vendor];
    }
};
?>

<div class="min-h-screen py-12 px-4">
    <div class="max-w-5xl mx-auto space-y-8">

        <a href="{{ route('client.clinic') }}" wire:navigate
            class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-[#EE6D52] transition-colors">
            ← Kembali ke daftar klinik
        </a>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow p-6 sm:p-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ $vendor->name }}</h1>
            <p class="mt-2 text-gray-600">📍 {{ $vendor->address }}</p>

            <div class="flex flex-wrap gap-2 mt-4">
                @foreach ($vendor->vendorServices->pluck('categoryService.name')->filter()->unique() as $name)
                    <span class="px-3 py-1 rounded-full bg-orange-50 text-[#EE6D52] text-sm font-medium">
                        {{ $name }}
                    </span>
                @endforeach
            </div>

            @if ($vendor->description)
                <p class="mt-6 text-gray-700 leading-relaxed">{{ $vendor->description }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                {{-- @if ($vendor->contact)
                    <a href="tel:{{ $vendor->contact }}"
                        class="px-5 py-2.5 rounded-lg bg-[#EE6D52] text-white text-sm font-medium hover:opacity-90 transition-opacity">
                        Hubungi: {{ $vendor->contact }}
                    </a>
                @endif --}}

                {{-- @if ($vendor->latitude && $vendor->longitude)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $vendor->latitude }},{{ $vendor->longitude }}"
                        target="_blank" rel="noopener"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:border-[#EE6D52] hover:text-[#EE6D52] transition-colors">
                        Buka di Google Maps
                    </a>
                @endif --}}
                <a href="{{ route("landing_page") }}">
                    <flux:button variant="primary">Reservasi Sekarang</flux:button>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Layanan -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Layanan</h2>

                @forelse ($vendor->vendorServices as $service)
                    <div wire:key="service-{{ $service->id }}"
                        class="py-4 border-t border-gray-100 first:border-t-0 first:pt-0">
                        <h3 class="font-medium text-gray-900">{{ $service->categoryService?->name }}</h3>

                        @if ($service->vendorServiceItems->isNotEmpty())
                            <ul class="mt-2 space-y-1 text-sm text-gray-600">
                                @foreach ($service->vendorServiceItems as $item)
                                    <li wire:key="item-{{ $item->id }}" class="flex justify-between gap-4">
                                        <span>{{ $item->name }}</span>
                                        <span class="font-medium text-gray-800">
                                            Rp {{ number_format($item->price, 0, ',', '.') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Belum ada layanan yang tersedia.</p>
                @endforelse
            </div>

            <!-- Jam operasional -->
            <div class="bg-white rounded-lg shadow p-6 h-fit">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Jam Operasional</h2>

                @forelse ($vendor->vendorOperatingHours as $hour)
                    <div wire:key="hour-{{ $hour->id }}"
                        class="flex justify-between py-2 text-sm border-t border-gray-100 first:border-t-0">
                        <span class="text-gray-700">{{ $hour->day_of_week->label() }}</span>
                        <span class="font-medium text-gray-900">
                            @if ($hour->is_closed)
                                Tutup
                            @else
                                {{ substr($hour->open_time, 0, 5) }} - {{ substr($hour->close_time, 0, 5) }}
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Jam operasional belum diatur.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
