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

<body class="bg-[#FFF8F5]">

    <div>
        <flux:header container
            class="border-b border-zinc-200 bg-white lg:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Open navigation" />
            <flux:brand href="#" logo="{{ asset('assets/img/pawpaw-logo.webp') }}"
                class="max-lg:hidden dark:hidden" />
            {{-- <flux:brand href="#" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="Acme Bank"
                class="max-lg:hidden! hidden dark:flex" /> --}}
            <flux:navbar class="max-lg:hidden">
                <flux:navbar.item href="{{ route('client.clinic') }}" current>Klinik</flux:navbar.item>
                {{-- <flux:navbar.item href="#">Transfers</flux:navbar.item> --}}
                <flux:navbar.item href="#">Riwayat</flux:navbar.item>
                {{-- <flux:navbar.item href="#">Statements</flux:navbar.item> --}}
            </flux:navbar>
            <flux:spacer />
            <flux:navbar class="me-4">
                <flux:navbar.item icon="bell" href="#" label="Notifications" aria-label="Notifications" />
            </flux:navbar>
            <flux:dropdown position="bottom" align="end">
                <flux:profile avatar="https://fluxui.dev/img/demo/user.png" aria-label="Account menu" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <flux:menu.radio>Gagah</flux:menu.radio>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.item class="max-lg:hidden" icon="cog-6-tooth">Settings</flux:menu.item>
                    <flux:menu.item>
                        <form method="POST" action="{{ route('client.logout') }}">
                            @csrf
                            <flux:button type="submit" class="w-full" variant="primary">Logout</flux:button>
                        </form>
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>
        <flux:sidebar sticky collapsible="mobile"
            class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand href="#" logo="https://fluxui.dev/img/demo/logo.png"
                    logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png" name="Acme Bank"
                    class="min-w-0 flex-1" />
                <flux:sidebar.collapse />
            </flux:sidebar.header>
            <flux:sidebar.nav>
                <flux:sidebar.item icon="wallet" href="{{ route('client.clinic') }}" current>Klinik</flux:sidebar.item>
                {{-- <flux:sidebar.item icon="arrows-right-left" href="#">Transfers</flux:sidebar.item> --}}
                <flux:sidebar.item icon="calendar-days" href="#">Riwayat</flux:sidebar.item>
                {{-- <flux:sidebar.item icon="document-text" href="#">Statements</flux:sidebar.item> --}}
            </flux:sidebar.nav>
            <flux:sidebar.spacer />
            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
                <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>
        <flux:main container>
            {{ $slot }}

        </flux:main>
    </div>


    @livewireScripts
    @fluxScripts
    <x-client.auth.toast />
</body>

</html>
