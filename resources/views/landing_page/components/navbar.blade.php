<header class="sticky top-0 z-50 bg-[#FFF8F5] backdrop-blur-md border-b border-gray-100">
    <nav class="max-w-7xl mx-auto flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center gap-8">
            <a class="flex-none text-xl font-semibold text-foreground focus:outline-hidden focus:opacity-80" href="#" aria-label="Brand">
                <img class="w-36 h-auto" src="../../assets/img/pawpaw-logo.png" alt="Logo">
            </a>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="#services" class="hover:text-amber-600 transition">Layanan</a>
                <a href="#features" class="hover:text-amber-600 transition">Fitur</a>
                <a href="#how-it-works" class="hover:text-amber-600 transition">Cara Kerja</a>
                <a href="#testimonials" class="hover:text-amber-600 transition">Testimoni</a>
                <a href="#faq" class="hover:text-amber-600 transition">FAQ</a>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="#"
                class="hidden sm:inline-block py-3 px-6 text-sm font-medium rounded-full text-gray-700 hover:bg-gray-100 transition">
                Daftar Pelanggan
            </a>
            <a href="{{ route('vendor.register') }}"
                class="py-3 px-6 inline-flex justify-center items-center text-sm font-medium rounded-full border border-transparent bg-[#EE6D52] text-white hover:bg-[#d95b42] focus:outline-none focus:bg-[#d95b42] transition-colors shadow-xs">
                Daftar Mitra
            </a>
        </div>
    </nav>
</header>