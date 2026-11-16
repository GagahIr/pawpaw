@php
    $questions = [
        ['question' => 'Layanan apa saja yang tersedia di PawPaw?', 'answer' => 'Kamu bisa mencari layanan klinik/medis, grooming, dan pet hotel. Pilihan perawatan dan harga mengikuti layanan yang diaktifkan masing-masing mitra.'],
        ['question' => 'Apakah saya perlu akun untuk melakukan reservasi?', 'answer' => 'Ya. Masuk atau daftar sebagai pelanggan untuk membuka menu Klinik dan membuat reservasi. Pencarian dan pemilihan mitra dilakukan melalui menu Klinik.'],
        ['question' => 'Apakah mitra yang ditampilkan sudah diverifikasi?', 'answer' => 'Daftar klinik menampilkan mitra yang sudah disetujui admin, memiliki langganan aktif, dan mengaktifkan layanan. Verifikasi profil bukan jaminan hasil pelayanan medis.'],
        ['question' => 'Bagaimana cara membuat dan memantau reservasi?', 'answer' => 'Buka profil klinik, pilih layanan, lalu isi tanggal atau slot yang tersedia. Status dan riwayatnya dapat dilihat melalui menu Reservasi saya & Riwayat. Tunggu status diterima sebelum datang.'],
        ['question' => 'Apakah layanan hewan dibayar melalui PawPaw?', 'answer' => 'Pembayaran layanan hewan dilakukan langsung di klinik. Pembayaran melalui Midtrans di PawPaw digunakan untuk langganan mitra.'],
        ['question' => 'Bagaimana cara bergabung sebagai mitra?', 'answer' => 'Daftarkan akun pemilik dan profil bisnis, lalu tunggu verifikasi admin. Setelah disetujui, bayar langganan dan siapkan layanan serta jadwal operasional.'],
        ['question' => 'Apakah langganan mitra diperpanjang otomatis?', 'answer' => 'Tidak. Mitra melakukan pembayaran sendiri untuk memperpanjang langganan. Masa aktif mengikuti ketentuan paket yang ditampilkan saat pembayaran.'],
        ['question' => 'Siapa yang memberikan layanan perawatan hewan?', 'answer' => 'Layanan diberikan oleh mitra yang kamu pilih. PawPaw membantu menampilkan informasi dan mengatur reservasi. Jika ada pertanyaan mengenai perawatan, hubungi kontak klinik pada halaman profilnya.'],
    ];
@endphp
<section id="faq" class="landing-section landing-faq-section" aria-labelledby="faq-heading">
    <div class="landing-wrap landing-faq-grid">
        <div>
            <p class="landing-eyebrow">Biar makin yakin</p>
            <h2 id="faq-heading" class="landing-heading">Ada yang ingin<br><span>kamu tanyakan?</span></h2>
            <p class="landing-description">Kenali cara reservasi dan langkah bergabung sebelum mulai.</p>
            <a href="{{ route('terms') }}" class="landing-text-link">Baca syarat &amp; ketentuan <span aria-hidden="true">&rarr;</span></a>
            <div class="landing-faq-mascot" aria-hidden="true">
                <span class="landing-faq-cat-frame">
                    <img src="{{ asset('assets/faq-curious-kitten.png') }}" alt="" width="260" height="260" loading="lazy">
                </span>
                <span class="landing-faq-bubble"><flux:icon.chat-bubble-left-ellipsis class="size-6" /><span>Aku juga<br>penasaran!</span></span>
            </div>
        </div>
        <div class="landing-faq-list">
            @foreach ($questions as $question)
                <details name="landing-faq" class="landing-faq-item">
                    <summary><span class="landing-faq-number" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="landing-faq-question">{{ $question['question'] }}</span><flux:icon.plus class="size-5 shrink-0" aria-hidden="true" /></summary>
                    <p>{{ $question['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
