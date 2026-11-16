<footer id="footer" class="clinic-footer" aria-label="PawPaw">
    <div class="clinic-footer-inner">
        <div class="clinic-footer-top">
            <div class="clinic-footer-brand">
                <a href="{{ route('landing_page') }}" aria-label="Beranda PawPaw">
                    <img src="{{ asset('assets/img/pawpaw-logo.webp') }}" alt="PawPaw" width="150" height="44" loading="lazy">
                </a>
                <p>Temukan klinik hewan tepercaya dan perawatan yang pas untuk si kesayangan. Lebih mudah, lewat PawPaw.</p>
            </div>
            <nav class="clinic-footer-nav" aria-label="Jelajahi PawPaw">
                <h2>Jelajahi</h2>
                <a href="{{ route('landing_page') }}">Beranda</a>
                <a href="{{ route('client.clinic') }}">Daftar Klinik</a>
                <a href="{{ route('landing_page') }}#how-it-works">Cara Kerja</a>
            </nav>
            <nav class="clinic-footer-nav" aria-label="Layanan PawPaw">
                <h2>Layanan</h2>
                <a href="{{ route('client.clinic') }}">Klinik / Medis</a>
                <a href="{{ route('client.clinic') }}">Grooming</a>
                <a href="{{ route('client.clinic') }}">Pet Hotel</a>
            </nav>
        </div>
        <div class="clinic-footer-actions">
            <section class="clinic-footer-cta clinic-footer-cta-partner clinic-footer-peek" aria-labelledby="footer-partner-heading">
                <span class="clinic-footer-icon-disc" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 29V9h18v20M4 29h24M12 9V3h8v6M16 4v4M14 6h4M12 14h1m6 0h1m-8 5h1m6 0h1M14 29v-5h4v5"/></svg>
                </span>
                <div class="clinic-footer-cta-copy">
                    <h2 id="footer-partner-heading">Punya klinik hewan?</h2>
                    <p>Perluas jangkauanmu dan temui lebih banyak pemilik hewan bersama PawPaw.</p>
                    <a href="{{ route('vendor.register') }}" class="clinic-footer-button">Daftarkan Klinik <span aria-hidden="true">&rarr;</span></a>
                </div>
            </section>
            <div class="clinic-footer-client-wrap clinic-footer-peek">
                <div class="clinic-footer-cat" aria-hidden="true">
                    <img src="{{ asset('assets/footer-cat-peek.png') }}" alt="" width="180" height="180" loading="lazy">
                </div>
                <section class="clinic-footer-cta clinic-footer-cta-client" aria-labelledby="footer-client-heading">
                    <div class="clinic-footer-cta-copy">
                        <h2 id="footer-client-heading">Untuk si kesayangan</h2>
                        <p>Dari pemeriksaan sampai menginap, temukan perawatan yang pas untuknya.</p>
                        <a href="{{ route('client.clinic') }}" class="clinic-footer-button">Temukan Perawatan <span aria-hidden="true">&rarr;</span></a>
                    </div>
                    <svg class="clinic-footer-paw" viewBox="0 0 80 80" fill="currentColor" aria-hidden="true"><path d="M24 48c-8 8-12 19-3 23 8 4 13-2 19-2s12 6 20 1c9-5 4-16-5-23-6-5-7-13-15-13s-10 8-16 14Z"/><ellipse cx="14" cy="32" rx="8" ry="11" transform="rotate(-25 14 32)"/><ellipse cx="31" cy="18" rx="8" ry="11" transform="rotate(-8 31 18)"/><ellipse cx="51" cy="17" rx="8" ry="11" transform="rotate(12 51 17)"/><ellipse cx="68" cy="31" rx="8" ry="11" transform="rotate(25 68 31)"/></svg>
                    <svg class="clinic-footer-cat-marks" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="m9 13 5 7m7-14 1 10m10 1-6 6"/></svg>
                </section>
            </div>
        </div>
        <div class="clinic-footer-bottom">
            <p>&copy; {{ date('Y') }} PawPaw. Teman merawat si kesayangan.</p>
            <nav aria-label="Informasi PawPaw">
                <a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a>
                <a href="{{ route('privacy') }}">Kebijakan Privasi</a>
            </nav>
        </div>
    </div>
</footer>
