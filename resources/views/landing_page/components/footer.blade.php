<footer class="pawpaw-footer">

    {{-- Decorative blob bottom-left --}}
    <div class="pawpaw-footer-blob-left" aria-hidden="true"></div>

    <div class="pawpaw-footer-inner">

        {{-- ── Column 1: Brand --}}
        <div class="pawpaw-footer-brand">
            <a href="{{ url('/') }}" class="pawpaw-footer-logo-link">
                <img
                    src="{{ asset('assets/pawpaw-logo.webp') }}"
                    alt="PawPaw Logo"
                    class="pawpaw-footer-logo-img"
                >
            </a>

            <p class="pawpaw-footer-desc">
                Lorem ipsum dolor sit amet consectetur adipiscing elit.
                Quisque faucibus ex sapien vitae pellentesque sem placerat.
            </p>

            {{-- Social Icons — 4 sejajar, no wrap --}}
            <div class="pawpaw-footer-socials">

                {{-- Instagram --}}
                <a href="#" aria-label="Instagram" class="pawpaw-social-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                        <circle cx="12" cy="12" r="4.5"/>
                        <circle cx="17.5" cy="6.5" r="0.1" fill="currentColor" stroke-width="3"/>
                    </svg>
                </a>

                {{-- TikTok --}}
                <a href="#" aria-label="TikTok" class="pawpaw-social-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.31 6.31 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.56a8.17 8.17 0 0 0 4.78 1.52V6.63a4.85 4.85 0 0 1-1.01.06z"/>
                    </svg>
                </a>

                {{-- YouTube --}}
                <a href="#" aria-label="YouTube" class="pawpaw-social-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.5 6.19a3.02 3.02 0 0 0-2.12-2.14C19.56 3.5 12 3.5 12 3.5s-7.56 0-9.38.55A3.02 3.02 0 0 0 .5 6.19C0 8.03 0 12 0 12s0 3.97.5 5.81a3.02 3.02 0 0 0 2.12 2.14C4.44 20.5 12 20.5 12 20.5s7.56 0 9.38-.55a3.02 3.02 0 0 0 2.12-2.14C24 15.97 24 12 24 12s0-3.97-.5-5.81zM9.75 15.52V8.48L15.5 12l-5.75 3.52z"/>
                    </svg>
                </a>

                {{-- Facebook --}}
                <a href="#" aria-label="Facebook" class="pawpaw-social-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073C24 5.406 18.627 0 12 0S0 5.406 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
                    </svg>
                </a>

            </div>
        </div>

        {{-- ── Column 2: Jelajahi --}}
        <div class="pawpaw-footer-col">
            <h4 class="pawpaw-footer-heading pawpaw-heading-green">Jelajahi</h4>
            <ul class="pawpaw-footer-links">
                <li><a href="{{ url('/') }}" class="pawpaw-footer-link">Beranda</a></li>
                <li><a href="#" class="pawpaw-footer-link">Cari Klinik</a></li>
                <li><a href="#" class="pawpaw-footer-link">Cari Layanan</a></li>
                <li><a href="#" class="pawpaw-footer-link">Artikel &amp; Tips</a></li>
                <li><a href="#" class="pawpaw-footer-link">Promo</a></li>
                <li><a href="{{ url('/') }}" class="pawpaw-footer-link">Beranda</a></li>
            </ul>
        </div>

        {{-- ── Column 3: Layanan --}}
        <div class="pawpaw-footer-col">
            <h4 class="pawpaw-footer-heading pawpaw-heading-green">Layanan</h4>
            <ul class="pawpaw-footer-links">
                <li><a href="#" class="pawpaw-footer-link">Reservasi Klinik</a></li>
                <li><a href="#" class="pawpaw-footer-link">Vaksinasi</a></li>
                <li><a href="#" class="pawpaw-footer-link">Grooming</a></li>
                <li><a href="#" class="pawpaw-footer-link">Rawat Inap</a></li>
            </ul>
        </div>

        {{-- ── Column 4: Cat Illustration --}}
        <div class="pawpaw-footer-illustration" aria-hidden="true">
            <img
                src="{{ asset('assets/footer-cat-illustration.webp') }}"
                alt="Hewan Sehat, Hidup Lebih Bahagia"
                class="pawpaw-footer-cat-img"
            >
        </div>

    </div>

    {{-- Copyright --}}
    <div class="pawpaw-footer-copyright">
        <p>@ {{ date('Y') }} PawPaw. All rights reserved.</p>
    </div>

</footer>

<style>
/* =============================================
   PAWPAW FOOTER STYLES
   ============================================= */
.pawpaw-footer {
    position: relative;
    background-color: #FFFAF3;
    overflow: hidden;
    padding-top: 48px; /* Dekat dari atas agar PawPaw, Jelajahi, Layanan posisi pas di atas */
    font-family: 'Inter', sans-serif;
}

/* Decorative blob – bottom left */
.pawpaw-footer-blob-left {
    position: absolute;
    bottom: -70px;   /* lebih turun ke bawah */
    left: -70px;
    width: 150px;    /* lebih kecil */
    height: 130px;
    background-color: #8ecab8;
    border-radius: 60% 40% 70% 30% / 50% 60% 40% 50%;
    opacity: 0.55;
    pointer-events: none;
    z-index: 1;
}

/* ─────────────────────────────────────────────
   GRID — MOBILE (default): 2 kolom Brand + Nav
───────────────────────────────────────────── */
.pawpaw-footer-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 28px 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-areas:
        "brand brand"
        "nav1  nav2";
    gap: 24px 32px;
    align-items: start;
}

/* ── Brand ── */
.pawpaw-footer-brand {
    display: flex;
    flex-direction: column;
}

.pawpaw-footer-logo-link {
    display: inline-block;
    margin-bottom: 14px;
    text-decoration: none;
    width: fit-content;
}

.pawpaw-footer-logo-img {
    height: 44px;
    width: auto;
    object-fit: contain;
}

.pawpaw-footer-desc {
    font-size: 0.875rem;
    color: #666;
    line-height: 1.7;
    margin-bottom: 20px;
    max-width: 250px;
}

/* Social icons — PAKSA 1 BARIS */
.pawpaw-footer-socials {
    display: flex;
    gap: 10px;
    flex-wrap: nowrap;
    align-items: center;
}

.pawpaw-social-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background-color: #e2eeea;
    border: 1.5px solid #bad4cc;
    color: #2a7a5c;
    text-decoration: none;
    transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    flex-shrink: 0;
}

.pawpaw-social-btn:hover {
    background-color: #2a7a5c;
    color: #fff;
    transform: scale(1.1);
}

/* ── Nav columns ── */
.pawpaw-footer-col {
    display: flex;
    flex-direction: column;
    align-self: start;
}

.pawpaw-footer-heading {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 16px;
    letter-spacing: 0.01em;
}

.pawpaw-heading-green { color: #1a7a52; }

.pawpaw-footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 11px;
}

.pawpaw-footer-link {
    font-size: 0.875rem;
    color: #555;
    text-decoration: none;
    transition: color 0.18s;
}

.pawpaw-footer-link:hover {
    color: #1a7a52;
}

/* ── Cat illustration — DISEMBUNYIKAN di mobile ── */
.pawpaw-footer-illustration {
    display: none; /* hidden di mobile */
}

.pawpaw-footer-cat-img {
    height: 230px;
    width: auto;
    object-fit: contain;
    display: block;
    flex-shrink: 0;
}

/* ── Copyright bar ── */
.pawpaw-footer-copyright {
    position: relative;
    z-index: 2;
    border-top: 1px solid #ddd6cc;
    margin-top: 160px; /* Tambah tinggi footer ekstra agar ruang atas (headroom) kucing sangat lega */
    padding: 14px 28px;
    text-align: center;
}

.pawpaw-footer-copyright p {
    font-size: 0.8rem;
    color: #999;
    margin: 0;
}

/* ─────────────────────────────────────────────
   MOBILE (default grid areas untuk 2-col)
───────────────────────────────────────────── */
.pawpaw-footer-brand            { grid-area: brand; position: relative; z-index: 2; }
.pawpaw-footer-col:nth-child(2) { grid-area: nav1;  position: relative; z-index: 2; } /* Jelajahi */
.pawpaw-footer-col:nth-child(3) { grid-area: nav2;  position: relative; z-index: 2; } /* Layanan  */

/* ─────────────────────────────────────────────
   DESKTOP (1024px)
───────────────────────────────────────────── */
@media (min-width: 1024px) {
    .pawpaw-footer-inner {
        position: relative;
        grid-template-columns: 1.4fr 0.85fr 0.85fr 1.6fr;
        grid-template-areas: "brand nav1 nav2 cat";
        grid-template-rows: auto;
        column-gap: 32px;
        row-gap: 0;
        align-items: start;
        padding-bottom: 0;
    }

    .pawpaw-footer-brand            { grid-area: brand; align-self: start; }
    .pawpaw-footer-col:nth-child(2) { grid-area: nav1;  align-self: start; } /* Jelajahi */
    .pawpaw-footer-col:nth-child(3) { grid-area: nav2;  align-self: start; } /* Layanan  */

    /* Illustration: Position Absolute bottom right, Layer 1, Jarak atas SANGAT LEGA */
    .pawpaw-footer-illustration {
        display: block;
        position: absolute;
        right: -130px;        /* Posisikan lebih ke kanan agar jelas terpotong di kanan */
        bottom: -150px;       /* Diturunkan agar memberi jarak atas (headroom) yang SANGAT LEGA */
        z-index: 1;           /* Di belakang teks */
        pointer-events: none;
    }

    .pawpaw-footer-cat-img {
        height: 385px;        /* Ukuran BESAR dipertahankan */
        width: auto;
        object-fit: contain;
        display: block;
    }

    .pawpaw-footer-blob-left {
        width: 160px;
        height: 140px;
        bottom: -80px;
    }
}

/* ─────────────────────────────────────────────
   WIDE DESKTOP (1280px+): makin besar & presisi
───────────────────────────────────────────── */
@media (min-width: 1280px) {
    .pawpaw-footer-copyright {
        margin-top: 180px;   /* Tambah tinggi footer lagi di layar lebar */
    }

    .pawpaw-footer-inner {
        grid-template-columns: 1.4fr 0.85fr 0.85fr 1.7fr;
        column-gap: 36px;
    }

    .pawpaw-footer-illustration {
        right: -150px;        /* Posisikan lebih ke kanan di screen lebar */
        bottom: -170px;       /* Jarak atas sangat lega & terpotong di kanan */
    }

    .pawpaw-footer-cat-img {
        height: 410px;        /* Ukuran BESAR dipertahankan */
    }
}
</style>
