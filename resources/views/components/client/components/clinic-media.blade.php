@php
    $photos = collect(config('clinic-demo-photos', []));
    $photo = $photos->firstWhere('image_url', $vendor->logo);
    if (! $vendor->logo && $photos->isNotEmpty()) {
        $photo = $photos->get(((int) $vendor->id) % $photos->count());
    }
    $imageUrl = $photo['image_url'] ?? ($vendor->logo ? asset('storage/' . $vendor->logo) : '');
@endphp

<figure class="clinic-media-frame" x-data="{ failed: false }" wire:key="clinic-media-{{ $vendor->id }}-{{ md5($imageUrl) }}">
    <div class="clinic-media {{ $mediaClass ?? '' }}">
        <div class="clinic-media-placeholder" x-show="failed || {{ $imageUrl ? 'false' : 'true' }}" @if ($imageUrl) style="display: none" @endif>
            <img src="{{ asset('assets/img/cat-dog.webp') }}" alt="Ilustrasi anjing dan kucing" width="220" height="200">
            <span>Teman merawat si kesayangan</span>
        </div>
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $photo['alt'] ?? ('Logo ' . $vendor->name) }}" loading="lazy" decoding="async"
                x-show="!failed" x-on:error="failed = true" class="{{ $photo ? 'clinic-stock-photo' : 'clinic-vendor-logo' }}" />
        @endif
        @if ($photo && empty($showPhotoCredit))
            <span class="clinic-photo-label" x-show="!failed">Foto ilustrasi &middot; Pexels</span>
        @endif
    </div>
    @if ($photo && ! empty($showPhotoCredit))
        <figcaption class="clinic-photo-credit" x-show="!failed">
            Foto ilustrasi oleh <a href="{{ $photo['source_url'] }}" target="_blank" rel="noopener noreferrer">{{ $photo['photographer'] }}</a>
            &middot; <a href="{{ $photo['license_url'] }}" target="_blank" rel="noopener noreferrer">Lisensi Pexels</a>
        </figcaption>
    @endif
</figure>
