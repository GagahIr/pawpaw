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
                    'verified' => true,
                    'active_subscription' => true,
                ],
                perPage: 12,
            ),
            'categories' => $vendorService->getCategories(),
            'totalVendors' => $vendorService->getActiveVendorCount(),
        ];
    }
};
?>
<div class="clinic-page clinic-directory">
    @include('components.client.components.clinic-atmosphere')
    <div class="clinic-request-progress" wire:loading.delay wire:target="search,selectCategory,gotoPage,previousPage,nextPage" aria-hidden="true"><span></span></div>
    <p class="sr-only" role="status" wire:loading.delay wire:target="search,selectCategory,gotoPage,previousPage,nextPage">Sedang mencari teman perawatan untuk hewanmu.</p>
    <section class="clinic-hero" aria-labelledby="clinic-heading">
        <div class="clinic-hero-inner">
            <div class="clinic-hero-copy">
                <p class="clinic-eyebrow">TEMAN MERAWAT SI KESAYANGAN</p>
                <h1 id="clinic-heading" class="clinic-title">Temukan klinik,<br><span class="clinic-hero-accent">berikan yang terbaik.<svg class="clinic-title-swoosh" viewBox="0 0 500 24" preserveAspectRatio="none" aria-hidden="true"><path d="M8 16Q160 2 310 12T490 8" pathLength="1" /></svg></span></h1>
                <p class="clinic-intro">Dari perawatan kesehatan, grooming, hingga pet hotel. Temukan layanan yang pas untuk hewanmu.</p>
                <div class="clinic-search">
                    <img src="{{ asset('assets/img/searching.webp') }}" alt="" width="24" height="24">
                    <label for="clinic-search" class="sr-only">Cari nama atau lokasi klinik</label>
                    <input id="clinic-search" type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Cari nama atau lokasi klinik…" autocomplete="off">
                </div>
            </div>
            <div class="clinic-paw-trail" aria-hidden="true">
                @for ($step = 0; $step < 5; $step++)
                    <img src="{{ asset('assets/pawkucing.webp') }}" alt="" width="25" height="25" style="--paw-step: {{ $step }}">
                @endfor
            </div>
            <div class="clinic-hero-art clinic-hero-care" wire:ignore>
                <object data-clinic-hero-object data="{{ asset('assets/pawpaw-hero-care.svg') }}" type="image/svg+xml" aria-label="Anjing dan kucing dengan layanan Klinik dan Medis, Grooming, serta Pet Hotel" width="1280" height="1180">
                    <img src="{{ asset('assets/pawpaw-hero-care.svg') }}" alt="Anjing dan kucing dengan layanan Klinik dan Medis, Grooming, serta Pet Hotel" width="1280" height="1180">
                </object>
            </div>
        </div>
    </section>
    <div class="clinic-wrap">
        <section class="clinic-filter-band" aria-label="Filter kategori layanan">
            @include('components.client.components.clinic-wave')
            <div class="clinic-filters">
                <button type="button" wire:click="selectCategory(null)"
                    aria-pressed="{{ $categoryId === null ? 'true' : 'false' }}"
                    class="clinic-filter {{ $categoryId === null ? 'is-active' : '' }}">
                    <flux:icon.squares-2x2 class="size-5" /> Semua
                </button>
                @foreach ($categories as $category)
                    <button type="button" wire:key="cat-{{ $category->id }}"
                        wire:click="selectCategory({{ $category->id }})"
                        aria-pressed="{{ $categoryId === $category->id ? 'true' : 'false' }}"
                        class="clinic-filter {{ $categoryId === $category->id ? 'is-active' : '' }}">
                        @if (strtolower($category->name) === 'grooming')
                            <flux:icon.scissors class="size-5" />
                        @elseif (strtolower($category->name) === 'pet hotel')
                            <flux:icon.home class="size-5" />
                        @else
                            <flux:icon.heart class="size-5" />
                        @endif
                        {{ strtolower($category->name) === 'clinic' ? 'Klinik / Medis' : $category->name }}
                    </button>
                @endforeach
            </div>
        </section>

        <section class="clinic-results" aria-labelledby="clinic-results-heading">
            <div class="clinic-results-header" aria-live="polite" aria-atomic="true">
                <div>
                    <p class="clinic-eyebrow">PILIH TEMPAT PERAWATANNYA</p>
                    <h2 id="clinic-results-heading" class="clinic-section-title">Kenalan dengan klinik pilihanmu</h2>
                </div>
                <p class="clinic-result-count">Menampilkan {{ $vendors->count() }} dari {{ $vendors->total() }} klinik</p>
            </div>
            @if ($search !== '')
                <p class="clinic-search-summary">Hasil pencarian untuk “{{ $search }}”</p>
            @endif
            <div wire:loading.class="opacity-50" wire:target="search,selectCategory,gotoPage,previousPage,nextPage" class="transition-opacity">
                @if ($vendors->count())
                    <div class="clinic-grid">
                        @foreach ($vendors as $vendor)
                            <a wire:key="vendor-{{ $vendor->id }}" href="{{ route('client.vendor.detail', $vendor->slug) }}" class="clinic-card">
                                @include('components.client.components.clinic-media', ['vendor' => $vendor])
                                <div class="clinic-card-body">
                                    <h3>{{ $vendor->name }}</h3>
                                    <p class="clinic-card-address"><flux:icon.map-pin class="size-4 shrink-0" /><span>{{ $vendor->address ?: 'Alamat belum tersedia' }}</span></p>
                                    <div class="clinic-badges">
                                        @foreach ($vendor->vendorServices->pluck('categoryService.name')->filter()->unique() as $name)
                                            <span class="clinic-badge">{{ strtolower($name) === 'clinic' ? 'Klinik / Medis' : $name }}</span>
                                        @endforeach
                                    </div>
                                    <div class="clinic-card-action"><span>Lihat Klinik<span class="sr-only"> {{ $vendor->name }}</span></span><span class="clinic-arrow" aria-hidden="true">↗</span></div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    @if ($vendors->hasPages())
                        <div class="clinic-pagination">{{ $vendors->onEachSide(1)->links('components.client.components.clinic-pagination') }}</div>
                    @endif
                @else
                    <div class="clinic-empty">
                        <img src="{{ asset('assets/img/searching.webp') }}" alt="" width="52" height="52">
                        <h3>Klinik belum ditemukan</h3>
                        <p>Coba nama, lokasi, atau kategori layanan lainnya, ya.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
    @include('components.client.components.clinic-footer')
</div>
