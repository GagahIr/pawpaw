<?php

use App\Actions\Vendor\RegisterVendorAction;
use App\DTOs\RegisterVendorData;
use App\Enums\SystemMessage;
use Livewire\Component;

new class extends Component {
    public string $vendor_name = '';
    public string $address = '';
    public string $contact = '';
    public string $email_vendor = '';
    public string $email = '';
    public string $description = '';
    public string $owner_name = '';
    public string $phone_number = '';
    public string $password = '';

    public int $currentStep = 1;

    public array $steps = [
        1 => 'Vendor',
        2 => 'Pemilik & Akun',
        3 => 'Konfirmasi',
    ];

    protected function rules(): array
    {
        return [
            'vendor_name' => ['required', 'string', 'max:255', 'unique:vendors,name'],
            'address' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'min:10', 'max:20', 'unique:vendors,contact', 'unique:users,phone_number'],
            'email_vendor' => ['required', 'string', 'email', 'max:255', 'unique:vendors,email', 'unique:users,email'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:vendors,email'],
            'description' => ['nullable', 'string', 'max:1000'],
            'owner_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'min:10', 'max:20', 'unique:users,phone_number', 'unique:vendors,contact'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function mount()
    {
        // if (auth()->guard('owner')->check()) {
        //     return redirect()->route('filament.owner.pages.dashboard');
        // }

        // if (auth()->guard('front-office')->check()) {
        //     // Sesuaikan nama route dashboard front-office Anda
        //     return redirect()->route('filament.front-office.pages.dashboard');
        // }

        if (auth()->check()) {
            $user = auth()->user();

            if ($user->role === 'owner') {
                return redirect()->route('filament.owner.pages.dashboard');
            } elseif ($user->role === 'front_office') {
                return redirect()->route('filament.front-office.pages.dashboard');
            } else {
                return redirect('/');
            }
        }
    }

    protected function stepFields(int $step): array
    {
        return match ($step) {
            1 => ['vendor_name', 'contact', 'email_vendor', 'address', 'description'],
            2 => ['owner_name', 'phone_number', 'email', 'password'],
            default => [],
        };
    }

    public function nextStep(): void
    {
        $this->validate(
            collect($this->rules())
                ->only($this->stepFields($this->currentStep))
                ->all(),
        );

        if ($this->currentStep < count($this->steps)) {
            $this->currentStep++;
        }
    }

    public function register(RegisterVendorAction $action)
    {
        $this->validate();

        $data = new RegisterVendorData(vendorName: $this->vendor_name, address: $this->address, contact: $this->contact, emailVendor: $this->email_vendor, email: $this->email, description: $this->description, ownerName: $this->owner_name, phoneNumber: $this->phone_number, password: $this->password);

        try {
            $action->execute($data);
            // session()->flash('success', SystemMessage::VENDOR_REGISTER_SUCCESS->value);
            notify('Success', SystemMessage::VENDOR_REGISTER_SUCCESS->value, 'success');
            return redirect()->route('filament.owner.pages.dashboard');
        } catch (\Throwable $e) {
            report($e);
            notify('Error', SystemMessage::ERROR_SYSTEM->value, 'danger');
            // session()->flash('error', SystemMessage::ERROR_SYSTEM->value);
        }
    }
};
?>

<div x-data="{
    step: @entangle('currentStep'),
    f: {
        email_vendor: $wire.email_vendor ?? '',
        contact: $wire.contact ?? '',
        email: $wire.email ?? '',
        phone_number: $wire.phone_number ?? '',
    },
    normEmail(v) { return (v || '').trim().toLowerCase(); },
    normPhone(v) {
        const d = (v || '').replace(/\D/g, '');
        return d.startsWith('62') ? '0' + d.slice(2) : d;
    },
    get emailConflict() {
        const a = this.normEmail(this.f.email_vendor),
            b = this.normEmail(this.f.email);
        return a !== '' && a === b;
    },
    get phoneConflict() {
        const a = this.normPhone(this.f.contact),
            b = this.normPhone(this.f.phone_number);
        return a !== '' && a === b;
    },
    get hasConflict() { return this.emailConflict || this.phoneConflict; },
}">
    <div class="min-h-screen bg-gradient-to-b dark:from-zinc-950 dark:to-zinc-900">
        <div class="flex min-h-screen">
            <div class="flex-1 flex justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-xl space-y-6">

                    <flux:link href="/" variant="subtle" class="inline-flex items-center gap-1 text-sm">
                        <flux:icon.arrow-left variant="micro" />
                        Kembali ke Beranda
                    </flux:link>

                    {{-- <div class="flex flex-col items-center justify-center">
                        <img class="w-28 sm:w-36 h-auto" src="{{ asset('assets/img/pawpaw-logo.webp') }}"
                            alt="PawPaw">
                    </div> --}}
                    <div class="text-center">
                        <flux:heading size="xl">Pendaftaran Vendor</flux:heading>
                        <flux:subheading
                            x-text="step === 1 ? 'Ceritakan tentang bisnis pet care Anda.' : step === 2 ? 'Lengkapi data pemilik dan akun untuk masuk.' : 'Periksa kembali data sebelum menyelesaikan pendaftaran.'">
                            Ceritakan tentang bisnis pet care Anda.
                        </flux:subheading>
                    </div>

                    <div class="space-y-6 px-0 sm:px-6 lg:px-8">
                        <nav aria-label="Langkah pendaftaran">
                            <div class="flex items-center">
                                @foreach ($steps as $num => $label)
                                    <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-colors"
                                        :class="{
                                            'bg-[#1a5c40] text-white': step > {{ $num }},
                                        
                                            'bg-[#1a5c40] text-white ring-4 ring-[#1a5c40]/30': step ===
                                                {{ $num }},
                                        
                                            'border border-zinc-200 text-zinc-400 dark:border-zinc-700 dark:text-zinc-600': step <
                                                {{ $num }},
                                        }"
                                        :aria-current="step === {{ $num }} ? 'step' : null">

                                        <span x-show="step > {{ $num }}" x-cloak>
                                            <flux:icon.check variant="micro" />
                                        </span>
                                        <span x-show="step <= {{ $num }}">{{ $num }}</span>
                                    </div>

                                    @if (!$loop->last)
                                        <div class="mx-2 h-0.5 flex-1 rounded-full transition-colors"
                                            :class="step > {{ $num }} ?
                                                'bg-[#1a5c40]' :
                                                'bg-zinc-200 dark:bg-zinc-700'">
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <div class="mt-1.5 grid grid-cols-3 gap-2">
                                @foreach ($steps as $num => $label)
                                    <span class="text-center text-xs transition-colors"
                                        :class="step === {{ $num }} ?
                                            'font-bold text-[#1a5c40] dark:text-[#25825a]' :
                                            (step > {{ $num }} ? 'font-medium text-zinc-900 dark:text-white' :
                                                'text-zinc-400 dark:text-zinc-600')">
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        </nav>
                    </div>

                    <flux:card class="space-y-8">

                        @if (session('success'))
                            <flux:callout variant="success" icon="check-circle" heading="{{ session('success') }}" />
                        @endif

                        @if (session('error'))
                            <flux:callout variant="danger" icon="x-circle" heading="{{ session('error') }}" />
                        @endif

                        <form wire:submit.prevent="register"
                            x-on:submit.capture="if (hasConflict) { $event.stopImmediatePropagation(); $event.preventDefault(); }"
                            class="space-y-8">

                            {{-- Langkah 1: Informasi Vendor --}}
                            <div class="space-y-5" x-show="step === 1"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0">
                                <flux:heading size="lg" class="flex items-center gap-2">
                                    <flux:icon.building-storefront variant="micro" class="text-zinc-400" />
                                    Informasi Vendor
                                </flux:heading>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <flux:input wire:model.blur="vendor_name" label="Nama Vendor" required
                                        maxlength="100" placeholder="Contoh: Pawtner Pet Grooming"
                                        icon="building-storefront" />

                                    <flux:input wire:model.blur="contact" x-model="f.contact" label="Kontak Vendor"
                                        required minlength="10" maxlength="13" placeholder="08xxxxxxxxxx"
                                        icon="phone" />
                                </div>

                                <flux:input wire:model.blur="email_vendor" x-model="f.email_vendor" label="Email Vendor"
                                    required type="email" placeholder="example@vendor.com" icon="envelope" />

                                <flux:textarea wire:model.blur="address" label="Alamat" rows="3" required
                                    placeholder="Jalan, nomor, kelurahan, kecamatan, kota"
                                    description="Alamat lengkap memudahkan pelanggan menemukan lokasi Anda" />

                                <flux:textarea wire:model.blur="description" label="Deskripsi (Opsional)" rows="3"
                                    placeholder="Ceritakan keunggulan layanan, jam operasional, atau spesialisasi vendor Anda" />
                            </div>

                            {{-- Langkah 2: Informasi Pemilik & Akun --}}
                            <div class="space-y-5" x-show="step === 2"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0">
                                <flux:heading size="lg" class="flex items-center gap-2">
                                    <flux:icon.user-circle variant="micro" class="text-zinc-400" />
                                    Informasi Pemilik & Akun
                                </flux:heading>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <flux:input wire:model.blur="owner_name" label="Nama Pemilik" required
                                        placeholder="Nama lengkap sesuai KTP" icon="user" />

                                    <flux:input wire:model.blur="phone_number" x-model="f.phone_number"
                                        label="No. WhatsApp/Telepon" required minlength="10" maxlength="13"
                                        placeholder="08xxxxxxxxxx" icon="device-phone-mobile" autocomplete="tel" />
                                </div>

                                <p x-show="phoneConflict" x-cloak class="text-sm text-red-600">
                                    Nomor telepon pemilik tidak boleh sama dengan kontak vendor.
                                </p>

                                <flux:input wire:model.blur="email" x-model="f.email" type="email" label="Email"
                                    required placeholder="email@example.com" icon="envelope" autocomplete="email" />

                                <p x-show="emailConflict" x-cloak class="text-sm text-red-600">
                                    Email pemilik tidak boleh sama dengan email vendor.
                                </p>

                                <flux:input wire:model.blur="password" type="password" label="Password" required
                                    placeholder="Minimal 8 karakter" icon="lock-closed" viewable
                                    description="Gunakan kombinasi huruf, angka, dan simbol agar lebih aman"
                                    autocomplete="new-password" />
                            </div>

                            {{-- Langkah 3: Konfirmasi --}}
                            <div class="space-y-5" x-show="step === 3"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0">
                                <flux:heading size="lg" class="flex items-center gap-2">
                                    <flux:icon.clipboard-document-check variant="micro" class="text-zinc-400" />
                                    Konfirmasi Data
                                </flux:heading>

                                <div
                                    class="rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700 overflow-hidden">
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <flux:text class="font-medium">Informasi Vendor</flux:text>
                                            <flux:link href="#" x-on:click.prevent="step = 1" variant="subtle"
                                                class="text-sm">Ubah</flux:link>
                                        </div>
                                        <dl class="space-y-2">
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Nama Vendor</dt>
                                                <dd class="text-sm text-zinc-800 dark:text-zinc-100 col-span-2">
                                                    {{ $vendor_name ?: '-' }}</dd>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Kontak</dt>
                                                <dd class="text-sm text-zinc-800 dark:text-zinc-100 col-span-2">
                                                    {{ $contact ?: '-' }}</dd>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Vendor Email</dt>
                                                <dd class="text-sm text-zinc-800 dark:text-zinc-100 col-span-2">
                                                    {{ $email_vendor ?: '-' }}</dd>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Alamat</dt>
                                                <dd class="text-sm text-zinc-800 dark:text-zinc-100 col-span-2">
                                                    {{ $address ?: '-' }}</dd>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Deskripsi</dt>
                                                <dd
                                                    class="text-sm col-span-2 {{ $description ? 'text-zinc-800 dark:text-zinc-100' : 'italic text-zinc-400' }}">
                                                    {{ $description ?: 'Tidak ada deskripsi' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <flux:text class="font-medium">Informasi Pemilik & Akun</flux:text>
                                            <flux:link href="#" x-on:click.prevent="step = 2" variant="subtle"
                                                class="text-sm">Ubah</flux:link>
                                        </div>
                                        <dl class="space-y-2">
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Nama Pemilik</dt>
                                                <dd class="text-sm text-zinc-800 dark:text-zinc-100 col-span-2">
                                                    {{ $owner_name ?: '-' }}</dd>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">No. Telepon</dt>
                                                <dd class="text-sm text-zinc-800 dark:text-zinc-100 col-span-2">
                                                    {{ $phone_number ?: '-' }}</dd>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Email</dt>
                                                <dd class="text-sm text-zinc-800 dark:text-zinc-100 col-span-2">
                                                    {{ $email ?: '-' }}</dd>
                                            </div>
                                            <div class="grid grid-cols-3 gap-4">
                                                <dt class="text-sm text-zinc-500 col-span-1">Password</dt>
                                                <dd
                                                    class="text-sm col-span-2 flex items-center gap-1.5 text-zinc-800 dark:text-zinc-100">
                                                    <flux:icon.check-circle variant="micro" class="text-zinc-400" />
                                                    Sudah diisi
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <flux:callout variant="secondary" icon="information-circle"
                                    heading="Pastikan data di atas sudah benar sebelum mendaftar" />
                            </div>

                            <flux:separator variant="subtle" />

                            <div class="flex items-center gap-3">
                                <flux:button type="button" x-show="step > 1" x-on:click="step = step - 1"
                                    variant="subtle" icon="chevron-left">
                                    Sebelumnya
                                </flux:button>

                                <flux:button type="button" wire:click="nextStep"
                                    x-show="step < {{ count($steps) }}" x-bind:disabled="hasConflict"
                                    variant="primary" icon:trailing="chevron-right" class="flex-1 !bg-[#1a5c40]">
                                    Selanjutnya
                                </flux:button>

                                <flux:button type="submit" x-show="step === {{ count($steps) }}"
                                    x-bind:disabled="hasConflict" variant="primary" class="flex-1 !bg-[#1a5c40]">
                                    Daftar Sekarang
                                </flux:button>
                            </div>
                        </form>
                    </flux:card>

                    <flux:text class="text-center">
                        Sudah memiliki akun? <flux:link class="!text-[#1a5c40]"
                            href="{{ route('filament.owner.pages.dashboard') }}">Masuk
                            di sini</flux:link>
                    </flux:text>
                </div>
            </div>

            {{-- Jika ingin menggunakan gambar background di sisi kanan, hilangkan komentar di bawah --}}
            {{--
        <div class="flex-1 p-4 max-lg:hidden">
            <div class="text-white relative rounded-lg h-full w-full bg-zinc-900 flex flex-col items-start justify-end p-16"
                style="background-image: url('https://fluxui.dev/img/demo/auth_aurora_2x.png'); background-size: cover">
                <div class="mb-6 italic font-base text-3xl xl:text-4xl">
                    Bergabunglah dengan PawPaw dan kembangkan bisnis pet care Anda bersama kami.
                </div>
            </div>
        </div>
        --}}
        </div>
    </div>
</div>
