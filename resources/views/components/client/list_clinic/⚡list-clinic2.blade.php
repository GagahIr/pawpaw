<?php

use App\Services\VendorService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.client')] class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'layanan', except: null)]
    public ?int $categoryId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectCategory(?int $id): void
    {
        $this->categoryId = $this->categoryId === $id ? null : $id;
        $this->resetPage();
    }

    public function with(VendorService $vendorService): array
    {
        // $service = VendorService::class;

        return [
            'vendors' => $vendorService->getVendors(
                [
                    'search' => $this->search,
                    'category_id' => $this->categoryId,
                ],
                perPage: 12,
            ),
            'categories' => $vendorService->getCategories(),
            'totalVendors' => $vendorService->getActiveVendorCount(),
        ];
    }
};
?>

<div class="min-h-screen py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">
                Klinik yang Tersedia
            </h1>
            <p class="text-lg text-gray-600">
                Temukan layanan kesehatan dan grooming terbaik untuk hewan kesayangan Anda
            </p>
        </div>

        <!-- Search & Filter -->
        <div class="mb-8 space-y-4">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari klinik atau grooming..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#EE6D52]" />

            <div class="flex flex-wrap gap-2">
                @php
                    $base = 'px-4 py-2 rounded-full text-sm font-medium border transition-colors cursor-pointer';
                    $active = 'bg-[#EE6D52] border-[#EE6D52] text-white';
                    $idle = 'bg-white border-gray-300 text-gray-700 hover:border-[#EE6D52] hover:text-[#EE6D52]';
                @endphp

                <button type="button" wire:click="selectCategory(null)"
                    class="{{ $base }} {{ $categoryId === null ? $active : $idle }}">
                    Semua
                </button>

                @foreach ($categories as $category)
                    <button type="button" wire:key="cat-{{ $category->id }}"
                        wire:click="selectCategory({{ $category->id }})"
                        class="{{ $base }} {{ $categoryId === $category->id ? $active : $idle }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Result Info -->
        <div class="mb-6 text-sm text-gray-600">
            Menampilkan
            <span class="font-semibold">{{ $vendors->count() }}</span>
            dari
            <span class="font-semibold">{{ $vendors->total() }}</span>
            vendor
            @if ($search !== '')
                untuk "{{ $search }}"
            @endif
        </div>

        <div wire:loading.class="opacity-50" wire:target="search,selectCategory,gotoPage,previousPage,nextPage"
            class="transition-opacity">
            <!-- Vendor Grid -->
            @if ($vendors->count())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    @forelse ($vendors as $vendor)
                        <a href="{{ route('client.vendor.detail', $vendor->slug) }}"
                            class="group bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden">
                            <!-- Header Info -->
                            <div class="p-4 border-b border-gray-200">
                                <img src="{{ asset('storage/' . $vendor->logo) }}" class="rounded-md" alt="">
                                <h3
                                    class="text-lg font-semibold text-gray-900 group-hover:text-[#EE6D52] transition-colors">
                                    {{ $vendor->name }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    📍 {{ $vendor->address }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $vendor->description }}
                                </p>
                            </div>

                            <!-- Contact Info -->
                            <div class="p-4 space-y-2 text-sm">
                                <p class="text-gray-700">
                                    <span class="font-medium">Kontak:</span> {{ $vendor->contact }}
                                </p>
                                @if ($vendor->description)
                                    <p class="text-gray-600 line-clamp-2">
                                        {{ $vendor->description }}
                                    </p>
                                @endif
                                <div class="flex flex-wrap gap-1 pt-1">
                                    @foreach ($vendor->vendorServices->pluck('categoryService.name')->filter()->unique() as $name)
                                        <span
                                            class="px-2 py-0.5 rounded-full bg-orange-50 text-[#EE6D52] text-xs">{{ $name }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Action -->
                            <div class="p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                                <span class="text-sm font-medium text-[#EE6D52]">
                                    Lihat Detail
                                </span>
                                <svg class="w-4 h-4 text-[#EE6D52] group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7">
                                    </path>
                                </svg>
                            </div>
                        </a>
                    @empty
                    @endforelse
                </div>

                <!-- Pagination -->
                @if ($vendors->hasPages())
                    <div class="flex justify-center">
                        {{ $vendors->links() }}
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    <p class="mt-4 text-gray-900 font-medium">Vendor tidak ditemukan</p>
                    <p class="text-gray-500">Coba ubah filter pencarian Anda</p>
                </div>
            @endif
        </div>
    </div>
</div>
