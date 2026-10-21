<?php

use App\Actions\Client\LoginClientAction;
use App\DTOs\LoginClientData;
use App\Enums\SystemMessage;
use App\Support\ClientFlash;
use Flux\Flux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    public string $identifier = '';
    public string $password = '';
    public bool $remember = false;
    public string $errorMessage = '';

    public function mount(): void
    {
        $identifier = request()->cookie('pawpaw_client_identifier');
        if (is_string($identifier) && $identifier !== '' && strlen($identifier) <= 255) {
            $this->identifier = $identifier;
        }
    }

    protected function rules(): array
    {
        $identifierRules = str_contains($this->identifier, '@')
            ? ['required', 'string', 'email', 'max:255']
            : ['required', 'string', 'min:10', 'max:15', 'regex:/\A\+?[0-9]{10,20}\z/'];

        return [
            'identifier' => $identifierRules,
            'password' => ['required', 'string', 'min:8', 'max:72', function (string $attribute, mixed $value, \Closure $fail): void {
                if (strlen($value) > 72) {
                    $fail('Password terlalu panjang. Maksimal 72 byte; emoji atau huruf beraksen dapat memakai lebih dari satu byte.');
                }
            }],
            'remember' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'identifier.email' => 'Format email tidak valid.',
            'identifier.regex' => 'Nomor telepon harus berisi minimal 10 angka, dengan tanda + hanya di awal jika diperlukan.',
            'remember.boolean' => 'Pilihan Ingat Saya tidak valid.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['identifier' => 'Email atau nomor telepon', 'password' => 'Password'];
    }

    public function loginWithGoogle(): RedirectResponse
    {
        return redirect()->route('client.google.redirect', ['remember' => $this->remember ? 1 : 0]);
    }

    public function login(LoginClientAction $action): ?RedirectResponse
    {
        if (Auth::guard('client')->check()) {
            return redirect()->route('client.dashboard');
        }

        $this->errorMessage = '';
        $this->resetValidation();
        $this->identifier = trim($this->identifier);
        $this->identifier = str_contains($this->identifier, '@')
            ? strtolower($this->identifier)
            : preg_replace('/[\s()-]+/', '', $this->identifier);

        try {
            $this->validate();
            $action->execute(new LoginClientData(
                identifier: $this->identifier,
                password: $this->password,
                remember: $this->remember,
            ));
        } catch (ValidationException $e) {
            $this->reset('password');
            Flux::toast($e->validator->errors()->first(), variant: 'danger');

            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->reset('password');
            $this->errorMessage = SystemMessage::ERROR_SYSTEM->value;
            Flux::toast($this->errorMessage, variant: 'danger');

            return null;
        }

        $this->reset('password');
        ClientFlash::loginSuccess(SystemMessage::CLIENT_LOGIN_SUCCESS->value);

        return redirect()->route('client.dashboard');
    }
};

?>

<div x-data="{
    notice: '',
}">
    <div class="min-h-screen bg-gradient-to-b dark:from-zinc-950 dark:to-zinc-900">
        <div class="flex min-h-screen">
            <div class="flex-1 flex justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-xl space-y-6">
                    <flux:link href="{{ url('/') }}" variant="subtle" class="inline-flex items-center gap-1 text-sm">
                        <flux:icon.arrow-left variant="micro" />
                        Kembali ke Beranda
                    </flux:link>

                    <div class="text-center">
                        <flux:heading size="xl" level="1">Masuk Akun Pelanggan</flux:heading>
                        <flux:subheading>
                            Masuk untuk reservasi dokter hewan, cek riwayat kesehatan, dan perawatan anabul kesayangan Anda.
                        </flux:subheading>
                    </div>

                    <flux:card class="space-y-8">
                        <form class="space-y-8"
                            wire:submit.prevent="login" novalidate>
                            <div class="space-y-5">
                                <flux:input id="client-login-identifier" name="identifier" wire:model.blur="identifier"
                                    type="text" label="Email atau Nomor Telepon" required
                                    placeholder="johndoe@example.com atau 08xxxxxxxxxx"
                                    icon="envelope" autocomplete="username" autocapitalize="none" spellcheck="false" />

                                <flux:input id="client-login-password" name="password" wire:model="password"
                                    type="password" label="Kata Sandi (Password)" required minlength="8" maxlength="72"
                                    placeholder="Masukkan kata sandi Anda" icon="lock-closed" viewable
                                    autocomplete="current-password" />

                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <flux:field variant="inline">
                                        <flux:checkbox id="client-login-remember" name="remember" wire:model="remember" />
                                        <flux:label for="client-login-remember" class="!font-normal">
                                            <span>Ingat Saya</span>
                                        </flux:label>
                                    </flux:field>

                                    <flux:button href="{{ route('client.password.request') }}" variant="subtle" size="sm"
                                        class="!text-[#1a5c40] dark:!text-[#25825a]"
                                        >
                                        Lupa Password?
                                    </flux:button>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <flux:button type="submit" variant="primary" icon:trailing="arrow-right"
                                    wire:loading.attr="disabled" wire:target="login"
                                    class="flex-1 !bg-[#1a5c40]">
                                    Masuk Sekarang
                                </flux:button>
                            </div>

                            <div class="space-y-5">
                                <div class="flex items-center gap-3">
                                    <flux:separator variant="subtle" class="flex-1" />
                                    <flux:text class="shrink-0 text-xs">atau masuk dengan</flux:text>
                                    <flux:separator variant="subtle" class="flex-1" />
                                </div>

                                <flux:button type="button" variant="outline" class="w-full"
                                    wire:click="loginWithGoogle" wire:loading.attr="disabled" wire:target="loginWithGoogle">
                                    <span class="inline-flex items-center justify-center gap-2">
                                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.02v2.51h3.24c1.9-1.75 2.98-4.32 2.98-7.36Z" />
                                        <path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.62-2.41l-3.24-2.51c-.9.6-2.05.96-3.38.96-2.6 0-4.8-1.76-5.59-4.13H3.07v2.59A10 10 0 0 0 12 22Z" />
                                        <path fill="#FBBC05" d="M6.41 13.91a6 6 0 0 1 0-3.82V7.5H3.07a10 10 0 0 0 0 9l3.34-2.59Z" />
                                        <path fill="#EA4335" d="M12 5.96c1.47 0 2.79.51 3.83 1.51l2.87-2.87A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.93 5.5l3.34 2.59C7.2 7.72 9.4 5.96 12 5.96Z" />
                                    </svg>
                                    <span>Masuk dengan Google</span>
                                    </span>
                                </flux:button>
                            </div>

                            <p x-show="notice" x-text="notice" style="display: none" role="status" aria-live="polite"
                                class="text-sm text-zinc-500 dark:text-zinc-400"></p>
                        </form>
                    </flux:card>

                    <div class="space-y-2">
                        <flux:text class="text-center">
                            Belum memiliki akun?
                            <flux:link class="!text-[#1a5c40]" href="{{ url('/client-register') }}">Daftar di sini</flux:link>
                        </flux:text>

                        <flux:text class="flex flex-wrap items-center justify-center gap-1 text-[11px]">
                            <flux:icon.building-storefront variant="micro" class="size-3.5" />
                            Punya klinik atau salon hewan?
                            <flux:link href="{{ url('/vendor-register') }}" class="!text-[#777E77]">Daftar sebagai
                                Vendor</flux:link>
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
