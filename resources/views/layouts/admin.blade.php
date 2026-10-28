<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PawPaw</title>
    {{-- @fluxAppearance --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#FBF8F2]">

    <main class="flex min-h-screen">
        <div class="flex w-full">

            <flux:sidebar sticky collapsible
                class="border-r border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.header class="py-3 overflow-hidden">
                    @php
                        $vendorLogoUrl = auth()->user()->vendor->logo
                            ? Storage::url(auth()->user()->vendor->logo)
                            : '';
                        $vendorInitial = strtoupper(substr(auth()->user()->vendor->name ?? 'V', 0, 1));
                        $vendorName = auth()->user()->vendor->name ?? '';
                    @endphp

                    {{-- Brand: logo + name, collapses cleanly --}}
                    <div
                        x-data="{ logoSrc: '{{ $vendorLogoUrl }}' }"
                        x-on:vendor-logo-updated.window="logoSrc = $event.detail.url"
                        class="flex min-w-0 flex-1 items-center gap-2 overflow-hidden"
                    >
                        {{-- Logo icon — always visible, fixed 36×36 --}}
                        <div class="relative h-9 w-9 shrink-0 rounded-md overflow-hidden border border-zinc-200 bg-zinc-100">
                            <template x-if="logoSrc">
                                <img
                                    :src="logoSrc"
                                    alt="Mitra Logo"
                                    class="absolute inset-0 h-full w-full object-cover"
                                />
                            </template>
                            <span
                                x-show="!logoSrc"
                                class="absolute inset-0 flex items-center justify-center text-white font-bold text-sm bg-[#1B5E43]"
                            >{{ $vendorInitial }}</span>
                        </div>

                        {{-- Text — hidden when sidebar is collapsed --}}
                        <div class="min-w-0 flex-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                            <p class="truncate text-sm font-semibold text-zinc-800 leading-tight">{{ $vendorName }}</p>
                            <p class="text-[0.6rem] font-bold tracking-widest text-[#1B5E43] uppercase leading-tight mt-0.5">PARTNER PORTAL</p>
                        </div>
                    </div>

                    <flux:sidebar.collapse class="shrink-0 in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
                </flux:sidebar.header>

                <flux:sidebar.nav>
                    <flux:sidebar.item icon="squares-2x2" href="{{ route('vendor.admin.dashboard') }}" :current="request()->routeIs('vendor.admin.dashboard')">Dashboard</flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" href="{{ route('vendor.admin.operations') }}" :current="request()->routeIs('vendor.admin.operations*')">Mitra Operations</flux:sidebar.item>
                    <flux:sidebar.item icon="rectangle-stack" href="{{ route('vendor.admin.services') }}" :current="request()->routeIs('vendor.admin.services*')">Mitra Services</flux:sidebar.item>
                    <flux:sidebar.item icon="building-storefront" href="{{ route('vendor.admin.profile.profile-vendor') }}" :current="request()->routeIs('vendor.admin.profile*')">Profile Mitra</flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" href="{{ route('vendor.admin.manage_staff') }}" :current="request()->routeIs('vendor.admin.manage_staff*')">Manage Staff</flux:sidebar.item>
                </flux:sidebar.nav>

                <flux:sidebar.group expandable heading="Favorites" class="grid">
                    <flux:sidebar.item href="#">Marketing site</flux:sidebar.item>
                    <flux:sidebar.item href="#">Android app</flux:sidebar.item>
                    <flux:sidebar.item href="#">Brand guidelines</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar>

            <flux:header container
                class="border-b border-zinc-200 bg-white lg:bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.toggle class="me-4 lg:hidden" icon="bars-2" inset="left" />

                {{-- Dynamic breadcrumb based on current route --}}
                @php
                    $breadcrumbs = match(true) {
                        request()->routeIs('vendor.admin.dashboard')              => [['label' => 'Dashboard']],
                        request()->routeIs('vendor.admin.operations')             => [['label' => 'Mitra Operations']],
                        request()->routeIs('vendor.admin.services.manage')        => [['label' => 'Mitra Services', 'href' => route('vendor.admin.services')], ['label' => 'Kelola Layanan']],
                        request()->routeIs('vendor.admin.services')               => [['label' => 'Mitra Services']],
                        request()->routeIs('vendor.admin.profile.profile-vendor') => [['label' => 'Profile Mitra']],
                        request()->routeIs('vendor.admin.manage_staff')           => [['label' => 'Manage Staff']],
                        default                                                   => [['label' => 'Dashboard']],
                    };
                @endphp
                <flux:breadcrumbs class="max-sm:hidden">
                    <flux:breadcrumbs.item href="{{ route('vendor.admin.dashboard') }}">Mitra Portal</flux:breadcrumbs.item>
                    @foreach ($breadcrumbs as $crumb)
                        @if (isset($crumb['href']))
                            <flux:breadcrumbs.item href="{{ $crumb['href'] }}">{{ $crumb['label'] }}</flux:breadcrumbs.item>
                        @else
                            <flux:breadcrumbs.item>{{ $crumb['label'] }}</flux:breadcrumbs.item>
                        @endif
                    @endforeach
                </flux:breadcrumbs>

                <flux:spacer />

                <flux:navbar class="me-4">
                    <flux:navbar.item icon="cog-6-tooth" href="#" label="Settings" aria-label="Settings" />
                    <flux:navbar.item icon="bell" href="#" label="Notifications" aria-label="Notifications" />
                </flux:navbar>

                <flux:dropdown position="top" align="start">
                    <flux:profile avatar="https://fluxui.dev/img/demo/user.png" aria-label="Account menu" />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <flux:menu.radio checked>{{ auth()->user()->name }}</flux:menu.radio>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('vendor.admin.logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">
                                Logout
                            </flux:menu.item>
                        </form>

                    </flux:menu>
                </flux:dropdown>
            </flux:header>

            <flux:main container>
                {{ $slot }}
            </flux:main>
        </div>
    </main>

    @livewireScripts
    @fluxScripts
    <flux:toast position="top end" class="pt-4" />
</body>

</html>