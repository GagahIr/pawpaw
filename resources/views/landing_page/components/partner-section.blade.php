<section id="for-partners" class="landing-section landing-partner-section" aria-labelledby="partners-heading">
    <div class="landing-wrap landing-partners">
        <div data-animate="fade-up">
            <p class="landing-eyebrow">Untuk pemilik bisnis pet care</p>
            <h2 id="partners-heading" class="landing-heading">Bantu pelanggan menemukan klinikmu.<br><span>Reservasinya pun lebih tertata.</span></h2>
            <p class="landing-description">Kenalkan layanan klinikmu ke pemilik hewan. Urusan layanan, jadwal, dan reservasi bisa kamu kelola dari satu tempat.</p>
            <ul class="landing-partner-benefits">
                <li><flux:icon.check class="size-5" aria-hidden="true" /> Tampilkan profil klinik, layanan, dan harga</li>
                <li><flux:icon.check class="size-5" aria-hidden="true" /> Atur jam operasional dan tim klinik</li>
                <li><flux:icon.check class="size-5" aria-hidden="true" /> Pantau reservasi dan lihat ringkasan bisnis</li>
            </ul>
            <a href="{{ route('vendor.register') }}" class="landing-button">Gabung Jadi Mitra <span aria-hidden="true">&rarr;</span></a>
            <a href="{{ route('vendor.domain') }}" class="landing-text-link landing-partner-login">Sudah punya akun? Masuk mitra</a>
        </div>
        @include('landing_page.components.product-preview', ['audience' => 'partner'])
        <div class="landing-onboarding" data-animate="fade-up">
            <img class="landing-onboarding-paw" src="{{ asset('assets/pawkucing.webp') }}" alt="" width="56" height="56" aria-hidden="true">
            <h3>Mulai jadi mitra PawPaw</h3>
            <ol>
                <li><span aria-hidden="true">1</span><div><h4>Daftarkan bisnismu</h4><p>Lengkapi akun pemilik dan informasi profil bisnis.</p></div></li>
                <li><span aria-hidden="true">2</span><div><h4>Tunggu verifikasi</h4><p>Kami akan memeriksa informasi pendaftaran klinikmu.</p></div></li>
                <li><span aria-hidden="true">3</span><div><h4>Aktifkan langganan</h4><p>Setelah disetujui, lakukan pembayaran langganan.</p></div></li>
                <li><span aria-hidden="true">4</span><div><h4>Siap menerima reservasi</h4><p>Atur layanan, jam operasional, dan tim klinikmu.</p></div></li>
            </ol>
            <p class="landing-subscription-note"><flux:icon.information-circle class="size-5 shrink-0" aria-hidden="true" /><span>Setelah pendaftaran disetujui, aktifkan paket langganan berbayar. Langganan diperpanjang sendiri saat dibutuhkan, bukan otomatis.</span></p>
        </div>
    </div>
</section>
