<?php

use App\Models\Vendor;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    #[Validate('required', message: 'Nama mitra wajib diisi.')]
    public string $vendor_name = '';

    /**
     * Submit and redirect to vendor subdomain login page.
     *
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function submit()
    {
        $this->validate();

        $input = trim($this->vendor_name);

        $cleaned = preg_replace('#^https?://#i', '', $input);
        $cleaned = preg_replace('#[/\\?].*$#', '', $cleaned);
        $cleaned = preg_replace('#:[0-9]+$#', '', $cleaned);

        $appDomain = config('app.domain', 'localhost');
        if (str_ends_with(strtolower($cleaned), '.'.strtolower($appDomain))) {
            $cleaned = substr($cleaned, 0, -strlen('.'.$appDomain));
        }

        $slug = Str::slug($cleaned);

        if (empty($slug)) {
            $this->addError('vendor_name', 'Domain mitra tidak ditemukan');

            return null;
        }

        try {
            $vendor = Vendor::where('slug', $slug)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($cleaned)])
                ->orWhere('name', $cleaned)
                ->first();
        } catch (\Throwable) {
            $vendor = null;
        }

        if (! $vendor) {
            $this->addError('vendor_name', 'Domain mitra tidak ditemukan');

            return null;
        }

        $url = route('vendor.login', ['subdomain' => $vendor->slug]);

        return redirect()->away($url);
    }
};
?>

<div class="min-h-[calc(100vh-14rem)] flex flex-col justify-center items-center pt-6 sm:pt-10 pb-16 px-4 sm:px-6 lg:px-8 bg-[#FFF8F5] dark:bg-zinc-950 transition-colors"
    x-data="{
        vendorName: $wire.entangle('vendor_name'),
        appDomain: '{{ config('app.domain', 'localhost') }}',
        get slug() {
            if (!this.vendorName) return '';
            let cleaned = this.vendorName.toString().trim().toLowerCase()
                .replace(/^https?:\/\//, '')
                .replace(/[/\\?].*$/, '')
                .replace(/:\d+$/, '');
            if (cleaned.endsWith('.' + this.appDomain.toLowerCase())) {
                cleaned = cleaned.slice(0, -(this.appDomain.length + 1));
            }
            return cleaned
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        },
        get previewUrl() {
            if (!this.slug) return '';
            let port = '{{ request()->getPort() !== 80 && request()->getPort() !== 443 ? ':' . request()->getPort() : '' }}';
            return this.slug + '.' + this.appDomain + port + '/login';
        }
    }">
    <div class="w-full max-w-md mx-auto space-y-6 -mt-4 sm:-mt-10">

        {{-- Tombol Kembali --}}
        <div>
            <flux:link href="{{ route('landing_page') }}" variant="subtle"
                class="inline-flex items-center gap-1.5 text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors">
                <flux:icon.arrow-left variant="micro" />
                <span>Kembali ke Beranda</span>
            </flux:link>
        </div>

        {{-- Heading & Subheading --}}
        <div class="text-center space-y-2">
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-white">
                Masuk ke Akun
            </flux:heading>
            <flux:subheading class="text-sm text-zinc-600 dark:text-zinc-400 max-w-sm mx-auto">
                Masuk melalui domain klinik Anda
            </flux:subheading>
        </div>

        {{-- Card Form --}}
        <flux:card class="space-y-6 rounded-2xl bg-white dark:bg-zinc-900 shadow-sm border border-zinc-200/80 dark:border-zinc-800 p-6 sm:p-8">
            <form wire:submit="submit" class="space-y-5">
                <div class="space-y-2">
                    <flux:input wire:model.live.debounce.150ms="vendor_name"
                        label="Nama Mitra"
                        type="text"
                        icon="building-storefront"
                        placeholder="Masukkan nama mitra (contoh: klinik-gagah)"
                        autofocus
                        autocomplete="off" />

                    {{-- Live Preview Domain Tujuan --}}
                    <div x-show="previewUrl" x-cloak x-transition
                        class="mt-3 flex items-center gap-2.5 rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-3 text-xs text-zinc-600 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-zinc-300">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-[#1B5E43] text-white shadow-xs">
                            <flux:icon.globe-alt class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Domain tujuan:</p>
                            <p class="truncate font-semibold text-[#1B5E43] dark:text-emerald-400 text-xs sm:text-sm" x-text="previewUrl"></p>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <flux:button type="submit" variant="primary"
                        class="w-full !bg-[#1B5E43] hover:!bg-[#154a35] focus:!bg-[#154a35] text-white font-medium shadow-xs transition-colors py-2.5 rounded-xl"
                        wire:loading.attr="disabled"
                        wire:target="submit">
                        <span wire:loading.remove wire:target="submit">Masuk</span>
                        <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>Memeriksa domain...</span>
                        </span>
                    </flux:button>
                </div>
            </form>
        </flux:card>

        {{-- Link Pendaftaran Mitra --}}
        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>Klinik Anda belum terdaftar?</span>
            <flux:link class="font-semibold !text-[#1B5E43] hover:underline dark:!text-[#25825a] ml-1"
                href="{{ route('vendor.register') }}">
                Daftar sebagai Mitra
            </flux:link>
        </div>
    </div>
</div>
