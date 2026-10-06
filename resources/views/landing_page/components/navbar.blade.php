<header class="sticky top-0 z-50 bg-[#FFF8F5] backdrop-blur-md border-b border-gray-100">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 md:py-4 flex flex-wrap md:flex-nowrap items-center justify-between gap-y-2"
        aria-label="Navigasi utama">

        {{-- Logo + menu desktop --}}
        <div class="flex items-center gap-8">
            <a class="flex-none focus:outline-hidden focus:opacity-80" href="{{ url('/') }}" aria-label="PawPaw">
                <img class="w-28 sm:w-36 h-auto" src="{{ asset('assets/img/pawpaw-logo.webp') }}" alt="PawPaw">
            </a>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="{{ url('/') }}#services" class="hover:text-amber-600 transition">Layanan</a>
                <a href="{{ url('/') }}#features" class="hover:text-amber-600 transition">Fitur</a>
                <a href="{{ url('/') }}#how-it-works" class="hover:text-amber-600 transition">Cara Kerja</a>
                <a href="{{ url('/') }}#testimonials" class="hover:text-amber-600 transition">Testimoni</a>
                <a href="{{ url('/') }}#faq" class="hover:text-amber-600 transition">FAQ</a>
            </div>
        </div>

        {{-- Tombol aksi (tablet ke atas) + hamburger (mobile) --}}
        <div class="flex items-center gap-2 sm:gap-3">
            <a href="#"
                class="hidden md:inline-block py-3 px-6 text-sm font-medium rounded-full text-gray-700 hover:bg-gray-100 transition">
                Daftar Pelanggan
            </a>
            <a href="{{ route('vendor.register') }}"
                class="hidden sm:inline-flex py-2.5 px-5 md:py-3 md:px-6 justify-center items-center text-sm font-medium rounded-full border border-transparent bg-[#EE6D52] text-white hover:bg-[#d95b42] focus:outline-none focus:bg-[#d95b42] transition-colors shadow-xs">
                Daftar Mitra
            </a>

            {{-- Hamburger --}}
            <button type="button"
                class="hs-collapse-toggle md:hidden size-10 inline-flex justify-center items-center rounded-full text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition"
                id="navbar-toggle" aria-expanded="false" aria-controls="navbar-collapse"
                aria-label="Buka menu" data-hs-collapse="#navbar-collapse">
                <svg class="hs-collapse-open:hidden size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <line x1="3" x2="21" y1="6" y2="6" />
                    <line x1="3" x2="21" y1="12" y2="12" />
                    <line x1="3" x2="21" y1="18" y2="18" />
                </svg>
                <svg class="hs-collapse-open:block hidden size-6" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>

        {{-- Menu mobile --}}
        <div id="navbar-collapse"
            class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full md:hidden"
            aria-labelledby="navbar-toggle">
            <div class="flex flex-col gap-1 pt-3 pb-2 text-base font-medium text-gray-700 border-t border-gray-100 mt-2">
                <a href="{{ url('/') }}#services" class="py-3 px-2 rounded-lg hover:bg-white hover:text-amber-600 transition">Layanan</a>
                <a href="{{ url('/') }}#features" class="py-3 px-2 rounded-lg hover:bg-white hover:text-amber-600 transition">Fitur</a>
                <a href="{{ url('/') }}#how-it-works" class="py-3 px-2 rounded-lg hover:bg-white hover:text-amber-600 transition">Cara Kerja</a>
                <a href="{{ url('/') }}#testimonials" class="py-3 px-2 rounded-lg hover:bg-white hover:text-amber-600 transition">Testimoni</a>
                <a href="{{ url('/') }}#faq" class="py-3 px-2 rounded-lg hover:bg-white hover:text-amber-600 transition">FAQ</a>

                <div class="flex flex-col gap-2 mt-3">
                    <a href="#"
                        class="py-3 px-6 text-center text-sm font-medium rounded-full border border-gray-200 text-gray-700 hover:bg-white transition">
                        Daftar Pelanggan
                    </a>
                    <a href="{{ route('vendor.register') }}"
                        class="sm:hidden py-3 px-6 text-center text-sm font-medium rounded-full bg-[#EE6D52] text-white hover:bg-[#d95b42] transition-colors">
                        Daftar Mitra
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>