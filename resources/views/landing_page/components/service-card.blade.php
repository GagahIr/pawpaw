@php
    $service = match (strtolower($category->name)) {
        'clinic' => ['label' => 'Klinik / Medis', 'headline' => 'Saat kesehatannya perlu perhatian.', 'description' => 'Lihat pilihan pemeriksaan dan perawatan yang tersedia di klinik.', 'tone' => 'cream'],
        'grooming' => ['label' => 'Grooming', 'headline' => 'Biar bersih dan nyaman dipeluk.', 'description' => 'Pilih perawatan kebersihan yang sesuai untuk si kesayangan.', 'tone' => 'peach'],
        'pet hotel' => ['label' => 'Pet Hotel', 'headline' => 'Ada rencana pergi?', 'description' => 'Cari tempat menginap untuknya dan tentukan tanggal penitipannya.', 'tone' => 'sage'],
        default => ['label' => $category->name, 'headline' => 'Ada perawatan untuk kebutuhannya.', 'description' => 'Kenali layanan yang tersedia dari mitra PawPaw.', 'tone' => 'sage'],
    };
@endphp
<a href="{{ route('client.clinic', ['layanan' => $category->id]) }}" class="landing-service-card landing-service-{{ $service['tone'] }}" data-animate="fade-up" data-animate-delay="{{ ($loop->index ?? 0) * 0.08 }}">
    <div class="landing-service-icon" aria-hidden="true">
        @if (strtolower($category->name) === 'grooming')
            <flux:icon.scissors />
        @elseif (strtolower($category->name) === 'pet hotel')
            <flux:icon.home />
        @else
            <flux:icon.heart />
        @endif
    </div>
    <h3>{{ $service['label'] }}</h3>
    <p class="landing-service-headline">{{ $service['headline'] }}</p>
    <p>{{ $service['description'] }}</p>
    <span class="landing-service-action">Lihat klinik <span aria-hidden="true">&rarr;</span></span>
</a>
