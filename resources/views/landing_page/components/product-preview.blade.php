<figure class="landing-product-preview landing-product-{{ $audience }}" data-animate="fade-up" @if ($audience === 'partner') data-motion-once @endif>
    @if ($audience === 'partner')
        <div class="landing-partner-sticker"><flux:icon.calendar-days class="size-5" aria-hidden="true" /><span>Reservasi bersama kami</span></div>
        <svg class="landing-partner-spark" viewBox="0 0 40 40" fill="none" aria-hidden="true" focusable="false"><path d="M20 4Q20 20 36 20Q20 20 20 36Q20 20 4 20Q20 20 20 4Z" stroke="currentColor" stroke-width="2" /></svg>
    @endif
    <figcaption class="landing-preview-caption">
        <span class="landing-preview-label">{{ $audience === 'partner' ? 'Untuk tim klinik' : 'Sebelum kamu datang' }}</span>
        <h3>{{ $audience === 'partner' ? 'Layanan sampai reservasi, lebih mudah dipantau.' : 'Tahu pilihannya. Tahu langkah berikutnya.' }}</h3>
        <p>Ilustrasi fitur PawPaw dengan data contoh.</p>
    </figcaption>
    @if ($audience === 'partner')
        <div class="landing-preview-toolbar"><span>PawPaw <span class="landing-preview-muted">/ Mitra</span></span><span class="landing-preview-example">Contoh</span></div>
        <ul class="landing-preview-tools">
            <li><span class="landing-preview-icon"><flux:icon.scissors class="size-5" aria-hidden="true" /></span><div><h4>Layanan &amp; harga</h4><p>Kelola pilihan perawatan di klinikmu.</p></div></li>
            <li><span class="landing-preview-icon"><flux:icon.calendar-days class="size-5" aria-hidden="true" /></span><div><h4>Jam operasional</h4><p>Atur hari dan jam pelayanan.</p></div></li>
            <li><span class="landing-preview-icon"><flux:icon.clipboard-document-list class="size-5" aria-hidden="true" /></span><div><h4>Reservasi masuk</h4><p>Periksa pengajuan dan perbarui statusnya.</p></div></li>
        </ul>
    @else
        <div class="landing-preview-flow">
            <div class="landing-preview-panel">
                <span class="landing-preview-icon"><flux:icon.heart class="size-5" aria-hidden="true" /></span>
                <h4>Lihat layanan &amp; harga</h4>
                <p>Kenali pilihan perawatannya sebelum membuat reservasi.</p>
                <div class="landing-preview-service"><span>Grooming</span><span>Harga dari klinik</span></div>
            </div>
            <div class="landing-preview-panel">
                <span class="landing-preview-icon"><flux:icon.calendar-days class="size-5" aria-hidden="true" /></span>
                <h4>Pilih waktu yang tersedia</h4>
                <p>Slot waktu untuk klinik dan grooming, rentang tanggal untuk Pet Hotel.</p>
                <div class="landing-preview-slots" aria-label="Contoh slot waktu"><span>09.00</span><span>10.00</span><span>11.00</span></div>
            </div>
            <div class="landing-preview-panel">
                <span class="landing-preview-icon"><flux:icon.clipboard-document-list class="size-5" aria-hidden="true" /></span>
                <h4>Cek status reservasi</h4>
                <p>Pengajuanmu perlu dikonfirmasi klinik sebelum kamu datang.</p>
                <span class="landing-preview-status"><span aria-hidden="true"></span>Menunggu konfirmasi</span>
            </div>
        </div>
    @endif
</figure>
