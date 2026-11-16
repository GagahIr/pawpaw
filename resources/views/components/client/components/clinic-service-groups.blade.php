@forelse ($vendor->vendorServices->groupBy('category_service_id') as $categoryId => $services)
    @php
        $categoryName = $services->first()->categoryService?->name ?? 'Layanan';
        $categoryType = strtolower($categoryName);
        $items = $services->flatMap->vendorServiceItems;
    @endphp
    <div wire:key="service-category-{{ $categoryId }}" data-clinic-reveal class="clinic-service-group clinic-service-card {{ $categoryType === 'grooming' ? 'clinic-service-card-peach' : '' }}">
        <span class="clinic-service-doodle" aria-hidden="true">
            @if ($categoryType === 'grooming')
                <flux:icon.scissors />
            @elseif ($categoryType === 'pet hotel')
                <flux:icon.home />
            @else
                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M10 8v12a10 10 0 0 0 20 0V8M7 8h6M27 8h6M20 30v3a8 8 0 0 0 16 0v-5"/><circle cx="36" cy="24" r="4"/></svg>
            @endif
        </span>
        <h3 class="clinic-service-heading">
            <span class="clinic-service-icon" aria-hidden="true">
                @if ($categoryType === 'grooming')
                    <flux:icon.scissors class="size-6" />
                @elseif ($categoryType === 'pet hotel')
                    <flux:icon.home class="size-6" />
                @else
                    <flux:icon.heart class="size-6" />
                @endif
            </span>
            {{ $categoryType === 'clinic' ? 'Klinik / Medis' : $categoryName }}
        </h3>
        @if ($items->isNotEmpty())
            <ul class="clinic-treatment-list">
                @foreach ($items as $item)
                    <li wire:key="item-{{ $item->id }}" class="clinic-treatment">
                        <span class="clinic-treatment-name">{{ $item->name }}</span>
                        <span class="clinic-price">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="clinic-muted">Detail perawatan belum tersedia.</p>
        @endif
    </div>
@empty
    <div class="clinic-empty clinic-empty-services"><h3>Belum ada layanan</h3><p>Layanan klinik ini belum tersedia.</p></div>
@endforelse
