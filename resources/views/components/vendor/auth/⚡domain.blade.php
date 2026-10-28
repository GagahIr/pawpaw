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
            $this->addError('vendor_name', 'Format nama vendor tidak valid.');

            return null;
        }

        try {
            $vendor = Vendor::where('slug', $slug)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($cleaned)])
                ->orWhere('name', $cleaned)
                ->first();

            if ($vendor) {
                $slug = $vendor->slug;
            }
        } catch (\Throwable) {
            // Keep derived slug if database query fails
        }

        $url = route('vendor.login', ['subdomain' => $slug]);

        return redirect()->away($url);
    }
};
?>

<div class="min-h-screen bg-gradient-to-b dark:from-zinc-950 dark:to-zinc-900" x-data="{
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
    <div class="flex min-h-screen">
        <div class="flex-1 flex justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-md space-y-6">

                {{-- Tombol Kembali --}}
                <flux:link href="{{ route('landing_page') }}" variant="subtle"
                    class="inline-flex items-center gap-1 text-sm">
                    <flux:icon.arrow-left variant="micro" />
                    Kembali ke Beranda
                </flux:link>

                <div class="text-center">
                    <flux:heading size="xl">Masuk ke Akun</flux:heading>
                    <flux:subheading>Masuk melalui domain klinik Anda</flux:subheading>
                </div>

                <flux:card class="space-y-6">
                    <form wire:submit="submit" class="space-y-5">
                        <div>
                            <flux:input wire:model.live.debounce.150ms="vendor_name"
                                label="Nama Mitra"
                                type="text"
                                icon="building-storefront"
                                placeholder="Masukkan nama mitra (contoh: klinik-gagah)"
                                autofocus
                                autocomplete="off" />

                            <div x-show="previewUrl" x-cloak class="text-xs px-1 mt-2 text-zinc-500">
                                Domain tujuan: <span class="font-medium text-[#1a5c40] dark:text-[#25825a]" x-text="previewUrl"></span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <flux:button type="submit" variant="primary" class="w-full !bg-[#1a5c40]">
                                Masuk
                            </flux:button>
                        </div>
                    </form>
                </flux:card>

                <flux:text class="text-center text-sm">
                    Klinik Anda belum terdaftar?
                    <flux:link class="!text-[#1a5c40] dark:!text-[#25825a] font-medium"
                        href="{{ route('vendor.register') }}">
                        Daftar sebagai Mitra
                    </flux:link>
                </flux:text>
            </div>
        </div>
    </div>
</div>
