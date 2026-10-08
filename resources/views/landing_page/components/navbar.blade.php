<header class="sticky top-0 z-50 bg-[#FFF8F5] backdrop-blur-md border-b border-gray-100">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap lg:flex-nowrap items-center justify-between" aria-label="Global">

        <div class="flex items-center">
            <a class="flex items-center focus:outline-none focus:opacity-80" href="#" aria-label="PawPaw Brand">
                <img class="w-36 h-auto" src="{{ asset('assets/img/pawpaw-logo.webp') }}" alt="PawPaw Logo">
            </a>
        </div>

        <div class="flex items-center gap-3 ms-auto lg:ms-0 lg:order-3">
            <div class="hidden sm:flex items-center gap-2 lg:gap-3">
                <a href="#"
                    class="py-2.5 px-3.5 lg:px-5 text-sm font-medium text-gray-700 hover:text-[#EE6D52] hover:bg-white/60 rounded-full transition-all whitespace-nowrap">
                    Daftar Pelanggan
                </a>
                <a href="{{ route('vendor.register') }}"
                    class="py-2.5 lg:py-3 px-4 lg:px-6 inline-flex justify-center items-center text-sm font-medium rounded-full border border-transparent bg-[#EE6D52] text-white hover:bg-[#d95b42] focus:outline-none focus:bg-[#d95b42] transition-colors shadow-xs whitespace-nowrap">
                    Daftar Mitra
                </a>
            </div>

            <div class="lg:hidden">
                <button type="button"
                    class="hs-collapse-toggle p-2 inline-flex justify-center items-center gap-x-2 rounded-xl text-gray-700 hover:bg-white/80 focus:outline-none transition-colors"
                    id="hs-navbar-collapse"
                    aria-expanded="false"
                    aria-controls="hs-navbar-menu"
                    aria-label="Toggle navigation"
                    data-hs-collapse="#hs-navbar-menu">
                    <svg class="hs-collapse-open:hidden shrink-0 size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg class="hs-collapse-open:block hidden shrink-0 size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div id="hs-navbar-menu"
            class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow lg:block lg:w-auto lg:basis-auto lg:order-2 lg:ms-10"
            aria-labelledby="hs-navbar-collapse">
            <div class="flex flex-col lg:flex-row lg:items-center gap-y-2 lg:gap-y-0 lg:gap-8 text-sm font-medium text-gray-700 pt-3 pb-2 lg:py-0 border-t border-gray-100 lg:border-t-0 mt-2 lg:mt-0">
                <a href="#services" class="py-2 px-3 lg:p-0 rounded-lg hover:bg-white/60 lg:hover:bg-transparent hover:text-[#EE6D52] transition-colors">Layanan</a>
                <a href="#features" class="py-2 px-3 lg:p-0 rounded-lg hover:bg-white/60 lg:hover:bg-transparent hover:text-[#EE6D52] transition-colors">Fitur</a>
                <a href="#how-it-works" class="py-2 px-3 lg:p-0 rounded-lg hover:bg-white/60 lg:hover:bg-transparent hover:text-[#EE6D52] transition-colors">Cara Kerja</a>
                <a href="#testimonials" class="py-2 px-3 lg:p-0 rounded-lg hover:bg-white/60 lg:hover:bg-transparent hover:text-[#EE6D52] transition-colors">Testimoni</a>
                <a href="#faq" class="py-2 px-3 lg:p-0 rounded-lg hover:bg-white/60 lg:hover:bg-transparent hover:text-[#EE6D52] transition-colors">FAQ</a>
            </div>
        </div>
    </nav>
</header>