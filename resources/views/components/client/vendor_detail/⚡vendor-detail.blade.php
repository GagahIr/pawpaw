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
<div class="clinic-page clinic-detail">
    @include('components.client.components.clinic-atmosphere')
    <div class="clinic-wrap">
        <a href="{{ route('client.clinic') }}" wire:navigate class="clinic-back"><flux:icon.arrow-left class="size-4" /> Kembali ke daftar klinik</a>
        <section class="clinic-profile" aria-labelledby="vendor-heading">
            <div class="clinic-profile-visual" data-clinic-reveal>
                @include('components.client.components.clinic-media', ['vendor' => $vendor, 'mediaClass' => 'clinic-profile-media', 'showPhotoCredit' => true])
                <svg class="clinic-photo-outline" viewBox="0 0 600 400" preserveAspectRatio="none" fill="none" aria-hidden="true"><path d="M25 180C34 70 153 12 306 20C457 18 576 93 578 199C588 306 458 381 304 379C139 385 14 298 25 180Z" pathLength="1" /></svg>
                <span class="clinic-photo-doodle" aria-hidden="true">
                    <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 8v12a10 10 0 0 0 20 0V8M7 8h6M27 8h6M20 30v3a8 8 0 0 0 16 0v-5"/><circle cx="36" cy="24" r="4"/><path d="m35 8 2-3m4 9 4-1"/></svg>
                </span>
                <svg class="clinic-photo-spark" viewBox="0 0 40 40" fill="none" aria-hidden="true"><path d="M20 4Q20 20 36 20Q20 20 20 36Q20 20 4 20Q20 20 20 4Z" stroke="currentColor" stroke-width="2"/></svg>
            </div>
            <div class="clinic-profile-copy" data-clinic-reveal>
                <p class="clinic-eyebrow">KENAL LEBIH DEKAT</p>
                <h1 id="vendor-heading" class="clinic-title">{{ $vendor->name }}</h1>
                <p class="clinic-full-address"><flux:icon.map-pin class="size-5 shrink-0" /><span>{{ $vendor->address ?: 'Alamat belum tersedia' }}</span></p>
                <div class="clinic-badges">
                    @foreach ($vendor->vendorServices->pluck('categoryService.name')->filter()->unique() as $name)
                        <span class="clinic-badge">{{ strtolower($name) === 'clinic' ? 'Klinik / Medis' : $name }}</span>
                    @endforeach
                </div>
                @if ($vendor->description)
                    <p class="clinic-description">{{ $vendor->description }}</p>
                @endif
                <a href="{{ route('client.clinic.booking', $vendor->slug) }}" class="clinic-book-button clinic-profile-book">Buat Reservasi <flux:icon.arrow-right class="size-5" /></a>
            </div>
        </section>

        <div class="clinic-detail-intro">
            <p class="clinic-eyebrow">LAYANAN & HARGA</p>
            <h2 id="services-heading" class="clinic-section-title">Perawatan untuk<br><span>si kesayangan.</span></h2>
            <p class="clinic-services-intro">Kenali pilihan perawatannya sebelum membuat reservasi.</p>
        </div>
        <div class="clinic-detail-grid">
            <section class="clinic-services" aria-labelledby="services-heading">
                @include('components.client.components.clinic-service-groups', ['vendor' => $vendor])
            </section>

            <aside class="clinic-visit" aria-labelledby="visit-heading" data-clinic-reveal>
                <img class="clinic-visit-paw" src="{{ asset('assets/pawkucing.webp') }}" alt="" width="38" height="38">
                <h2 id="visit-heading" class="clinic-section-title">Rencanakan<br>kunjunganmu</h2>
                <section class="clinic-visit-panel" aria-labelledby="hours-heading">
                    <h3 id="hours-heading" class="clinic-panel-heading"><flux:icon.clock class="size-5" /> Jam Operasional</h3>
                    <dl class="clinic-hours">
                        @forelse ($vendor->vendorOperatingHours as $hour)
                            <div wire:key="hour-{{ $hour->id }}">
                                <dt>{{ $hour->day_of_week->label() }}</dt>
                                <dd class="{{ $hour->is_closed ? 'clinic-closed' : '' }}">
                                    @if ($hour->is_closed)
                                        Tutup
                                    @elseif ($hour->open_time && $hour->close_time)
                                        {{ substr($hour->open_time, 0, 5) }} – {{ substr($hour->close_time, 0, 5) }}
                                    @else
                                        Belum diatur
                                    @endif
                                </dd>
                            </div>
                        @empty
                            <div>
                                <dt class="sr-only">Jadwal</dt>
                                <dd class="clinic-muted">Jam operasional belum diatur.</dd>
                            </div>
                        @endforelse
                    </dl>
                </section>
                @if ($vendor->contact)
                    <section class="clinic-contact clinic-visit-panel" aria-labelledby="contact-heading">
                        <h3 id="contact-heading" class="clinic-panel-heading">Kontak Klinik</h3>
                        <p><flux:icon.phone class="size-5 shrink-0" /><span>{{ $vendor->contact }}</span></p>
                    </section>
                @endif
                <a href="{{ route('client.clinic.booking', $vendor->slug) }}" class="clinic-book-button">Buat Reservasi <flux:icon.arrow-right class="size-5" /></a>
            </aside>
        </div>
    </div>
    @include('components.client.components.clinic-footer')
</div>
