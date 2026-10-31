<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PawPaw Super Admin</title>
    {{-- @fluxAppearance --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#FFF]">

    @php
        $admin = auth('web')->user();

        $breadcrumbs = match (true) {
            request()->routeIs('super_admin.dashboard') => [['label' => 'Dashboard']],
            request()->routeIs('super_admin.manage_vendors') => [['label' => 'Manage Mitra']],
            default => [['label' => 'Dashboard']],
        };
    @endphp

    <main class="flex min-h-screen">
        <div class="flex w-full">

            <flux:sidebar sticky collapsible
                class="border-r border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.header class="py-3 overflow-hidden">
                    <div class="flex min-w-0 flex-1 items-center gap-2 overflow-hidden">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[#1B5E43] text-sm font-bold text-white">
                            P
                        </div>

                        <div class="min-w-0 flex-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                            <p class="truncate text-sm font-semibold leading-tight text-zinc-800">PawPaw</p>
                            <p
                                class="mt-0.5 text-[0.6rem] font-bold uppercase leading-tight tracking-widest text-[#1B5E43]">
                                Super Admin</p>
                        </div>
                    </div>

                    <flux:sidebar.collapse
                        class="shrink-0 in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
                </flux:sidebar.header>

                <flux:sidebar.nav>
                    <flux:sidebar.item icon="squares-2x2" href="{{ route('super_admin.dashboard') }}"
                        :current="request()->routeIs('super_admin.dashboard')">Dashboard</flux:sidebar.item>

                    <flux:sidebar.item icon="cog-6-tooth" href="{{ route('super_admin.manage_vendors') }}"
                        :current="request()->routeIs('super_admin.manage_vendors')">Manage Mitra</flux:sidebar.item>

                    <flux:sidebar.item icon="cog-6-tooth" href="{{ route('super_admin.verification_vendors') }}"
                        :current="request()->routeIs('super_admin.verification_vendors')">Verification Mitra</flux:sidebar.item>
                </flux:sidebar.nav>
            </flux:sidebar>

            <flux:header container
                class="border-b border-zinc-200 bg-white lg:bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.toggle class="me-4 lg:hidden" icon="bars-2" inset="left" />

                <flux:breadcrumbs class="max-sm:hidden">
                    <flux:breadcrumbs.item href="{{ route('super_admin.dashboard') }}">Super Admin
                    </flux:breadcrumbs.item>
                    @foreach ($breadcrumbs as $crumb)
                        <flux:breadcrumbs.item>{{ $crumb['label'] }}</flux:breadcrumbs.item>
                    @endforeach
                </flux:breadcrumbs>

                <flux:spacer />

                <flux:navbar class="me-4">
                    <flux:navbar.item icon="bell" href="#" label="Notifications" aria-label="Notifications" />
                </flux:navbar>

                <flux:dropdown position="top" align="start">
                    <flux:profile avatar="https://fluxui.dev/img/demo/user.png" aria-label="Account menu" />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <flux:menu.radio checked>{{ $admin?->name }}</flux:menu.radio>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('super_admin.logout') }}">
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
