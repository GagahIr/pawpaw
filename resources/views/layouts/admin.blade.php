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

<body class="bg-[#FFFF]">

    <main>
        <div>
            <flux:sidebar sticky collapsible
                class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.header>
                    <flux:dropdown class="min-w-0 flex-1">
                        <flux:sidebar.brand as="button" logo="https://fluxui.dev/img/demo/logo.png"
                            logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png" name="acme.co"
                            icon:trailing="chevron-down" class="w-full" />

                        <flux:menu class="min-w-56">
                            <flux:menu.radio.group>
                                <flux:menu.radio value="acme.co" checked>acme.co</flux:menu.radio>
                                {{-- <flux:menu.radio value="shop.acme.co">shop.acme.co</flux:menu.radio>
                                <flux:menu.radio value="docs.acme.co">docs.acme.co</flux:menu.radio> --}}
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            {{-- <flux:menu.item icon="plus">Add property</flux:menu.item> --}}
                        </flux:menu>
                    </flux:dropdown>

                    <flux:sidebar.collapse
                        class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
                </flux:sidebar.header>

                {{-- <flux:sidebar.search placeholder="Search..." /> --}}

                <flux:sidebar.nav>
                    <flux:sidebar.item icon="squares-2x2" href="{{ route('vendor.admin.dashboard') }}" current>Dashboard</flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" href="{{ route('vendor.admin.operations') }}">Vendor Operations</flux:sidebar.item>
                    <flux:sidebar.item icon="rectangle-stack" href="{{ route('vendor.admin.services') }}">Vendor Services</flux:sidebar.item>
                    <flux:sidebar.item icon="building-storefront" href="{{ route('vendor.admin.profile.profile-vendor') }}"> Profile Vendor</flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" href="{{ route('vendor.admin.manage_staff') }}">Manage Staff</flux:sidebar.item>
                </flux:sidebar.nav>

                <flux:sidebar.group expandable heading="Favorites" class="grid">
                    <flux:sidebar.item href="#">Marketing site</flux:sidebar.item>
                    <flux:sidebar.item href="#">Android app</flux:sidebar.item>
                    <flux:sidebar.item href="#">Brand guidelines</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar>

            <flux:header container
                class="border-b border-zinc-200 bg-white lg:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.toggle class="me-4 lg:hidden" icon="bars-2" inset="left" />

                <flux:breadcrumbs class="max-sm:hidden">
                    <flux:breadcrumbs.item href="#">Analytics</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Overview</flux:breadcrumbs.item>
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
                            <flux:menu.radio checked>Olivia Martin</flux:menu.radio>
                            <flux:menu.radio>Truly Delta</flux:menu.radio>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.item icon="arrow-right-start-on-rectangle">
                            <form method="POST" action="{{ route('vendor.logout') }}">
                                @csrf
                                <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle">Logout
                                </flux:button>
                            </form>
                        </flux:menu.item>
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
