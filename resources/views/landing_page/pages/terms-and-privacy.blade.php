@extends('landing_page.layouts.app-landing')

@section('content')
    <div
        x-data="{
            activeTab: '{{ request('tab') === 'privacy' ? 'privacy' : 'terms' }}',
            setTab(tab) {
                this.activeTab = tab;
                if (window.history && window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    window.history.replaceState(null, '', url.toString());
                }
            }
        }"
        class="min-h-screen bg-[#FFF8F5] text-zinc-800"
    >
        {{-- =========================================================
             HERO HEADER
        ========================================================= --}}
        <section class="relative overflow-hidden border-b border-orange-100/70 bg-gradient-to-b from-[#FFF0E8] to-[#FFF8F5] pt-12 pb-14 sm:pt-16 sm:pb-20">
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 text-xs font-semibold text-[#1B5E43] shadow-xs border border-orange-200/50 mb-5">
                    <svg class="size-4 text-[#EE6D52]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    Pusat Informasi Hukum & Privasi PawPaw
                </div>

                {{-- Page Title --}}
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-gray-900" style="font-family: 'Fredoka', 'Nunito', sans-serif;">
                    Syarat, Ketentuan &amp; <span class="text-[#EE6D52]">Kebijakan Privasi</span>
                </h1>

                <p class="mx-auto mt-4 max-w-2xl text-sm sm:text-base text-gray-600 leading-relaxed">
                    Komitmen penuh kami dalam memberikan perlindungan data pribadi dan kenyamanan bagi Anda serta si kesayangan saat menggunakan layanan klinik, pet hotel, dan grooming bersama mitra PawPaw.
                </p>

                <p class="mt-3 text-xs text-gray-500">
                    Terakhir diperbarui: <span class="font-medium text-gray-700">10 November 2026</span>
                </p>

                {{-- Tab Switcher Pills --}}
                <div class="mt-8 inline-flex items-center rounded-full bg-white/90 p-1.5 shadow-sm border border-zinc-200/80 backdrop-blur-xs">
                    <button
                        type="button"
                        @click="setTab('terms')"
                        :class="activeTab === 'terms' ? 'bg-[#1B5E43] text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/60'"
                        class="flex items-center gap-2 rounded-full px-5 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer"
                    >
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span>Syarat &amp; Ketentuan</span>
                    </button>

                    <button
                        type="button"
                        @click="setTab('privacy')"
                        :class="activeTab === 'privacy' ? 'bg-[#1B5E43] text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100/60'"
                        class="flex items-center gap-2 rounded-full px-5 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer"
                    >
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span>Kebijakan Privasi</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- =========================================================
             MAIN CONTENT SECTION
        ========================================================= --}}
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- Left Sidebar: Quick Links & Summary Card (Sticky) --}}
                <aside class="lg:col-span-4 space-y-6">
                    {{-- Sticky Nav Box --}}
                    <div class="sticky top-24 rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-400">
                            Daftar Isi Dokumen
                        </h2>

                        {{-- Nav for Terms --}}
                        <div x-show="activeTab === 'terms'" x-cloak class="mt-4 space-y-2 text-sm">
                            <a href="#terms-definisi" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                1. Ketentuan Umum &amp; Definisi
                            </a>
                            <a href="#terms-akun" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                2. Akun &amp; Keamanan Pengguna
                            </a>
                            <a href="#terms-layanan" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                3. Reservasi &amp; Syarat Hewan
                            </a>
                            <a href="#terms-pembatalan" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                4. Pembatalan &amp; Kehadiran
                            </a>
                            <a href="#terms-tanggung-jawab" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                5. Batasan Tanggung Jawab
                            </a>
                            <a href="#terms-perubahan" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                6. Perubahan Ketentuan
                            </a>
                        </div>

                        {{-- Nav for Privacy --}}
                        <div x-show="activeTab === 'privacy'" x-cloak class="mt-4 space-y-2 text-sm">
                            <a href="#privacy-data-dikumpulkan" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                1. Data yang Dikumpulkan
                            </a>
                            <a href="#privacy-penggunaan-data" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                2. Penggunaan Informasi
                            </a>
                            <a href="#privacy-pihak-ketiga" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                3. Pembagian ke Mitra &amp; Pihak Ketiga
                            </a>
                            <a href="#privacy-keamanan" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                4. Keamanan &amp; Penyimpanan
                            </a>
                            <a href="#privacy-hak-pengguna" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                5. Hak &amp; Kontrol Pengguna
                            </a>
                            <a href="#privacy-kontak" class="block rounded-xl px-3 py-2 text-zinc-700 hover:bg-zinc-50 hover:text-[#1B5E43] font-medium transition">
                                6. Kontak Layanan Bantuan
                            </a>
                        </div>

                        <div class="my-6 border-t border-zinc-100"></div>

                        {{-- Need Help Box --}}
                        <div class="rounded-2xl bg-[#FFF5F0] p-4 border border-orange-200/50">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#EE6D52] text-white">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-gray-900">Ada Pertanyaan?</h3>
                                    <p class="text-xs text-gray-600 mt-0.5">Tim PawPaw siap membantu kendala atau pertanyaan Anda.</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="mailto:support@pawpaw.id" class="inline-flex w-full items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-semibold text-[#EE6D52] shadow-2xs hover:bg-orange-50 transition border border-orange-100">
                                    Hubungi Tim Dukungan
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Right Column: Detailed Clauses --}}
                <div class="lg:col-span-8 space-y-8">

                    {{-- =========================================================
                         TAB 1: SYARAT & KETENTUAN
                    ========================================================= --}}
                    <div x-show="activeTab === 'terms'" x-cloak class="space-y-6">

                        {{-- Pasal 1 --}}
                        <article id="terms-definisi" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    1
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Ketentuan Umum &amp; Definisi
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    Selamat datang di platform <strong>PawPaw</strong>. Dengan mengakses, mendaftar, atau menggunakan situs web dan aplikasi kami, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh Syarat &amp; Ketentuan ini.
                                </p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li><strong>Platform PawPaw:</strong> Platform digital yang menghubungkan pemilik hewan peliharaan dengan mitra penyedia layanan perawatan hewan peliharaan terpercaya.</li>
                                    <li><strong>Pengguna / Client:</strong> Individu yang mendaftarkan akun di PawPaw untuk mencari informasi serta melakukan reservasi layanan klinik, pet hotel, atau grooming.</li>
                                    <li><strong>Mitra / Vendor:</strong> Badan usaha, klinik hewan mandiri, penyedia pet hotel, atau salon grooming yang telah lolos verifikasi dan bekerjasama dengan PawPaw.</li>
                                    <li><strong>Layanan:</strong> Jasa perawatan hewan meliputi pemeriksaan medis, rawat inap, vaksinasi, penitipan hewan (pet hotel), dan perawatan kebersihan (grooming).</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 2 --}}
                        <article id="terms-akun" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    2
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Akun &amp; Keamanan Pengguna
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    Untuk dapat melakukan pemesanan layanan, Anda wajib membuat akun pengguna dengan ketentuan sebagai berikut:
                                </p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li>Pengguna berusia minimal 18 tahun, atau didampingi orang tua/wali yang sah menurut hukum Republik Indonesia.</li>
                                    <li>Data yang dimasukkan (nama lengkap, nomor telepon, alamat email) harus akurat, valid, dan dapat dihubungi.</li>
                                    <li>Pengguna bertanggung jawab penuh atas kerahasiaan kata sandi akun dan segala aktivitas yang dilakukan melalui akun tersebut.</li>
                                    <li>PawPaw berhak menonaktifkan atau memblokir akun yang terbukti melakukan pemesanan fiktif (<em>fake booking</em>), manipulasi data, atau pelanggaran hukum lainnya.</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 3 --}}
                        <article id="terms-layanan" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    3
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Reservasi &amp; Syarat Hewan Peliharaan
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    Demi kesehatan, keselamatan, dan kenyamanan bersama seluruh hewan dan staf perawat:
                                </p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li><strong>Status Kesehatan:</strong> Hewan peliharaan yang didaftarkan untuk layanan Pet Hotel dan Grooming wajib dalam kondisi bebas dari kutu akut, jamur aktif parah, serta penyakit menular, kecuali pada layanan Klinik khusus perawatan isolasi.</li>
                                    <li><strong>Buku Vaksinasi:</strong> Untuk layanan Pet Hotel, mitra berhak meminta bukti buku vaksinasi aktif untuk mencegah penularan virus antarhewan.</li>
                                    <li><strong>Hewan yang Dilarang:</strong> PawPaw hanya melayani hewan peliharaan domestik resmi (anjing, kucing, kelinci, dsb.). Hewan liar yang dilindungi undang-undang tanpa izin resmi tidak diperbolehkan.</li>
                                    <li><strong>Hak Penolakan:</strong> Mitra berhak menolak melayani hewan yang menunjukkan perilaku agresif ekstrem yang membahayakan keselamatan staf atau hewan lain.</li>
                                </ul>

                                <div class="rounded-2xl bg-amber-50 p-4 border border-amber-200/70 text-amber-900 text-xs mt-4">
                                    <strong>Perhatian:</strong> Pastikan Anda memberikan catatan khusus (seperti alergi makanan, riwayat kejang, atau trauma suara) pada kolom catatan booking agar mitra dapat memberikan penanganan yang sesuai.
                                </div>
                            </div>
                        </article>

                        {{-- Pasal 4 --}}
                        <article id="terms-pembatalan" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    4
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Pembatalan &amp; Kehadiran
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li><strong>Toleransi Keterlambatan:</strong> Pengguna diharapkan hadir tepat waktu sesuai jam operasional dan slot jadwal reservasi. Toleransi keterlambatan maksimal 15–30 menit bergantung pada kebijakan masing-masing mitra.</li>
                                    <li><strong>Pembatalan oleh Pengguna:</strong> Pembatalan dapat dilakukan melalui menu <em>Reservasi Saya</em> sebelum waktu layanan dimulai.</li>
                                    <li><strong>Pembatalan oleh Mitra:</strong> Jika terjadi kondisi darurat atau kapasitas penuh mendadak, mitra berhak menolak atau menjadwalkan ulang dengan memberitahukan pengguna terlebih dahulu.</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 5 --}}
                        <article id="terms-tanggung-jawab" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    5
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Batasan Tanggung Jawab
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    PawPaw bertindak sebagai penyedia platform teknologi. Seluruh tindakan medis, diagnosis dokter hewan, pengerjaan salon grooming, serta pemeliharaan fisik pada pet hotel merupakan tanggung jawab profesional independen dari masing-masing Mitra Vendor.
                                </p>
                                <p>
                                    PawPaw tidak bertanggung jawab atas kerugian langsung atau tidak langsung akibat ketidakakuratan data yang dimasukkan oleh pengguna atau sengketa transaksi di luar platform resmi PawPaw.
                                </p>
                            </div>
                        </article>

                        {{-- Pasal 6 --}}
                        <article id="terms-perubahan" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    6
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Perubahan Ketentuan
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    PawPaw dapat mengubah atau memperbarui Syarat &amp; Ketentuan ini sewaktu-waktu. Setiap perubahan akan diumumkan pada halaman ini beserta tanggal pembaruan terbaru. Kelanjutan penggunaan layanan setelah tanggal perubahan menandakan persetujuan Anda terhadap ketentuan yang diperbarui.
                                </p>
                            </div>
                        </article>

                    </div>

                    {{-- =========================================================
                         TAB 2: KEBIJAKAN PRIVASI
                    ========================================================= --}}
                    <div x-show="activeTab === 'privacy'" x-cloak class="space-y-6">

                        {{-- Pasal 1 --}}
                        <article id="privacy-data-dikumpulkan" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    1
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Data yang Kami Kumpulkan
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    Kami mengumpulkan informasi yang Anda berikan secara langsung saat menggunakan platform PawPaw, meliputi:
                                </p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li><strong>Informasi Akun Pribadi:</strong> Nama lengkap, username, alamat email, nomor telepon/WhatsApp, kata sandi terenkripsi, serta foto profil (avatar).</li>
                                    <li><strong>Informasi Hewan Peliharaan:</strong> Nama hewan, jenis/ras, tanggal lahir/usia, riwayat alergi, serta catatan keluhan saat melakukan reservasi.</li>
                                    <li><strong>Data Transaksi &amp; Reservasi:</strong> Riwayat pemesanan layanan, pilihan tanggal dan jam, nama mitra yang dikunjungi, status pemesanan, dan riwayat ulasan.</li>
                                    <li><strong>Data Teknis:</strong> Alamat IP, jenis peramban (browser), dan log akses sesi saat menggunakan sistem kami.</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 2 --}}
                        <article id="privacy-penggunaan-data" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    2
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Tujuan Penggunaan Informasi
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>Informasi yang terkumpul kami gunakan untuk:</p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li>Memproses, mengonfirmasi, dan meneruskan jadwal reservasi Anda kepada Mitra Klinik, Pet Hotel, atau Grooming terkait.</li>
                                    <li>Mengirimkan notifikasi dan pengingat terkait status pesanan (dikonfirmasi, selesai, atau dibatalkan).</li>
                                    <li>Memverifikasi identitas pengguna dan mencegah aktivitas penipuan atau pemesanan fiktif.</li>
                                    <li>Memberikan layanan dukungan pelanggan (*customer service*) saat Anda memerlukan bantuan.</li>
                                    <li>Menganalisis performa sistem untuk meningkatkan kualitas antarmuka dan kecepatan layanan PawPaw.</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 3 --}}
                        <article id="privacy-pihak-ketiga" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    3
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Pembagian Data ke Mitra &amp; Pihak Ketiga
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <div class="rounded-2xl bg-emerald-50 p-4 border border-emerald-200/80 text-emerald-900 text-xs">
                                    <strong>Prinsip Kami:</strong> PawPaw <u>TIDAK PERNAH</u> menjual, menyewakan, atau memperdagangkan data pribadi Anda kepada pihak ketiga pengiklan manapun.
                                </div>
                                <p>Data Anda hanya dapat dibagikan dalam kondisi terbatas berikut:</p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li><strong>Kepada Mitra Vendor Terpilih:</strong> Saat Anda melakukan booking, nama, nomor telepon, dan data hewan peliharaan Anda diteruskan kepada mitra yang Anda pilih agar staf mitra dapat melayani kedatangan Anda.</li>
                                    <li><strong>Kepatuhan Hukum:</strong> Apabila diwajibkan oleh peraturan perundang-undangan atau perintah pengadilan yang sah di wilayah Republik Indonesia.</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 4 --}}
                        <article id="privacy-keamanan" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    4
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Keamanan &amp; Penyimpanan Data
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    Keamanan data Anda adalah prioritas utama kami. Kami menerapkan standar perlindungan teknis dan operasional yang ketat:
                                </p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li>Seluruh kata sandi akun disimpan dalam bentuk terenkripsi satu arah menggunakan algoritma hashing standar industri (Bcrypt).</li>
                                    <li>Pertukaran data antara browser Anda dan peladen (server) dilindungi oleh protokol enkripsi HTTPS (SSL/TLS).</li>
                                    <li>Akses data internal dibatasi secara ketat hanya untuk staf berwenang yang bertugas mengelola sistem.</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 5 --}}
                        <article id="privacy-hak-pengguna" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    5
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Hak &amp; Kontrol Pengguna
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>Sebagai pemilik data pribadi, Anda memiliki kendali penuh atas akun Anda:</p>
                                <ul class="list-disc list-outside pl-5 space-y-2">
                                    <li><strong>Akses &amp; Pembaruan:</strong> Anda dapat memperbarui informasi nama, nomor telepon, email, kata sandi, dan foto avatar Anda kapan saja melalui halaman <em>Profil Saya</em>.</li>
                                    <li><strong>Hapus Foto Profil:</strong> Anda berhak menghapus foto profil kapan saja dan mengembalikannya ke inisial nama default.</li>
                                    <li><strong>Penutupan Akun:</strong> Anda dapat mengajukan permintaan penutupan akun dan penghapusan data dengan menghubungi tim dukungan kami.</li>
                                </ul>
                            </div>
                        </article>

                        {{-- Pasal 6 --}}
                        <article id="privacy-kontak" class="scroll-mt-28 rounded-3xl border border-zinc-200/80 bg-white p-6 sm:p-8 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#E8F3EE] text-sm font-bold text-[#1B5E43]">
                                    6
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-[#1B3C2A]">
                                    Kontak Layanan Bantuan
                                </h2>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600 leading-relaxed">
                                <p>
                                    Apabila Anda memiliki pertanyaan, saran, atau kekhawatiran terkait Kebijakan Privasi atau tata kelola data pribadi di PawPaw, silakan hubungi kami melalui:
                                </p>
                                <div class="mt-2 rounded-2xl bg-zinc-50 p-4 border border-zinc-200/80 space-y-1.5 text-xs text-zinc-700">
                                    <p><strong>Email Dukungan:</strong> support@pawpaw.id</p>
                                    <p><strong>Operasional:</strong> Senin – Minggu (08.00 – 20.00 WIB)</p>
                                    <p><strong>Platform:</strong> PawPaw Care Indonesia</p>
                                </div>
                            </div>
                        </article>

                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection
