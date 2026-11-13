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
    <div class="clinic-wrap">
        <a href="{{ route('client.clinic') }}" wire:navigate class="clinic-back"><flux:icon.arrow-left class="size-4" /> Kembali ke daftar klinik</a>
        <section class="clinic-profile" aria-labelledby="vendor-heading">
            @include('components.client.components.clinic-media', ['vendor' => $vendor, 'mediaClass' => 'clinic-profile-media'])
            <div class="clinic-profile-copy">
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
            </div>
        </section>

        <div class="clinic-detail-grid">
            <section class="clinic-services" aria-labelledby="services-heading">
                <p class="clinic-eyebrow">LAYANAN & HARGA</p>
                <h2 id="services-heading" class="clinic-section-title">Perawatan untuk<br><span>si kesayangan.</span></h2>
                <p class="clinic-services-intro">Kenali pilihan perawatannya sebelum membuat reservasi.</p>
                @forelse ($vendor->vendorServices as $service)
                    <div wire:key="service-{{ $service->id }}" class="clinic-service-group">
                        <h3 class="clinic-badge">{{ strtolower($service->categoryService?->name ?? '') === 'clinic' ? 'Klinik / Medis' : ($service->categoryService?->name ?? 'Layanan') }}</h3>
                        @if ($service->vendorServiceItems->isNotEmpty())
                            <ul class="clinic-treatment-list">
                                @foreach ($service->vendorServiceItems as $item)
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
            </section>

            <aside class="clinic-visit" aria-labelledby="visit-heading">
                <img class="clinic-visit-paw" src="{{ asset('assets/pawkucing.webp') }}" alt="" width="38" height="38">
                <h2 id="visit-heading" class="clinic-section-title">Rencanakan<br>kunjunganmu</h2>
                <section aria-labelledby="hours-heading">
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
                    <section class="clinic-contact" aria-labelledby="contact-heading">
                        <h3 id="contact-heading" class="clinic-panel-heading">Kontak Klinik</h3>
                        <p><flux:icon.phone class="size-5 shrink-0" /><span>{{ $vendor->contact }}</span></p>
                    </section>
                @endif
                <a href="{{ route('client.clinic.booking', $vendor->slug) }}" class="clinic-book-button">Buat Reservasi <flux:icon.arrow-right class="size-5" /></a>
            </aside>
        </div>
        <div class="clinic-bottom-note"><img src="{{ asset('assets/pawkucing.webp') }}" alt="" width="28" height="28"><p>Perawatan yang tepat dimulai dari pilihanmu.</p></div>
    </div>
</div>
