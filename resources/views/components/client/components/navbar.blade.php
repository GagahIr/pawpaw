<div>
    <flux:header container class="border-b border-zinc-200 bg-white lg:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Open navigation" />
        <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="Acme Bank" class="max-lg:hidden dark:hidden" />
        <flux:brand href="#" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="Acme Bank" class="max-lg:hidden! hidden dark:flex" />
        <flux:navbar class="max-lg:hidden">
            <flux:navbar.item href="#" current>Accounts</flux:navbar.item>
            <flux:navbar.item href="#">Transfers</flux:navbar.item>
            <flux:navbar.item href="#">Payments</flux:navbar.item>
            <flux:navbar.item href="#">Statements</flux:navbar.item>
        </flux:navbar>
        <flux:spacer />
        <flux:navbar class="me-4">
            <flux:navbar.item icon="magnifying-glass" href="#" label="Search" aria-label="Search" />
            <flux:navbar.item icon="bell" href="#" label="Notifications" aria-label="Notifications" />
        </flux:navbar>
        <flux:dropdown position="bottom" align="end">
            <flux:profile avatar="https://fluxui.dev/img/demo/user.png" aria-label="Account menu" />
            <flux:menu>
                <flux:menu.radio.group>
                    <flux:menu.radio checked>Olivia Martin</flux:menu.radio>
                    <flux:menu.radio>Truly Delta</flux:menu.radio>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <flux:menu.item class="max-lg:hidden" icon="cog-6-tooth">Settings</flux:menu.item>
                <flux:menu.item class="max-lg:hidden" icon="information-circle">Help</flux:menu.item>
                <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:header>
    <flux:sidebar sticky collapsible="mobile" class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand
                href="#"
                logo="https://fluxui.dev/img/demo/logo.png"
                logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
                name="Acme Bank"
                class="min-w-0 flex-1"
            />
            <flux:sidebar.collapse />
        </flux:sidebar.header>
        <flux:sidebar.search placeholder="Search..." />
        <flux:sidebar.nav>
            <flux:sidebar.item icon="wallet" href="#" current>Accounts</flux:sidebar.item>
            <flux:sidebar.item icon="arrows-right-left" href="#">Transfers</flux:sidebar.item>
            <flux:sidebar.item icon="calendar-days" href="#">Payments</flux:sidebar.item>
            <flux:sidebar.item icon="document-text" href="#">Statements</flux:sidebar.item>
        </flux:sidebar.nav>
        <flux:sidebar.spacer />
        <flux:sidebar.nav>
            <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
            <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>
    <flux:main container>
        
    </flux:main>
</div>