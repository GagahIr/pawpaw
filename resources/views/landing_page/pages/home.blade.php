@extends('landing_page.layouts.app-landing')

@section('content')

    {{-- =========================================================
     HERO
========================================================= --}}
    <section class="relative overflow-hidden bg-[#FFF8F5]">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 lg:pt-24 lg:pb-28 grid lg:grid-cols-2 gap-12 items-center">

            <div class="z-20 lg:-mr-16" data-animate="fade-up">
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
                    <div class="relative" data-hs-combo-box='{
                        "groupingType": "default",
                        "isOpenOnFocus": true,
                        "apiUrl": "../../assets/data/searchbox.json",
                        "apiGroupField": "category",
                        "outputItemTemplate": "<div data-hs-combo-box-output-item class=\"rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100\"><span class=\"flex items-center cursor-pointer py-2 px-4 w-full text-sm text-gray-800 rounded-lg\"><div class=\"flex items-center w-full\"><div class=\"flex items-center justify-center rounded-full bg-gray-100 size-6 overflow-hidden me-2.5\"><img class=\"shrink-0\" data-hs-combo-box-output-item-attr=&#39;[{\"valueFrom\": \"image\", \"attr\": \"src\"}, {\"valueFrom\": \"name\", \"attr\": \"alt\"}]&#39; /></div><div data-hs-combo-box-output-item-field=\"name\" data-hs-combo-box-value></div><div class=\"hidden\" data-hs-combo-box-output-item-field=&#39;[\"name\", \"category\"]&#39; data-hs-combo-box-search-text></div></div><span class=\"hidden hs-combo-box-selected:block\"><svg class=\"shrink-0 size-3.5 text-amber-500\" xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"20 6 9 17 4 12\"></polyline></svg></span></span></div>",
                        "groupingTitleTemplate": "<div class=\"text-xs uppercase text-gray-400 m-3 mb-1\"></div>"}'>
                        <div class="relative">
                        <!-- Icon Search -->
                        <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-5">
                            <img src="{{ asset('../../assets/img/searching.png') }}" class="size-6 object-contain" alt="Search Icon">
                        </div>

                        <!-- Input Field -->
                        <input class="py-3.5 ps-14 pe-6 block w-full bg-white border border-gray-100 rounded-full text-base text-gray-800 placeholder:text-gray-400 focus:outline-none focus:border-[#EE6D52] focus:ring-1 focus:ring-[#EE6D52] shadow-sm transition-all" 
                                type="text" 
                                role="combobox" 
                                aria-expanded="false" 
                                placeholder="Cari klinik atau layanan" 
                                value="" 
                                data-hs-combo-box-input="">
                        </div>

                        <!-- SearchBox Dropdown -->
                        <div class="absolute z-50 w-full bg-white border border-gray-100 rounded-2xl shadow-xl p-2 mt-2" style="display: none;" data-hs-combo-box-output="">
                        <div class="max-h-72 rounded-b-xl overflow-hidden overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300" data-hs-combo-box-output-items-wrapper=""></div>
                        </div>
                        <!-- End SearchBox Dropdown -->
                    </div>
                    <!-- End SearchBox -->
                </div>

            </div>

            <div class="relative z-10 flex justify-center lg:justify-end" data-animate="fade-left" data-animate-delay="0.15">
                <img class="w-full max-w-lg lg:max-w-xl h-auto object-contain" 
                    src="{{ asset('assets/img/cat-dog-blob.png') }}">
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
                <div data-animate="fade-up" data-animate-delay="0.1" class="flex flex-col items-center text-center group cursor-pointer">
                    <div class="size-32 sm:size-36 rounded-full bg-[#FDE4DB] flex items-center justify-center mb-4 shadow-md group-hover:scale-105 transition-transform duration-300">
                        <img class="size-16 object-contain" 
                            src="{{ asset('assets/img/Codex Image Sep 24, 2026, 01_00_43 PM 2.png') }}">
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Grooming</h3>
                </div>

                <div data-animate="fade-up" data-animate-delay="0.2" class="flex flex-col items-center text-center group cursor-pointer">
                    <div class="size-32 sm:size-36 rounded-full bg-[#D2EBE0] flex items-center justify-center mb-4 shadow-md group-hover:scale-105 transition-transform duration-300">
                        <img class="size-16 object-contain" 
                            src="{{ asset('assets/img/Codex Image Sep 24, 2026, 01_00_43 PM 2.png') }}">
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Pet Hotel</h3>
                </div>

                <div data-animate="fade-up" data-animate-delay="0.3" class="flex flex-col items-center text-center group cursor-pointer">
                    <div class="size-32 sm:size-36 rounded-full bg-[#FDF0CF] flex items-center justify-center mb-4 shadow-md group-hover:scale-105 transition-transform duration-300">
                        <img class="size-16 object-contain" 
                            src="{{ asset('assets/img/Codex Image Sep 24, 2026, 01_00_43 PM 2.png') }}">
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Klinik</h3>
                </div>
            </div>
        </div>
    </section>

    
    {{-- =========================================================
     TEMUKAN VET
========================================================= --}}
    <section id="services" class="pt-20 pb-20 lg:pt-24 lg:pb-28 bg-[#FFF8F5]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-left" data-animate="fade-up">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-[#2C5E4E] tracking-tight">Temukan Vet</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">

                <!-- Card -->
                <a data-animate="fade-up" data-animate-delay="0.1" class="flex flex-col bg-card border border-card-line shadow-2xs rounded-xl hover:shadow-lg focus:outline-hidden focus:shadow-lg transition" href="#">
                <img class="w-full h-auto rounded-t-xl" src="https://images.unsplash.com/photo-1680868543815-b8666dba60f7?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=320&q=80" alt="Card Image">
                <div class="p-4">
                    <h3 class="font-semibold text-foreground">
                    Card title
                    </h3>
                    <p class="mt-1 text-muted-foreground-1">
                    Some quick example text to build on the card title and make up the bulk of the card's content.
                    </p>
                </div>
                </a>
                <!-- End Card -->

                <!-- Card -->
                <a data-animate="fade-up" data-animate-delay="0.2" class="flex flex-col bg-card border border-card-line shadow-2xs rounded-xl hover:shadow-lg focus:outline-hidden focus:shadow-lg transition" href="#">
                <img class="w-full h-auto rounded-t-xl" src="https://images.unsplash.com/photo-1680868543815-b8666dba60f7?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=320&q=80" alt="Card Image">
                <div class="p-4">
                    <h3 class="font-semibold text-foreground">
                    Card title
                    </h3>
                    <p class="mt-1 text-muted-foreground-1">
                    Some quick example text to build on the card title and make up the bulk of the card's content.
                    </p>
                </div>
                </a>
                <!-- End Card -->

                <!-- Card -->
                <a data-animate="fade-up" data-animate-delay="0.3" class="flex flex-col bg-card border border-card-line shadow-2xs rounded-xl hover:shadow-lg focus:outline-hidden focus:shadow-lg transition" href="#">
                <img class="w-full h-auto rounded-t-xl" src="https://images.unsplash.com/photo-1680868543815-b8666dba60f7?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=320&q=80" alt="Card Image">
                <div class="p-4">
                    <h3 class="font-semibold text-foreground">
                    Card title
                    </h3>
                    <p class="mt-1 text-muted-foreground-1">
                    Some quick example text to build on the card title and make up the bulk of the card's content.
                    </p>
                </div>
                </a>
                <!-- End Card -->
            </div>
        </div>
    </section>

    {{-- =========================================================
     LOGO STRIP
========================================================= --}}
    <section class="border-y border-gray-100 py-8 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-xs font-semibold tracking-wider text-gray-400 uppercase mb-6">
                Dipercaya oleh klinik & pet shop terkemuka
            </p>
            <div class="flex flex-wrap justify-center items-center gap-x-12 gap-y-4 opacity-60 grayscale">
                <span class="text-lg font-bold">VetCare</span>
                <span class="text-lg font-bold">PetLovers</span>
                <span class="text-lg font-bold">Doggo&Co</span>
                <span class="text-lg font-bold">MeowKlinik</span>
                <span class="text-lg font-bold">FurryFriends</span>
            </div>
        </div>
    </section>

    {{-- =========================================================
     FEATURES
========================================================= --}}
    <section id="features" class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16" data-animate="fade-up">
                <span class="text-amber-600 font-semibold text-sm">FITUR UTAMA</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-gray-900">Semua kebutuhan hewan peliharaanmu, dalam satu
                    tempat</h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $features = [
                        [
                            'icon' => '📅',
                            'title' => 'Booking Instan',
                            'desc' => 'Jadwalkan grooming atau konsultasi dokter hewan dalam hitungan detik.',
                        ],
                        [
                            'icon' => '💉',
                            'title' => 'Pengingat Vaksin',
                            'desc' => 'Nggak perlu khawatir lupa jadwal vaksin dan checkup rutin lagi.',
                        ],
                        [
                            'icon' => '💬',
                            'title' => 'Konsultasi Chat',
                            'desc' => 'Tanya langsung ke dokter hewan berlisensi lewat chat, kapan saja.',
                        ],
                        [
                            'icon' => '🏥',
                            'title' => 'Rekam Medis Digital',
                            'desc' => 'Riwayat kesehatan hewan tersimpan rapi dan bisa diakses kapan pun.',
                        ],
                        [
                            'icon' => '🛍️',
                            'title' => 'Marketplace Pet Shop',
                            'desc' => 'Belanja kebutuhan hewan dari mitra pet shop terpercaya.',
                        ],
                        [
                            'icon' => '📍',
                            'title' => 'Cari Klinik Terdekat',
                            'desc' => 'Temukan klinik dan groomer terdekat lengkap dengan rating.',
                        ],
                    ];
                @endphp

                @foreach ($features as $i => $f)
                    <div data-animate="fade-up" data-animate-delay="{{ $i * 0.08 }}"
                        class="p-6 rounded-2xl border border-gray-100 hover:border-amber-200 hover:shadow-lg transition-all duration-300 bg-white">
                        <div class="size-12 rounded-xl bg-amber-50 flex items-center justify-center text-2xl mb-4">
                            {{ $f['icon'] }}
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================================================
     HOW IT WORKS
========================================================= --}}
    <section id="how-it-works" class="py-20 lg:py-28 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16" data-animate="fade-up">
                <span class="text-amber-600 font-semibold text-sm">CARA KERJA</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-gray-900">Mulai dalam 3 langkah mudah</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative">
                @foreach ([['no' => '01', 'title' => 'Daftar Akun', 'desc' => 'Buat profil untuk kamu dan hewan peliharaanmu, gratis.'], ['no' => '02', 'title' => 'Pilih Layanan', 'desc' => 'Cari klinik, groomer, atau dokter hewan sesuai kebutuhan.'], ['no' => '03', 'title' => 'Booking & Selesai', 'desc' => 'Konfirmasi jadwal, dan tim PawPaw yang urus sisanya.']] as $i => $step)
                    <div data-animate="fade-up" data-animate-delay="{{ $i * 0.1 }}"
                        class="relative bg-white p-8 rounded-2xl border border-gray-100 text-center">
                        <span class="text-5xl font-bold text-amber-100">{{ $step['no'] }}</span>
                        <h3 class="mt-2 text-lg font-semibold text-gray-900">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================================================
     TESTIMONIALS
========================================================= --}}
    <section id="testimonials" class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16" data-animate="fade-up">
                <span class="text-amber-600 font-semibold text-sm">TESTIMONI</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-gray-900">Kata mereka soal PawPaw</h2>
            </div>

            {{-- Tab nav ala Preline --}}
            <div class="max-w-3xl mx-auto" data-hs-tabs>
                <div class="flex justify-center gap-2 mb-8" role="tablist">
                    <button type="button"
                        class="hs-tab-active:bg-amber-500 hs-tab-active:text-white py-2 px-4 text-sm font-medium rounded-full bg-gray-100 text-gray-600 active"
                        data-hs-tab="#tab-1" aria-controls="tab-1" role="tab">Pemilik Kucing</button>
                    <button type="button"
                        class="hs-tab-active:bg-amber-500 hs-tab-active:text-white py-2 px-4 text-sm font-medium rounded-full bg-gray-100 text-gray-600"
                        data-hs-tab="#tab-2" aria-controls="tab-2" role="tab">Pemilik Anjing</button>
                </div>

                <div id="tab-1" role="tabpanel" data-animate="fade-up">
                    <blockquote class="text-center">
                        <p class="text-xl text-gray-800 font-medium leading-relaxed">
                            "Sejak pakai PawPaw, jadwal vaksin Milo nggak pernah kelewat lagi. Fitur pengingatnya beneran
                            menyelamatkan!"
                        </p>
                        <footer class="mt-6 flex items-center justify-center gap-3">
                            <img src="https://i.pravatar.cc/64?img=47" class="size-10 rounded-full" alt="">
                            <div class="text-left">
                                <div class="text-sm font-semibold text-gray-900">Nadia Putri</div>
                                <div class="text-xs text-gray-500">Pemilik kucing, Malang</div>
                            </div>
                        </footer>
                    </blockquote>
                </div>

                <div id="tab-2" class="hidden" role="tabpanel">
                    <blockquote class="text-center">
                        <p class="text-xl text-gray-800 font-medium leading-relaxed">
                            "Booking groomer buat Rocky sekarang tinggal 3 kali tap. Nggak perlu telepon-telepon klinik
                            lagi."
                        </p>
                        <footer class="mt-6 flex items-center justify-center gap-3">
                            <img src="https://i.pravatar.cc/64?img=15" class="size-10 rounded-full" alt="">
                            <div class="text-left">
                                <div class="text-sm font-semibold text-gray-900">Bagas Aditya</div>
                                <div class="text-xs text-gray-500">Pemilik anjing, Surabaya</div>
                            </div>
                        </footer>
                    </blockquote>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================
     FAQ (Accordion Preline)
========================================================= --}}
    <section id="faq" class="py-20 lg:py-28 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-animate="fade-up">
                <span class="text-amber-600 font-semibold text-sm">FAQ</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-gray-900">Pertanyaan yang sering ditanyakan</h2>
            </div>

            <div class="hs-accordion-group space-y-3" data-hs-accordion-always-open>
                @foreach ([['q' => 'Apakah PawPaw gratis digunakan?', 'a' => 'Ya, mendaftar dan menggunakan fitur dasar PawPaw 100% gratis. Biaya hanya dikenakan untuk layanan berbayar seperti booking klinik atau grooming.'], ['q' => 'Kota mana saja yang sudah terjangkau?', 'a' => 'Saat ini PawPaw sudah tersedia di Malang, Surabaya, dan Jakarta, dengan rencana ekspansi ke kota lain tahun depan.'], ['q' => 'Bagaimana cara membatalkan booking?', 'a' => 'Kamu bisa membatalkan booking langsung dari halaman riwayat di aplikasi, maksimal 2 jam sebelum jadwal.']] as $i => $item)
                    <div class="hs-accordion bg-white border border-gray-100 rounded-xl {{ $i === 0 ? 'active' : '' }}"
                        id="faq-{{ $i }}">
                        <button
                            class="hs-accordion-toggle w-full flex items-center justify-between gap-3 py-4 px-5 text-left font-medium text-gray-900"
                            aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                            aria-controls="faq-content-{{ $i }}">
                            {{ $item['q'] }}
                            <svg class="hs-accordion-active:hidden size-4 shrink-0" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                            <svg class="hs-accordion-active:block hidden size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="m18 15-6-6-6 6" />
                            </svg>
                        </button>
                        <div id="faq-content-{{ $i }}"
                            class="hs-accordion-content {{ $i === 0 ? '' : 'hidden' }} w-full overflow-hidden transition-[height] duration-300"
                            role="region">
                            <p class="pb-4 px-5 text-sm text-gray-600 leading-relaxed">{{ $item['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================================================
     CTA
========================================================= --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div data-animate="fade-up"
                class="relative overflow-hidden rounded-3xl bg-amber-500 px-8 py-14 sm:px-16 text-center">
                <div class="absolute -top-10 -right-10 size-40 rounded-full bg-white/10"></div>
                <div class="absolute -bottom-16 -left-10 size-52 rounded-full bg-white/10"></div>
                <h2 class="relative text-3xl sm:text-4xl font-bold text-white">Siap bikin hidup si bulu lebih mudah?</h2>
                <p class="relative mt-4 text-amber-50 max-w-xl mx-auto">Bergabung dengan ribuan pemilik hewan lain yang
                    sudah lebih tenang mengurus si kesayangan.</p>
                <a href="{{ route('vendor.register') }}"
                    class="relative inline-block mt-8 py-3.5 px-8 text-sm font-semibold rounded-xl bg-white text-amber-600 hover:bg-amber-50 transition shadow-lg">
                    Download PawPaw — Gratis
                </a>
            </div>
        </div>
    </section>
@endsection
