@extends('landing_page.layouts.app-landing')

@section('content')
<div class="landing-page">
    <section class="landing-hero" aria-labelledby="landing-heading">
        <div class="landing-wrap landing-hero-grid">
            <div data-animate="fade-up">
                <p class="landing-eyebrow">Teman merawat si kesayangan</p>
                <h1 id="landing-heading" class="landing-title">Si kesayangan terawat.<br><span class="landing-title-accent">Kamu lebih tenang.<svg viewBox="0 0 500 24" preserveAspectRatio="none" aria-hidden="true"><path d="M8 16Q160 2 310 12T490 8" pathLength="1" /></svg></span></h1>
                <p class="landing-description">Cari layanan klinik, grooming, dan pet hotel untuk si kesayangan. Lihat layanan dan harganya, lalu ajukan reservasi sesuai jadwal yang tersedia.</p>
                <div class="landing-hero-actions">
                    <a href="{{ route('client.clinic') }}" class="landing-button">Jelajahi Klinik <span aria-hidden="true">&rarr;</span></a>
                    <a href="#for-partners" class="landing-text-link">Untuk Pemilik Klinik <span aria-hidden="true">&rarr;</span></a>
                </div>
                @auth('client')
                    <p class="landing-entry-note">Pilih klinik dan layanan, lalu ajukan reservasi untuk si kesayangan.</p>
                @else
                    <p class="landing-entry-note">Masuk sebagai pelanggan untuk memilih klinik dan membuat reservasi.</p>
                @endauth
            </div>
            <div class="landing-hero-visual" data-animate="fade-up" data-animate-delay="0.12">
                <span class="landing-hero-halo" aria-hidden="true"></span>
                <img src="{{ asset('assets/img/landing-pets-hero.webp') }}" alt="Anjing dan kucing, sahabat yang siap dirawat" width="1254" height="1254" fetchpriority="high">
                <p class="landing-hero-caption" data-motion-once><flux:icon.check-badge class="size-5" aria-hidden="true" /> Mitra terverifikasi <span aria-hidden="true">&middot;</span> Jadwal lebih teratur</p>
            </div>
        </div>
        <div class="landing-trust-band">
            @include('components.client.components.clinic-wave')
        <ul class="landing-wrap landing-trust" aria-label="Manfaat PawPaw">
            <li><flux:icon.check-badge class="size-5" aria-hidden="true" /> Verifikasi profil mitra</li>
            <li><flux:icon.heart class="size-5" aria-hidden="true" /> Pilihan layanan pet care</li>
            <li><flux:icon.calendar-days class="size-5" aria-hidden="true" /> Jadwal lebih teratur</li>
            <li><flux:icon.clock class="size-5" aria-hidden="true" /> Riwayat reservasi tersimpan</li>
        </ul>
        </div>
    </section>

    <section id="services" class="landing-section landing-services" aria-labelledby="services-heading">
        <div class="landing-wrap">
            <div class="landing-section-heading" data-animate="fade-up">
                <p class="landing-eyebrow">Sesuai kebutuhan mereka</p>
                <h2 id="services-heading" class="landing-heading">Lagi butuh perawatan apa<br><span>untuk si kesayangan?</span></h2>
                <p>Mau periksa kesehatan, waktunya grooming, atau perlu tempat menginap? Kenali dulu pilihan layanannya.</p>
            </div>
            <div class="landing-service-grid">
                @forelse ($categories as $category)
                    @include('landing_page.components.service-card', ['category' => $category])
                @empty
                    <p class="landing-empty">Pilihan layanan sedang disiapkan. Kamu tetap bisa melihat daftar klinik.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section id="features" class="landing-section" aria-labelledby="features-heading">
        <div class="landing-wrap landing-features-grid">
            <div class="landing-feature-visual" data-animate="fade-up">
                <img src="{{ asset('assets/cat-sleep-illustration.webp') }}" alt="" width="929" height="734" loading="lazy">
                <svg class="landing-butterfly" data-motion-once viewBox="0 0 48 48" fill="none" aria-hidden="true" focusable="false">
                    <path class="landing-butterfly-wing landing-butterfly-wing-left" d="M24 23C18 4 3 7 8 20C2 29 14 37 24 27Z" fill="#e7a88a" stroke="#bd715a" stroke-width="1.4" />
                    <path class="landing-butterfly-wing landing-butterfly-wing-right" d="M24 23C30 4 45 7 40 20C46 29 34 37 24 27Z" fill="#f6d48c" stroke="#bd715a" stroke-width="1.4" />
                    <path d="M24 19V31M24 20L20 15M24 20L28 15" stroke="#40674c" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            </div>
            <div>
                <p class="landing-eyebrow">Lebih tenang merawatnya</p>
                <h2 id="features-heading" class="landing-heading">Lebih mudah memilih.<br><span>Lebih siap datang.</span></h2>
                <ul class="landing-benefits">
                    <li><flux:icon.magnifying-glass class="size-5" aria-hidden="true" /><div><h3>Cari berdasarkan kebutuhan</h3><p>Temukan mitra lewat nama, lokasi, dan kategori layanan.</p></div></li>
                    <li><flux:icon.building-office-2 class="size-5" aria-hidden="true" /><div><h3>Kenali informasi mitra</h3><p>Lihat alamat, kontak, jam operasional, serta pilihan layanan dan harganya.</p></div></li>
                    <li><flux:icon.calendar-days class="size-5" aria-hidden="true" /><div><h3>Pilih jadwal yang pas</h3><p>Sesuaikan tanggal atau slot reservasi dengan layanan yang dipilih.</p></div></li>
                    <li><flux:icon.clipboard-document-list class="size-5" aria-hidden="true" /><div><h3>Pantau reservasimu</h3><p>Lihat status dan riwayat reservasi dari akun pelanggan.</p></div></li>
                </ul>
                <p class="landing-service-note">Kamu memilih mitranya, PawPaw membantu reservasinya. Perawatan tetap dilakukan oleh klinik yang kamu pilih.</p>
            </div>
        </div>
        <div class="landing-wrap landing-product-wrap">
            @include('landing_page.components.product-preview', ['audience' => 'client'])
        </div>
    </section>

    <section id="how-it-works" class="landing-section landing-how" aria-labelledby="how-heading">
        @include('components.client.components.clinic-wave')
        <div class="landing-wrap">
            <p class="landing-eyebrow">Tiga langkah sederhana</p>
            <h2 id="how-heading" class="landing-heading">Cari klinik, pilih jadwal,<br>lalu datang.</h2>
            <div class="landing-journey" data-motion-once>
                <svg class="landing-journey-line" viewBox="0 0 1000 60" preserveAspectRatio="none" fill="none" aria-hidden="true" focusable="false"><path d="M0 30C180 0 320 60 500 30S820 0 1000 30" pathLength="1" /></svg>
            <ol class="landing-steps">
                <li data-animate="fade-up"><span class="landing-step-number" aria-hidden="true">01</span><img src="{{ asset('assets/map.webp') }}" alt="" width="48" height="48" loading="lazy"><h3>Cari kliniknya</h3><p>Masuk sebagai pelanggan, lalu buka menu Klinik. Cari berdasarkan nama, lokasi, atau layanan yang kamu butuhkan.</p></li>
                <li data-animate="fade-up" data-animate-delay="0.08"><span class="landing-step-number" aria-hidden="true">02</span><img src="{{ asset('assets/calendar.webp') }}" alt="" width="48" height="48" loading="lazy"><h3>Pilih layanan dan jadwal</h3><p>Cek informasi dan harga di profil klinik. Pilih slot waktu atau tanggal menginap sesuai kebutuhan.</p></li>
                <li data-animate="fade-up" data-animate-delay="0.16"><span class="landing-step-number" aria-hidden="true">03</span><img src="{{ asset('assets/list.webp') }}" alt="" width="48" height="48" loading="lazy"><h3>Tunggu konfirmasi, lalu datang</h3><p>Cek statusnya di akunmu. Setelah reservasi diterima, datang sesuai jadwal dan bayar layanan langsung di klinik.</p></li>
            </ol>
            </div>
            <a href="{{ route('client.clinic') }}" class="landing-button landing-button-light">Cari layanan <span aria-hidden="true">&rarr;</span></a>
        </div>
    </section>

    @include('landing_page.components.partner-section')
    @include('landing_page.components.faq')
</div>
@endsection
