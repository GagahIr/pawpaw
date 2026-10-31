<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public int $retryAfter = 0;

    public function mount(): void
    {
        $this->checkRateLimit();
    }

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->checkRateLimit();

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
        ];

        if (Auth::guard('web')->attempt($credentials, $this->remember)) {
            $user = Auth::guard('web')->user();

            // Pastikan akun merupakan Super Admin
            if ($user->role?->name !== 'super_admin' || $user->deleted_at !== null) {
                Auth::guard('web')->logout();

                throw ValidationException::withMessages([
                    'email' => 'Anda tidak memiliki akses Super Admin.',
                ]);
            }

            RateLimiter::clear($this->throttleKey());

            session()->regenerate();

            $this->redirectRoute('super_admin.dashboard');

            return;
        }

        RateLimiter::hit($this->throttleKey(), 60);

        $this->checkRateLimit();

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    protected function checkRateLimit(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $this->retryAfter = RateLimiter::availableIn($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $this->retryAfter,
                    'minutes' => ceil($this->retryAfter / 60),
                ]),
            ]);
        }

        $this->retryAfter = 0;
    }

    protected function throttleKey(): string
    {
        return 'super-admin-login:' . Str::lower($this->email) . '|' . request()->ip();
    }
};
?>

<div class="min-h-screen bg-gradient-to-b dark:from-zinc-950 dark:to-zinc-900">
    <div class="flex min-h-screen">
        <div class="flex-1 flex justify-center items-center py-12 px-4 sm:px-6 lg:px-8">

            <div class="w-full max-w-md space-y-6" x-data="{
                seconds: $wire.entangle('retryAfter'),
                timer: null,
            
                init() {
                    this.$watch('seconds', value => {
                        if (value > 0) {
                            this.startTimer();
                        } else {
                            this.stopTimer();
                        }
                    });
            
                    if (this.seconds > 0) {
                        this.startTimer();
                    }
                },
            
                startTimer() {
                    if (this.timer !== null) return;
            
                    this.timer = setInterval(() => {
                        if (this.seconds > 0) {
                            this.seconds--;
                        }
            
                        if (this.seconds <= 0) {
                            this.stopTimer();
                        }
                    }, 1000);
                },
            
                stopTimer() {
                    if (this.timer !== null) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                },
            
                destroy() {
                    this.stopTimer();
                }
            }">

                <div class="text-center space-y-2">
                    <flux:heading size="xl">
                        Super Admin
                    </flux:heading>

                    <flux:text>
                        Masuk ke panel administrasi PawPaw
                    </flux:text>
                </div>

                {{-- Rate Limit Warning --}}
                <template x-if="seconds > 0">
                    <flux:callout variant="danger" icon="exclamation-triangle">
                        <flux:callout.text>
                            Terlalu banyak percobaan login.
                            Coba lagi dalam
                            <span x-text="seconds" class="font-semibold"></span>
                            detik.
                        </flux:callout.text>
                    </flux:callout>
                </template>

                <flux:card class="space-y-6">

                    <form wire:submit="login" class="space-y-5">

                        <flux:input wire:model="email" label="Email" type="email" icon="envelope"
                            placeholder="admin@example.com" autofocus autocomplete="username" />

                        <flux:input wire:model="password" label="Password" type="password" icon="lock-closed"
                            placeholder="Masukkan password Anda" viewable autocomplete="current-password" />

                        <div class="flex items-center justify-between">
                            <flux:checkbox wire:model="remember" label="Ingat saya" />
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

        </div>
    </div>
</div>
