<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Enums\SystemMessage;

new #[Layout('layouts.app')] class extends Component {
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public int $retryAfter = 0;

    public function mount(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $this->retryAfter = RateLimiter::availableIn($this->throttleKey());
        }
    }

    public function login()
    {
        $this->validate();

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            event(new Lockout(request()));
            $this->retryAfter = RateLimiter::availableIn($this->throttleKey());
            return;
        }
        // $this->ensureIsNotRateLimited();

        $vendorId = app('vendor.context')->id();

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            fn($query) => $query->whereHas('vendorStaff', fn($query) => $query->where('vendor_id', $vendorId)),
        ];

        $authenticated = Auth::guard('vendor')->attempt($credentials, $this->remember);

        if (!$authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => SystemMessage::INVALID_CREDENTIALS->value,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        return $this->redirectIntended(route('vendor.admin.dashboard'));
    }

    // protected function ensureIsNotRateLimited(): void
    // {
    //     if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
    //         return;
    //     }

    //     event(new Lockout(request()));

    //     $this->retryAfter = RateLimiter::availableIn($this->throttleKey());

    //     $this->addError('email', 'Terlalu banyak percobaan login.');
    // }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip() . '|' . app('vendor.context')->id());
    }
};
?>

<div class="min-h-screen bg-gradient-to-b dark:from-zinc-950 dark:to-zinc-900">
    <div class="flex min-h-screen">
        <div class="flex-1 flex justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-md space-y-6" x-data="{
                seconds: {{ $retryAfter }},
                init() {
                    if (this.seconds > 0) {
                        const timer = setInterval(() => {
                            this.seconds--;
                            if (this.seconds <= 0) {
                                clearInterval(timer);
                                $wire.set('retryAfter', 0);
                            }
                        }, 1000);
                    }
                }
            }">

                {{-- Tombol Kembali --}}
                <flux:link href="{{ route('landing_page') }}" variant="subtle"
                    class="inline-flex items-center gap-1 text-sm">
                    <flux:icon.arrow-left variant="micro" />
                    Kembali ke Beranda
                </flux:link>

                <div class="text-center">
                    <flux:heading size="xl">Masuk ke Akun</flux:heading>
                    <flux:subheading>Dasbor {{ app('vendor.context')->get()->name }}</flux:subheading>
                </div>

                {{-- Callout Peringatan --}}
                <template x-if="seconds > 0">
                    <flux:callout variant="danger" icon="exclamation-triangle">
                        <flux:callout.text>
                            Terlalu banyak percobaan. Coba lagi dalam
                            <span x-text="seconds" class="font-semibold"></span> detik.
                        </flux:callout.text>
                    </flux:callout>
                </template>

                <flux:card class="space-y-6">
                    <form wire:submit="login" class="space-y-5">
                        <flux:input wire:model="email" label="Email" type="email" icon="envelope"
                            placeholder="email@example.com" autofocus autocomplete="email" />

                        <flux:input wire:model="password" label="Password" type="password" icon="lock-closed"
                            placeholder="Masukkan password Anda" viewable autocomplete="current-password" />

                        <div class="flex items-center justify-between">
                            <flux:checkbox wire:model="remember" label="Ingat saya" />

                            <flux:link href="#" class="text-sm !text-[#1a5c40] dark:!text-[#25825a] font-medium">
                                Lupa password?
                            </flux:link>
                        </div>

                        <div class="pt-2">
                            <flux:button type="submit" variant="primary" class="w-full !bg-[#1a5c40]"
                                x-bind:disabled="seconds > 0">
                                Masuk
                            </flux:button>
                        </div>
                    </form>
                </flux:card>
            </div>
            {{-- 
                <flux:text class="text-center text-sm">
                    Klinik Anda belum terdaftar?
                    <flux:link class="!text-[#1a5c40] dark:!text-[#25825a] font-medium"
                        href="{{ route('landing_page') }}">
                        Daftar sebagai Vendor
                    </flux:link>
                </flux:text> --}}
        </div>

        {{-- Gambar Background Sisi Kanan (Sesuai inspirasi, diaktifkan jika butuh) --}}
        {{--
        <div class="flex-1 p-4 max-lg:hidden">
            <div class="text-white relative rounded-lg h-full w-full bg-zinc-900 flex flex-col items-start justify-end p-16"
                style="background-image: url('https://fluxui.dev/img/demo/auth_aurora_2x.png'); background-size: cover">
                <div class="mb-6 italic font-base text-3xl xl:text-4xl">
                    Kelola operasional pet care Anda dengan lebih mudah dan efisien bersama PawPaw.
                </div>
            </div>
        </div>
        --}}
    </div>
</div>
