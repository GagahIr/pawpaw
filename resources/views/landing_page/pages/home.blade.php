@extends('landing_page.layouts.app-landing')

@section('content')
    {{-- =========================================================
     HERO
    ========================================================= --}}
    <section class="relative overflow-hidden bg-[#FFF8F5]">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 lg:pt-24 lg:pb-28 grid lg:grid-cols-2 gap-12 items-center">

            <div class="z-20 w-full" data-animate="fade-up">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                    Urus si kesayangan,<br>
                    <span class="text-black-500">tanpa ribet.</span>
                </h1>
                <p class="mt-6 text-lg text-gray-600 max-w-lg">
                    PawPaw membantu kamu jadwalkan grooming, konsultasi dokter hewan, dan pengingat vaksin — semua dalam
                    satu aplikasi.
                </p>

                <div class="max-w-xl w-full mt-8">
                    <!-- SearchBox -->
                    <div class="relative"
                        data-hs-combo-box='{
                        "groupingType": "default",
                        "isOpenOnFocus": true,
                        "apiUrl": "{{ asset('assets/data/searchbox.json') }}",
                        "apiGroupField": "category",
                        "outputItemTemplate": "<div data-hs-combo-box-output-item class=\"rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100\"><span class=\"flex items-center cursor-pointer py-2 px-4 w-full text-sm text-gray-800 rounded-lg\"><div class=\"flex items-center w-full\"><div class=\"flex items-center justify-center rounded-full bg-gray-100 size-6 overflow-hidden me-2.5\"><img class=\"shrink-0\" data-hs-combo-box-output-item-attr=&#39;[{\"valueFrom\": \"image\", \"attr\": \"src\"}, {\"valueFrom\": \"name\", \"attr\": \"alt\"}]&#39; /></div><div data-hs-combo-box-output-item-field=\"name\" data-hs-combo-box-value></div><div class=\"hidden\" data-hs-combo-box-output-item-field=&#39;[\"name\", \"category\"]&#39; data-hs-combo-box-search-text></div></div><span class=\"hidden hs-combo-box-selected:block\"><svg class=\"shrink-0 size-3.5 text-amber-500\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"20 6 9 17 4 12\"></polyline></svg></span></span></div>",
                        "groupingTitleTemplate": "<div class=\"text-xs uppercase text-gray-400 m-3 mb-1\"></div>"}'>
                        <div class="relative">
                            <!-- Icon Search -->
                            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-5">
                                <img src="{{ asset('assets/img/searching.webp') }}" class="size-6 object-contain"
                                    alt="Search Icon">
                            </div>

                            <!-- Input Field -->
                            <input
                                class="py-3.5 ps-14 pe-6 block w-full bg-white border border-gray-100 rounded-full text-base text-gray-800 placeholder:text-gray-400 focus:outline-none focus:border-[#EE6D52] focus:ring-1 focus:ring-[#EE6D52] shadow-sm transition-all"
                                type="text" role="combobox" aria-expanded="false" placeholder="Cari klinik atau layanan"
                                value="" data-hs-combo-box-input="">
                            {{-- <div>
                                <a href="#"
                                    class="hidden md:inline-block py-3 px-6 text-sm font-medium rounded-full text-gray-700 hover:bg-gray-100 transition">
                                    Daftar Pelanggan
                                </a>
                                <a href="{{ route('vendor.register') }}"
                                    class="hidden sm:inline-flex py-2.5 px-5 md:py-3 md:px-6 justify-center items-center text-sm font-medium rounded-full border border-transparent bg-[#EE6D52] text-white hover:bg-[#d95b42] focus:outline-none focus:bg-[#d95b42] transition-colors shadow-xs">
                                    Daftar Mitra
                                </a>
                            </div> --}}
                        </div>

                        <!-- SearchBox Dropdown -->
                        <div class="absolute z-50 w-full bg-white border border-gray-100 rounded-2xl shadow-xl p-2 mt-2"
                            style="display: none;" data-hs-combo-box-output="">
                            <div class="max-h-72 rounded-b-xl overflow-hidden overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300"
                                data-hs-combo-box-output-items-wrapper=""></div>
                        </div>
                        <!-- End SearchBox Dropdown -->
                    </div>
                    <!-- End SearchBox -->
                </div>
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 mt-6 sm:hidden">
                    <a href="#"
                        class="flex-1 min-w-[140px] py-3 px-5 inline-flex justify-center items-center text-sm font-medium text-gray-700 bg-white hover:text-[#EE6D52] hover:bg-gray-50 border border-gray-200 rounded-full transition-all text-center">
                        Daftar Pelanggan
                    </a>
                    <a href="{{ route('vendor.register') }}"
                        class="flex-1 min-w-[140px] py-3 px-5 inline-flex justify-center items-center text-sm font-semibold rounded-full border border-transparent bg-[#EE6D52] text-white hover:bg-[#d95b42] focus:outline-none focus:bg-[#d95b42] transition-colors shadow-xs text-center">
                        Daftar Mitra
                    </a>
                </div>

            </div>

            <div class="relative z-10 hidden lg:flex justify-end" data-animate="fade-left" data-animate-delay="0.15">
                <img class="w-full max-w-lg lg:max-w-xl h-auto object-contain" 
                    src="{{ asset('assets/img/cat-dog.webp') }}">
            </div>
        </div>
    </section>

    {{-- =========================================================
     PILIHAN LAYANAN
    ========================================================= --}}
    <section id="services" class="pt-20 pb-20 lg:pt-24 lg:pb-28 bg-[#FFF8F5]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-left" data-animate="fade-up">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[#2C5E4E] tracking-tight">Pilihan layanan</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">
                <div data-animate="fade-up" data-animate-delay="0.1"
                    class="flex flex-col items-center text-center group cursor-pointer">
                    <div
                        class="size-32 sm:size-36 rounded-full bg-[#FDE4DB] flex items-center justify-center mb-4 shadow-md group-hover:scale-105 transition-transform duration-300">
                        <img class="size-16 object-contain" src="{{ asset('assets/img/stethoscope.webp') }}" alt="Grooming">
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Grooming</h3>
                </div>

                <div data-animate="fade-up" data-animate-delay="0.2"
                    class="flex flex-col items-center text-center group cursor-pointer">
                    <div
                        class="size-32 sm:size-36 rounded-full bg-[#D2EBE0] flex items-center justify-center mb-4 shadow-md group-hover:scale-105 transition-transform duration-300">
                        <img class="size-16 object-contain" src="{{ asset('assets/img/searching.webp') }}" alt="Pet Hotel">
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Pet Hotel</h3>
                </div>

                <div data-animate="fade-up" data-animate-delay="0.3"
                    class="flex flex-col items-center text-center group cursor-pointer">
                    <div
                        class="size-32 sm:size-36 rounded-full bg-[#FDF0CF] flex items-center justify-center mb-4 shadow-md group-hover:scale-105 transition-transform duration-300">
                        <img class="size-16 object-contain" src="{{ asset('assets/img/inject.webp') }}" alt="Klinik">
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Klinik</h3>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================
     TEMUKAN VET
    ========================================================= --}}
    <section id="vets" class="pt-20 pb-20 lg:pt-24 lg:pb-28 bg-[#FFF8F5]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-left" data-animate="fade-up">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[#2C5E4E] tracking-tight">Temukan Vet</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">

                @php
                    $vets = [
                        [
                            'title' => 'Klinik Sahabat Satwa',
                            'rating' => '4.9',
                            'reviews' => '120',
                            'distance' => '1.2 km',
                            'image' =>
                                'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&w=800&q=80',
                            'tags' => [
                                ['label' => 'Vaksin', 'bg' => 'bg-[#D2EBE0]', 'text' => 'text-[#2C5E4E]'],
                                ['label' => 'Grooming', 'bg' => 'bg-[#FDE4DB]', 'text' => 'text-[#EE6D52]'],
                                ['label' => '+1', 'bg' => 'bg-gray-200/80', 'text' => 'text-gray-600'],
                            ],
                        ],
                        [
                            'title' => 'Happy Paws Vet',
                            'rating' => '4.8',
                            'reviews' => '96',
                            'distance' => '1.2 km',
                            'image' =>
                                'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
                            'tags' => [
                                ['label' => 'Vaksin', 'bg' => 'bg-[#D2EBE0]', 'text' => 'text-[#2C5E4E]'],
                                ['label' => 'Rawat Inap', 'bg' => 'bg-[#FDF0CF]', 'text' => 'text-[#8C6D1F]'],
                                ['label' => '+1', 'bg' => 'bg-gray-200/80', 'text' => 'text-gray-600'],
                            ],
                        ],
                        [
                            'title' => 'Rumah Hewan',
                            'rating' => '4.9',
                            'reviews' => '85',
                            'distance' => '1.2 km',
                            'image' =>
                                'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?auto=format&fit=crop&w=800&q=80',
                            'tags' => [
                                ['label' => 'Konsultasi', 'bg' => 'bg-[#F8E0EC]', 'text' => 'text-[#9C3D74]'],
                                ['label' => 'Grooming', 'bg' => 'bg-[#FDE4DB]', 'text' => 'text-[#EE6D52]'],
                                ['label' => '+1', 'bg' => 'bg-gray-200/80', 'text' => 'text-gray-600'],
                            ],
                        ],
                    ];
                @endphp

                @foreach ($vets as $i => $vet)
                    <!-- Card -->
                    <a data-animate="fade-up" data-animate-delay="{{ ($i + 1) * 0.1 }}"
                        class="group flex flex-col bg-white rounded-t-[75px] sm:rounded-t-[90px] rounded-b-[32px] overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 focus:outline-none"
                        href="#">
                        <div class="overflow-hidden">
                            <img class="w-full h-56 sm:h-64 object-cover group-hover:scale-105 transition-transform duration-500"
                                src="{{ $vet['image'] }}" alt="{{ $vet['title'] }}">
                        </div>
                        <div class="p-6 flex flex-col flex-grow justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 group-hover:text-[#2C5E4E] transition-colors">
                                    {{ $vet['title'] }}
                                </h3>

                                <div class="mt-3 flex items-center gap-4 text-sm">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="size-4 text-amber-400 fill-amber-400 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                        <span class="font-bold text-gray-900">{{ $vet['rating'] }}</span>
                                        <span class="text-gray-400">({{ $vet['reviews'] }})</span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <svg class="size-4 text-[#C86D51] shrink-0" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-gray-400 font-medium">{{ $vet['distance'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-2">
                                @foreach ($vet['tags'] as $tag)
                                    <span
                                        class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-semibold {{ $tag['bg'] }} {{ $tag['text'] }}">
                                        {{ $tag['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </a>
                    <!-- End Card -->
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================================================
     HOW TO USE — "Cari klinik, pilih jadwal, lalu datang."
    ========================================================= --}}
    <section class="pawpaw-howtouse-section">

        {{-- Top cream wave over green --}}
        <div class="pawpaw-howtouse-wave-top" aria-hidden="true">
            <svg viewBox="0 0 1440 54" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,0 L1440,0 L1440,20 Q1320,54 1080,28 Q840,4 720,28 Q600,52 360,28 Q180,8 0,28 Z"
                    fill="#FFF9F2" />
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
                        <img src="{{ asset('assets/map.webp') }}" alt="Maps icon" class="pawpaw-step-icon">
                    </div>
                    <p class="pawpaw-step-desc">Jelajahi klinik dan layanan yang sesuai di sekitarmu.</p>
                </div>

                {{-- Arrow --}}
                <div class="pawpaw-step-arrow" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 12H18.5M13.5 6.5L19 12L13.5 17.5" stroke="#e07a5f" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                {{-- Step 02 --}}
                <div class="pawpaw-howtouse-step">
                    <div class="pawpaw-step-header">
                        <span class="pawpaw-step-num">02</span>
                        <img src="{{ asset('assets/calendar.webp') }}" alt="Calendar icon" class="pawpaw-step-icon">
                    </div>
                    <p class="pawpaw-step-desc">Tentukan waktu kunjungan yang paling pas.</p>
                </div>

                {{-- Arrow --}}
                <div class="pawpaw-step-arrow" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 12H18.5M13.5 6.5L19 12L13.5 17.5" stroke="#e07a5f" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>

                {{-- Step 03 --}}
                <div class="pawpaw-howtouse-step">
                    <div class="pawpaw-step-header">
                        <span class="pawpaw-step-num">03</span>
                        <img src="{{ asset('assets/list.webp') }}" alt="List icon" class="pawpaw-step-icon">
                    </div>
                    <p class="pawpaw-step-desc">Konfirmasi pilihanmu, lalu datang sesuai jadwal.</p>
                </div>

            </div>
        </div>

        {{-- Bottom cream wave over green --}}
        <div class="pawpaw-howtouse-wave-bottom" aria-hidden="true">
            <svg viewBox="0 0 1440 54" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,54 L1440,54 L1440,34 Q1260,0 1080,26 Q840,52 720,26 Q540,0 360,26 Q180,50 0,26 Z"
                    fill="#FFF9F2" />
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
                <img src="{{ asset('assets/cat-sleep-illustration.webp') }}" alt="Kucing tidur manis"
                    class="pawpaw-whyus-cat-img">
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
                            <img src="{{ asset('assets/pawkucing.webp') }}" alt="Paw icon" class="pawpaw-whyus-icon">
                        </span>
                        <span class="pawpaw-whyus-text">Temukan klinik secara cepat</span>
                    </li>
                    <li class="pawpaw-whyus-item">
                        <span class="pawpaw-whyus-icon-wrap">
                            <img src="{{ asset('assets/tag.webp') }}" alt="Tag icon" class="pawpaw-whyus-icon">
                        </span>
                        <span class="pawpaw-whyus-text">Harga jelas dan terjangkau</span>
                    </li>
                    <li class="pawpaw-whyus-item">
                        <span class="pawpaw-whyus-icon-wrap">
                            <img src="{{ asset('assets/calendar.webp') }}" alt="Calendar icon"
                                class="pawpaw-whyus-icon">
                        </span>
                        <span class="pawpaw-whyus-text">Reservasi lebih praktis</span>
                    </li>
                    <li class="pawpaw-whyus-item">
                        <span class="pawpaw-whyus-icon-wrap">
                            <img src="{{ asset('assets/heart.webp') }}" alt="Heart icon" class="pawpaw-whyus-icon">
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
@endsection
<style>
    /* =============================================
               SECTION 1: HOW TO USE (wave green band)
               ============================================= */
    .pawpaw-howtouse-section {
        background-color: #c8dfd8;
        /* sage green from design */
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
            height: 44px;
            /* Exactly matches height of step header row */
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
            height: 52px;
            /* Matches desktop step header height */
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
        display: block;
        /* "memilih kami?" on its own line */
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
        background-color: #faf6f0;
        /* Cream page background */
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
        background-color: #f6a88a;
        /* Peach card color from reference */
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
        color: #bf3c29;
        /* Rust red color */
        font-style: italic;
        /* Italic style like reference image */
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

{{-- @push('styles')
    <style>
        /* Tempel seluruh CSS .pawpaw-* (howtouse, whyus, goodcare) di sini.
           Pastikan layouts/app-landing.blade.php punya @stack('styles') di <head>. */
    </style>
@endpush --}}
