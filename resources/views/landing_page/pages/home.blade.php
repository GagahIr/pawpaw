@extends('landing_page.layouts.app-landing')

@section('content')
    {{-- =========================================================
     HOW TO USE — "Cari klinik, pilih jadwal, lalu datang."
    ========================================================= --}}
    <section class="pawpaw-howtouse-section">
        {{-- Top cream wave over green --}}
        <div class="pawpaw-howtouse-wave-top" aria-hidden="true">
            <svg viewBox="0 0 1440 54" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,0 L1440,0 L1440,20 Q1320,54 1080,28 Q840,4 720,28 Q600,52 360,28 Q180,8 0,28 Z" fill="#FFF9F2"/>
            </svg>
        </div>

        <div class="pawpaw-howtouse-inner">

            {{-- Left: Tagline --}}
            <div class="pawpaw-howtouse-tagline">
                Cari klinik,<br>pilih jadwal,<br>lalu datang.
            </div>

            {{-- Right: Steps --}}
            <div class="pawpaw-howtouse-steps">

                {{-- Step 01 --}}
                <div class="pawpaw-howtouse-step">
                    <div class="pawpaw-step-header">
                        <span class="pawpaw-step-num">01</span>
                        <img src="{{ asset('assets/maps.png') }}" alt="Maps icon" class="pawpaw-step-icon">
                    </div>
                    <p class="pawpaw-step-desc">Jelajahi klinik dan layanan yang sesuai di sekitarmu.</p>
                </div>

                {{-- Arrow --}}
                <div class="pawpaw-step-arrow" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 12H18.5M13.5 6.5L19 12L13.5 17.5" stroke="#e07a5f" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                {{-- Step 02 --}}
                <div class="pawpaw-howtouse-step">
                    <div class="pawpaw-step-header">
                        <span class="pawpaw-step-num">02</span>
                        <img src="{{ asset('assets/calendar.png') }}" alt="Calendar icon" class="pawpaw-step-icon">
                    </div>
                    <p class="pawpaw-step-desc">Tentukan waktu kunjungan yang paling pas.</p>
                </div>

                {{-- Arrow --}}
                <div class="pawpaw-step-arrow" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 12H18.5M13.5 6.5L19 12L13.5 17.5" stroke="#e07a5f" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                {{-- Step 03 --}}
                <div class="pawpaw-howtouse-step">
                    <div class="pawpaw-step-header">
                        <span class="pawpaw-step-num">03</span>
                        <img src="{{ asset('assets/list.png') }}" alt="List icon" class="pawpaw-step-icon">
                    </div>
                    <p class="pawpaw-step-desc">Konfirmasi pilihanmu, lalu datang sesuai jadwal.</p>
                </div>

            </div>
        </div>

        {{-- Bottom cream wave over green --}}
        <div class="pawpaw-howtouse-wave-bottom" aria-hidden="true">
            <svg viewBox="0 0 1440 54" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,54 L1440,54 L1440,34 Q1260,0 1080,26 Q840,52 720,26 Q540,0 360,26 Q180,50 0,26 Z" fill="#FFF9F2"/>
            </svg>
        </div>
    </section>

    {{-- =========================================================
     WHY CHOOSE US — "Mengapa harus memilih kami?"
    ========================================================= --}}
    <section class="pawpaw-whyus-section">
        <div class="pawpaw-whyus-inner">

            {{-- Left: Cat illustration --}}
            <div class="pawpaw-whyus-illustration" aria-hidden="true">
                <img src="{{ asset('assets/cat-sleep-illustration.png') }}" alt="Kucing tidur manis" class="pawpaw-whyus-cat-img">
            </div>

            {{-- Right: Content --}}
            <div class="pawpaw-whyus-content">
                <h2 class="pawpaw-whyus-title">
                    Mengapa harus
                    <span class="pawpaw-whyus-title-accent">memilih kami?</span>
                </h2>

                <ul class="pawpaw-whyus-list">
                    <li class="pawpaw-whyus-item">
                        <span class="pawpaw-whyus-icon-wrap">
                            <img src="{{ asset('assets/pawkucing.png') }}" alt="Paw icon" class="pawpaw-whyus-icon">
                        </span>
                        <span class="pawpaw-whyus-text">Temukan klinik secara cepat</span>
                    </li>
                    <li class="pawpaw-whyus-item">
                        <span class="pawpaw-whyus-icon-wrap">
                            <img src="{{ asset('assets/tag.png') }}" alt="Tag icon" class="pawpaw-whyus-icon">
                        </span>
                        <span class="pawpaw-whyus-text">Harga jelas dan terjangkau</span>
                    </li>
                    <li class="pawpaw-whyus-item">
                        <span class="pawpaw-whyus-icon-wrap">
                            <img src="{{ asset('assets/calendar.png') }}" alt="Calendar icon" class="pawpaw-whyus-icon">
                        </span>
                        <span class="pawpaw-whyus-text">Reservasi lebih praktis</span>
                    </li>
                    <li class="pawpaw-whyus-item">
                        <span class="pawpaw-whyus-icon-wrap">
                            <img src="{{ asset('assets/heart.png') }}" alt="Heart icon" class="pawpaw-whyus-icon">
                        </span>
                        <span class="pawpaw-whyus-text">Berbagai layanan dan produk hewan</span>
                    </li>
                </ul>
            </div>

        </div>
    </section>

    {{-- =========================================================
     GOOD CARE CTA — "Good care. Better together."
    ========================================================= --}}
    <section class="pawpaw-goodcare-section">
        <div class="pawpaw-goodcare-inner">
            <div class="pawpaw-goodcare-card">
                <h2 class="pawpaw-goodcare-title">
                    Good care.<br>
                    <span class="pawpaw-goodcare-accent">Better together.</span>
                </h2>
            </div>
        </div>
    </section>

    <style>
    /* =============================================
       SECTION 1: HOW TO USE (wave green band)
       ============================================= */
    .pawpaw-howtouse-section {
        background-color: #c8dfd8;  /* sage green from design */
        position: relative;
        font-family: 'Inter', sans-serif;
    }

    /* Top & bottom cream-wave overlays */
    .pawpaw-howtouse-wave-top,
    .pawpaw-howtouse-wave-bottom {
        position: relative;
        line-height: 0;
        display: block;
        width: 100%;
    }

    .pawpaw-howtouse-wave-top svg,
    .pawpaw-howtouse-wave-bottom svg {
        width: 100%;
        height: 54px;
        display: block;
    }

    /* Main content row */
    .pawpaw-howtouse-inner {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 36px 24px;
        display: flex;
        flex-direction: column;
        gap: 32px;
        align-items: stretch;
    }

    /* Left: bold chunky tagline — Fredoka font */
    .pawpaw-howtouse-tagline {
        font-family: 'Fredoka', 'Nunito', sans-serif;
        font-weight: 700;
        font-size: 1.85rem;
        color: #1a1a1a;
        line-height: 1.25;
        flex-shrink: 0;
        width: 100%;
    }

    /* Steps row */
    .pawpaw-howtouse-steps {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
    }

    /* Arrow between steps — hidden on mobile */
    .pawpaw-step-arrow {
        display: none;
        flex-shrink: 0;
    }

    .pawpaw-howtouse-step {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
        padding-bottom: 20px;
        border-bottom: 1px dashed rgba(26, 92, 64, 0.2);
    }

    .pawpaw-howtouse-step:last-child {
        padding-bottom: 0;
        border-bottom: none;
    }

    /* Number + icon on same row */
    .pawpaw-step-header {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Big coral numbers like the design */
    .pawpaw-step-num {
        font-family: 'Nunito', sans-serif;
        font-size: 3rem;
        font-weight: 900;
        color: #e07a5f;
        line-height: 1;
        letter-spacing: -0.03em;
    }

    /* Bigger icons */
    .pawpaw-step-icon {
        width: 44px;
        height: 44px;
        object-fit: contain;
    }

    /* Description text */
    .pawpaw-step-desc {
        font-size: 0.95rem;
        color: #2e2e2e;
        line-height: 1.5;
        margin: 0;
        max-width: 100%;
    }

    /* ── Tablet (768px+): switch to horizontal layout ── */
    @media (min-width: 768px) {
        .pawpaw-howtouse-inner {
            flex-direction: row;
            align-items: center;
            gap: 40px;
            padding: 24px 48px;
        }

        .pawpaw-howtouse-tagline {
            font-size: 2rem;
            min-width: 210px;
            width: auto;
        }

        .pawpaw-howtouse-steps {
            flex-direction: row;
            align-items: flex-start;
            gap: 0;
            flex: 1;
        }

        .pawpaw-step-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 44px;  /* Exactly matches height of step header row */
            margin: 0 10px;
            padding: 0;
        }

        .pawpaw-step-arrow svg {
            width: 24px;
            height: 24px;
            display: block;
        }

        .pawpaw-howtouse-step {
            flex: 1;
            min-width: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .pawpaw-step-num {
            font-size: 2.8rem;
        }

        .pawpaw-step-icon {
            width: 38px;
            height: 38px;
        }

        .pawpaw-step-desc {
            max-width: none;
            font-size: 0.85rem;
        }
    }

    /* ── Desktop (1024px+) ── */
    @media (min-width: 1024px) {
        .pawpaw-howtouse-inner {
            padding: 24px 64px;
            gap: 56px;
        }

        .pawpaw-howtouse-tagline {
            font-size: 2.2rem;
            min-width: 240px;
        }

        .pawpaw-step-num {
            font-size: 3.2rem;
        }

        .pawpaw-step-icon {
            width: 44px;
            height: 44px;
        }

        .pawpaw-step-desc {
            font-size: 0.875rem;
        }

        .pawpaw-step-arrow {
            height: 52px;  /* Matches desktop step header height */
            margin: 0 14px;
        }

        .pawpaw-step-arrow svg {
            width: 28px;
            height: 28px;
        }
    }

    /* =============================================
       SECTION 2: WHY CHOOSE US
       ============================================= */
    .pawpaw-whyus-section {
        background-color: #faf6f0;
        padding: 72px 0;
        font-family: 'Inter', sans-serif;
    }

    .pawpaw-whyus-inner {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    /* Left: cat illustration — extra large & aligned closer to content */
    .pawpaw-whyus-illustration {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    .pawpaw-whyus-cat-img {
        width: 100%;
        max-width: 500px;
        height: auto;
        object-fit: contain;
        display: block;
    }

    /* Right: content */
    .pawpaw-whyus-content {
        width: 100%;
    }

    /* Big chunky Fredoka title — matches reference design */
    .pawpaw-whyus-title {
        font-family: 'Fredoka', 'Nunito', sans-serif;
        font-size: 2.6rem;
        font-weight: 700;
        color: #1a5c40;
        line-height: 1.15;
        margin: 0 0 24px;
    }

    .pawpaw-whyus-title-accent {
        color: #e07a5f;
        display: block;  /* "memilih kami?" on its own line */
    }

    /* Feature list — bounded max-width so border lines don't stretch too far right */
    .pawpaw-whyus-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        max-width: 440px;
        width: 100%;
    }

    .pawpaw-whyus-item {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 20px 0;
        border-bottom: 1.5px solid #ddd5c8;
    }

    .pawpaw-whyus-item:first-child {
        border-top: 1.5px solid #ddd5c8;
    }

    .pawpaw-whyus-icon-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
    }

    .pawpaw-whyus-icon {
        width: 36px;
        height: 36px;
        object-fit: contain;
    }

    /* Bold text like the reference */
    .pawpaw-whyus-text {
        font-size: 1rem;
        font-weight: 700;
        color: #1a1a1a;
        line-height: 1.4;
    }

    /* ── Tablet (768px+) ── */
    @media (min-width: 768px) {
        .pawpaw-whyus-section {
            padding: 80px 0;
        }

        .pawpaw-whyus-inner {
            flex-direction: row;
            align-items: center;
            gap: 16px;
        }

        .pawpaw-whyus-illustration {
            flex-shrink: 0;
            width: 50%;
            justify-content: flex-end;
        }

        .pawpaw-whyus-cat-img {
            max-width: 650px;
        }

        .pawpaw-whyus-content {
            flex: 1;
            padding-left: 0;
        }

        .pawpaw-whyus-title {
            font-size: 2.8rem;
        }
    }

    /* ── Desktop (1024px+) ── */
    @media (min-width: 1024px) {
        .pawpaw-whyus-section {
            padding: 96px 0;
        }

        .pawpaw-whyus-inner {
            max-width: 1360px;
            padding: 0 48px;
            gap: 16px;
        }

        .pawpaw-whyus-illustration {
            width: 52%;
            max-width: none;
            justify-content: flex-end;
        }

        .pawpaw-whyus-cat-img {
            max-width: 800px;
            width: 100%;
        }

        .pawpaw-whyus-content {
            flex: 1;
            padding-left: 0;
        }

        .pawpaw-whyus-title {
            font-size: 3.2rem;
            margin-bottom: 28px;
        }

        .pawpaw-whyus-icon {
            width: 38px;
            height: 38px;
        }

        .pawpaw-whyus-text {
            font-size: 1.05rem;
        }

        .pawpaw-whyus-list {
            max-width: 480px;
        }

        .pawpaw-whyus-item {
            padding: 22px 0;
        }
    }

    /* ── Wide (1280px+) ── */
    @media (min-width: 1280px) {
        .pawpaw-whyus-inner {
            gap: 20px;
        }

        .pawpaw-whyus-cat-img {
            max-width: 900px;
        }

        .pawpaw-whyus-title {
            font-size: 3.5rem;
        }
    }

    /* =============================================
       SECTION 3: GOOD CARE CTA CARD
       ============================================= */
    .pawpaw-goodcare-section {
        background-color: #faf6f0;   /* Cream page background */
        width: 100%;
        padding: 24px 0 64px;
        font-family: 'Nunito', sans-serif;
    }

    .pawpaw-goodcare-inner {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 24px;
    }

    /* Rounded peach card inside cream background */
    .pawpaw-goodcare-card {
        background-color: #f6a88a;   /* Peach card color from reference */
        border-radius: 28px;
        padding: 48px 36px;
        width: 100%;
    }

    .pawpaw-goodcare-title {
        font-family: 'Nunito', sans-serif;
        font-size: 2.5rem;
        font-weight: 900;
        color: #181818;
        line-height: 1.18;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .pawpaw-goodcare-accent {
        color: #bf3c29;       /* Rust red color */
        font-style: italic;   /* Italic style like reference image */
        font-weight: 900;
        display: inline-block;
    }

    @media (min-width: 768px) {
        .pawpaw-goodcare-section {
            padding: 32px 0 80px;
        }

        .pawpaw-goodcare-inner {
            padding: 0 48px;
        }

        .pawpaw-goodcare-card {
            border-radius: 32px;
            padding: 64px 56px;
        }

        .pawpaw-goodcare-title {
            font-size: 3.2rem;
        }
    }

    @media (min-width: 1024px) {
        .pawpaw-goodcare-section {
            padding: 40px 0 96px;
        }

        .pawpaw-goodcare-inner {
            padding: 0 64px;
        }

        .pawpaw-goodcare-card {
            border-radius: 36px;
            padding: 80px 72px;
        }

        .pawpaw-goodcare-title {
            font-size: 3.8rem;
        }
    }

    @media (min-width: 1280px) {
        .pawpaw-goodcare-card {
            padding: 88px 80px;
        }

        .pawpaw-goodcare-title {
            font-size: 4.2rem;
        }
    }
    </style>

@endsection

