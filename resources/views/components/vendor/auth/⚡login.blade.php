<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Enums\SystemMessage;

new #[Layout('layouts.app')] class extends Component {
    public string $identifier = '';

    public string $password = '';

    public bool $remember = false;

    public int $retryAfter = 0;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Email, username, atau nomor HP wajib diisi.',
            'identifier.max' => 'Email, username, atau nomor HP maksimal 255 karakter.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    public function mount(): void
    {
        $identifier = request()->cookie('pawpaw_vendor_identifier');
        if (is_string($identifier) && $identifier !== '' && strlen($identifier) <= 255) {
            $this->identifier = $identifier;
            $this->remember = true;
        }

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
            'password' => $this->password,
            fn (Builder $query) => $query->where(fn (Builder $query) => $this->matchIdentifier($query)),
            fn (Builder $query) => $query->whereHas('vendorStaff', fn ($query) => $query->where('vendor_id', $vendorId)),
        ];

        $authenticated = Auth::guard('vendor')->attempt($credentials, $this->remember);

        if (!$authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identifier' => SystemMessage::INVALID_CREDENTIALS->value,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        if ($this->remember) {
            Cookie::queue('pawpaw_vendor_identifier', $this->identifier, 60 * 24 * 30);
        } else {
            Cookie::queue(Cookie::forget('pawpaw_vendor_identifier'));
        }

        return $this->redirectIntended(route('vendor.admin.dashboard'));
    }

    // protected function ensureIsNotRateLimited(): void
    // {
    //     if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
    //         return;
    //     }

    //     event(new Lockout(request()));

    //     $this->retryAfter = RateLimiter::availableIn($this->throttleKey());

    //     $this->addError('identifier', 'Terlalu banyak percobaan login.');
    // }

    /**
     * Match the typed identifier against email, username, or phone number.
     */
    protected function matchIdentifier(Builder $query): void
    {
        $identifier = trim($this->identifier);

        if (str_contains($identifier, '@')) {
            $query->where('email', $identifier);

            return;
        }

        $query->whereRaw('LOWER(username) = ?', [Str::lower($identifier)]);

        $phoneNumbers = $this->phoneNumberVariants($identifier);

        if ($phoneNumbers !== []) {
            $query->orWhereIn('phone_number', $phoneNumbers);
        }
    }

    /**
     * Indonesian numbers can be stored as 08xx, 628xx, or +628xx, so try all three.
     *
     * @return list<string>
     */
    protected function phoneNumberVariants(string $identifier): array
    {
        $number = preg_replace('/[\s().-]+/', '', $identifier);

        if (! preg_match('/\A\+?[0-9]{8,20}\z/', $number)) {
            return [];
        }

        $digits = ltrim($number, '+');

        $national = match (true) {
            str_starts_with($digits, '62') => substr($digits, 2),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => null,
        };

        if ($national === null) {
            return [$number];
        }

        return array_values(array_unique([$number, '0'.$national, '62'.$national, '+62'.$national]));
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->identifier) . '|' . request()->ip() . '|' . app('vendor.context')->id());
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
                    <flux:heading size="xl">Selamat datang di {{ app('vendor.context')->get()->name }}</flux:heading>
                    <flux:subheading>Silakan masuk menggunakan akun pemilik atau front office Anda.</flux:subheading>
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
                        <flux:input wire:model="identifier" label="Email, username, atau nomor HP" type="text" icon="user"
                            placeholder="Email, username, atau nomor HP" autofocus autocomplete="username" />

                        <flux:input wire:model="password" label="Password" type="password" icon="lock-closed"
                            placeholder="Masukkan password Anda" viewable autocomplete="current-password" />

                        <div class="flex items-center justify-between">
                            <flux:checkbox wire:model="remember" label="Ingat saya" />

                            <flux:link href="{{ route('vendor.password.request') }}" class="text-sm !text-[#1a5c40] dark:!text-[#25825a] font-medium">
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
        </div>
    </div>
</div>
