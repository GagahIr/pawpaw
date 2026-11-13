@php
    $logoUrl = $vendor->logo ? asset('storage/' . $vendor->logo) : '';
@endphp

<div class="clinic-media {{ $mediaClass ?? '' }}" x-data="{ failed: false, source: @js($logoUrl) }"
    x-effect="if (source !== @js($logoUrl)) { source = @js($logoUrl); failed = false; }">
    <div class="clinic-media-placeholder" x-show="failed || !source" @if ($logoUrl) style="display: none" @endif>
        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M10 9h4M12 7v4" />
        </svg>
        <span>Logo klinik belum tersedia</span>
    </div>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo {{ $vendor->name }}" loading="lazy" decoding="async"
            x-show="!failed" x-on:error="failed = true" class="clinic-vendor-logo" />
    @endif
</div>
