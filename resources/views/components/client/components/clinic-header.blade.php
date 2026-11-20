@php
    $isLandingPage = $isLandingPage ?? false;
    $isClinicPage = $isClinicPage ?? false;
    $clientUser = auth('client')->user();
    $clientUsername = $clientUser?->username ?? ($clientUser?->name ?? 'User');
    $mainSiteUrl = rtrim(route('landing_page'), '/');
    $clientLoginUrl = $mainSiteUrl . route('client.login', [], false);
    $clientRegisterUrl = $mainSiteUrl . route('client.register', [], false);
    $clientLogoutUrl = $mainSiteUrl . route('client.logout', [], false);
@endphp

<flux:header container class="border-b border-zinc-200 bg-white lg:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Buka navigasi" />
    <flux:brand href="{{ route('landing_page') }}" logo="{{ asset('assets/img/pawpaw-logo.webp') }}"
        class="clinic-brand hidden lg:flex" />
    {{-- <flux:brand href="{{ route('landing_page') }}" logo="{{ asset('assets/img/pawpaw-logo.webp') }}" class="{{ $isClinicPage ? 'clinic-brand' : 'max-lg:hidden dark:hidden' }}" /> --}}
    <flux:navbar class="max-lg:hidden">
        @if ($clientUser && !$isLandingPage)
            <flux:navbar.item href="{{ route('client.clinic') }}"
                :current="request()->routeIs('client.clinic', 'client.vendor.detail', 'client.clinic.booking')">Klinik
            </flux:navbar.item>
            <flux:navbar.item href="{{ route('client.my-bookings') }}"
                :current="request()->routeIs('client.my-bookings')">
                Reservasi saya &amp; Riwayat
            </flux:navbar.item>
        @elseif ($isLandingPage && $clientUser)
            <flux:navbar.item href="{{ route('client.dashboard') }}">Dashboard</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#services">Layanan</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#how-it-works">Cara Kerja</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#for-partners">Untuk Mitra</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#faq">FAQ</flux:navbar.item>
        @elseif ($isLandingPage)
            <flux:navbar.item href="{{ route('landing_page') }}#services">Layanan</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#how-it-works">Cara Kerja</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#for-partners">Untuk Mitra</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#faq">FAQ</flux:navbar.item>
        @elseif (!$isLandingPage)
            <flux:navbar.item href="{{ route('landing_page') }}#services">Layanan</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#how-it-works">Cara Kerja</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#for-partners">Untuk Mitra</flux:navbar.item>
            <flux:navbar.item href="{{ route('landing_page') }}#faq">FAQ</flux:navbar.item>
        @endif
    </flux:navbar>
    <flux:spacer />
    @if ($clientUser)
        <flux:navbar class="me-4">
            <flux:navbar.item icon="bell" href="#" label="Notifications" aria-label="Notifications" />
        </flux:navbar>
        <flux:dropdown position="bottom" align="end">
            <button type="button" aria-label="Menu akun {{ $clientUsername }}" class="pawpaw-account-trigger">
                <x-client.components.account-avatar :user="$clientUser" />
            </button>
            <flux:menu class="pawpaw-account-menu">
                <div class="pawpaw-account-identity">
                    <x-client.components.account-avatar :user="$clientUser" class="pawpaw-account-avatar" />
                    <div class="pawpaw-account-copy">
                        <p class="pawpaw-account-name">{{ $clientUser->name ?: $clientUsername }}</p>
                        <p class="pawpaw-account-label">Akun Pelanggan</p>
                    </div>
                </div>
                <flux:menu.item icon="user" href="{{ route('client.profile') }}">Profil Saya</flux:menu.item>
                <flux:menu.item icon="calendar-days" href="{{ route('client.my-bookings') }}">Reservasi saya &amp;
                    Riwayat</flux:menu.item>
                <flux:menu.item icon="cog-6-tooth">Settings</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ $clientLogoutUrl }}">
                    @csrf
                    <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle" class="pawpaw-account-logout">
                        Keluar</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    @else
        <div class="clinic-header-auth max-sm:hidden">
            <a href="{{ $clientLoginUrl }}" class="clinic-header-login">Masuk Pelanggan</a>
            <a href="{{ route('vendor.register') }}" class="clinic-header-partner">Gabung Jadi Mitra</a>
        </div>
    @endif
</flux:header>
<flux:sidebar sticky collapsible="mobile"
    class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <flux:sidebar.brand href="{{ route('landing_page') }}" logo="{{ asset('assets/img/pawpaw-logo.webp') }}"
            name="" class="min-w-0 flex-1" />
        <flux:sidebar.collapse />
    </flux:sidebar.header>
    <flux:sidebar.nav>
        @if ($clientUser && !$isLandingPage)
            <flux:sidebar.item icon="building-office-2" href="{{ route('client.clinic') }}"
                :current="request()->routeIs('client.clinic', 'client.vendor.detail', 'client.clinic.booking')">Klinik
            </flux:sidebar.item>
            <flux:sidebar.item icon="calendar-days" href="{{ route('client.my-bookings') }}"
                :current="request()->routeIs('client.my-bookings')">Reservasi saya &amp; Riwayat</flux:sidebar.item>
        @elseif ($isLandingPage && $clientUser)
            <flux:sidebar.item icon="squares-2x2" href="{{ route('client.dashboard') }}"
                :current="request()->routeIs('client.dashboard')">Dashboard</flux:sidebar.item>
            <flux:sidebar.item icon="heart" href="{{ route('landing_page') }}#services">Layanan</flux:sidebar.item>
            <flux:sidebar.item icon="information-circle" href="{{ route('landing_page') }}#how-it-works">Cara Kerja
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-office-2" href="{{ route('landing_page') }}#for-partners">Untuk Mitra
            </flux:sidebar.item>
            <flux:sidebar.item icon="question-mark-circle" href="{{ route('landing_page') }}#faq">FAQ
            </flux:sidebar.item>
        @elseif ($isLandingPage)
            <flux:sidebar.item icon="heart" href="{{ route('landing_page') }}#services">Layanan</flux:sidebar.item>
            <flux:sidebar.item icon="information-circle" href="{{ route('landing_page') }}#how-it-works">Cara Kerja
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-office-2" href="{{ route('landing_page') }}#for-partners">Untuk Mitra
            </flux:sidebar.item>
            <flux:sidebar.item icon="question-mark-circle" href="{{ route('landing_page') }}#faq">FAQ
            </flux:sidebar.item>
        @elseif (!$isLandingPage)
            <flux:sidebar.item icon="heart" href="{{ route('landing_page') }}#services">Layanan</flux:sidebar.item>
            <flux:sidebar.item icon="information-circle" href="{{ route('landing_page') }}#how-it-works">Cara Kerja
            </flux:sidebar.item>
            <flux:sidebar.item icon="building-office-2" href="{{ route('landing_page') }}#for-partners">Untuk Mitra
            </flux:sidebar.item>
            <flux:sidebar.item icon="question-mark-circle" href="{{ route('landing_page') }}#faq">FAQ
            </flux:sidebar.item>
        @endif
    </flux:sidebar.nav>
    <flux:sidebar.spacer />
    <flux:sidebar.nav>
        @if ($clientUser)
            <flux:sidebar.item icon="user" href="{{ route('client.profile') }}">Profil Saya</flux:sidebar.item>
        @else
            <flux:sidebar.item icon="user" href="{{ route('client.login') }}">Masuk Pelanggan</flux:sidebar.item>
            <flux:sidebar.item icon="building-office-2" href="{{ route('vendor.register') }}">Gabung Jadi Mitra
            </flux:sidebar.item>
        @endif
    </flux:sidebar.nav>
</flux:sidebar>
