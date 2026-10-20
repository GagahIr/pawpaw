<?php

use App\Actions\Client\RegisterClientAction;
use App\DTOs\RegisterClientData;
use App\Enums\SystemMessage;
use App\Support\ClientFlash;
use Flux\Flux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    public string $name = '';
    public string $username = '';
    public string $phone_number = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $terms = false;
    public string $errorMessage = '';

    public function mount(): void
    {
        session()->forget('google_client_registration');
    }

    public function registerWithGoogle(): RedirectResponse
    {
        try {
            $this->validate(['terms' => ['accepted']], [
                'terms.accepted' => 'Anda harus menyetujui Syarat & Ketentuan serta Kebijakan Privasi.',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('client-registration-notification', message: $e->validator->errors()->first(), type: 'error');

            throw $e;
        }

        session()->put('google_registration_terms', true);

        return redirect()->route('client.google.redirect', ['intent' => 'register']);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:4', 'max:100'],
            'username' => ['required', 'string', 'min:5', 'max:50', 'alpha_dash:ascii', 'unique:users,username'],
            'phone_number' => ['required', 'string', 'min:10', 'max:15', 'regex:/\A\+?[0-9]{10,20}\z/', 'unique:users,phone_number', 'unique:vendors,contact'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:vendors,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', function (string $attribute, mixed $value, \Closure $fail) {
                if (strlen($value) > 72) {
                    $fail('Password terlalu panjang. Maksimal 72 byte; emoji atau huruf beraksen dapat memakai lebih dari satu byte.');
                }
            }],
            'password_confirmation' => ['required', 'string', 'min:8', 'max:72', 'same:password'],
            'terms' => ['accepted'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'unique' => ':attribute sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_).',
            'phone_number.regex' => 'Nomor telepon harus berisi minimal 10 angka, dengan tanda + hanya di awal jika diperlukan.',
            'email.email' => 'Format email tidak valid.',
            'password.confirmed' => 'Konfirmasi password harus sama dengan password.',
            'password_confirmation.same' => 'Konfirmasi password harus sama dengan password.',
            'terms.accepted' => 'Anda harus menyetujui Syarat & Ketentuan serta Kebijakan Privasi.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'Nama lengkap',
            'username' => 'Username',
            'phone_number' => 'Nomor telepon',
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Konfirmasi password',
        ];
    }

    public function register(RegisterClientAction $action)
    {
        $this->errorMessage = '';
        $this->username = strtolower(trim($this->username));
        $this->email = strtolower(trim($this->email));
        $this->name = trim($this->name);
        $this->phone_number = preg_replace('/[\s()-]+/', '', trim($this->phone_number));
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('client-registration-notification',
                message: $e->validator->errors()->first(),
                type: 'error',
            );

            throw $e;
        }

        $data = new RegisterClientData(
            name: $this->name,
            username: $this->username,
            phoneNumber: $this->phone_number,
            email: $this->email,
            password: $this->password,
        );

        try {
            $action->execute($data);
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = SystemMessage::ERROR_SYSTEM->value;
            $this->dispatch('client-registration-notification', message: $this->errorMessage, type: 'error');

            return;
        }

        session()->forget('google_client_registration');
        ClientFlash::registrationSuccess(SystemMessage::CLIENT_REGISTER_SUCCESS->value);

        return redirect('/');
    }
};

?>

<div x-data="{
    notice: '',
}">
    <x-client.registration-toast
        :message="session('client_register_error') ?? session('client_register_notice', '')"
        :type="session('client_register_error') ? 'error' : 'success'" />
    <div class="min-h-screen bg-gradient-to-b dark:from-zinc-950 dark:to-zinc-900">
        <div class="flex min-h-screen">
            <div class="flex-1 flex justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-xl space-y-6">
                    <flux:link href="{{ url('/') }}" variant="subtle" class="inline-flex items-center gap-1 text-sm">
                        <flux:icon.arrow-left variant="micro" />
                        Kembali ke Beranda
                    </flux:link>

                    <div class="text-center">
                        <flux:heading size="xl" level="1">Daftar Akun Pelanggan</flux:heading>
                        <flux:subheading>
                            Buat akun untuk mempermudah reservasi dokter hewan dan perawatan anabul.
                        </flux:subheading>
                    </div>

                    <flux:card class="space-y-8">
                        @if ($errorMessage)
                            <flux:callout variant="danger" icon="x-circle" heading="{{ $errorMessage }}" />
                        @endif
                        <form class="space-y-8"
                            wire:submit.prevent="register">
                            <div class="space-y-5">
                                <header class="flex items-center gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#E8F5EE] text-[#236B55]">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <ellipse cx="5.5" cy="9" rx="2" ry="2.7"
                                                transform="rotate(-25 5.5 9)" />
                                            <ellipse cx="9.5" cy="5.5" rx="2" ry="2.7"
                                                transform="rotate(-10 9.5 5.5)" />
                                            <ellipse cx="14.5" cy="5.5" rx="2" ry="2.7"
                                                transform="rotate(10 14.5 5.5)" />
                                            <ellipse cx="18.5" cy="9" rx="2" ry="2.7"
                                                transform="rotate(25 18.5 9)" />
                                            <path
                                                d="M7 14.2c1.3-1.5 2.2-4.1 5-4.1s3.7 2.6 5 4.1c1.2 1.4 1.8 3.4.6 4.6-1.5 1.5-3.4.3-5.6.3s-4.1 1.2-5.6-.3c-1.2-1.2-.6-3.2.6-4.6Z" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0 space-y-1">
                                        <flux:heading id="client-register-heading" level="1"
                                            class="!text-base !font-bold !text-[#303A35]">
                                            Informasi Akun Pelanggan
                                        </flux:heading>
                                        <flux:text class="!text-xs !leading-relaxed !text-[#90958F]">
                                            Lengkapi identitas Anda untuk kemudahan verifikasi reservasi.
                                        </flux:text>
                                    </div>
                                </header>

                                <flux:separator class="my-6 !bg-[#F2F2EE]" />

                                <flux:input id="client-name" name="name" wire:model.blur="name" label="Nama Lengkap"
                                    required maxlength="100" placeholder="Contoh: John Doe" icon="user"
                                    autocomplete="name" />

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <flux:input id="client-username" name="username" wire:model.blur="username"
                                        label="Username" required maxlength="50" placeholder="johndoe"
                                        icon="at-symbol" autocomplete="username" />

                                    <flux:input id="client-phone" name="phone_number" wire:model.blur="phone_number"
                                        type="tel" label="No. WhatsApp/Telepon" required minlength="10" maxlength="20" placeholder="08xxxxxxxxxx"
                                        icon="device-phone-mobile" autocomplete="tel" />
                                </div>

                                <flux:input id="client-email" name="email" wire:model.blur="email" type="email"
                                    label="Email" required maxlength="255" placeholder="johndoe@example.com" icon="envelope"
                                    autocomplete="email" />

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <flux:input id="client-password" name="password" wire:model="password"
                                        type="password" label="Password" required minlength="8" maxlength="72"
                                        placeholder="Minimal 8 karakter" icon="lock-closed" viewable
                                        autocomplete="new-password" />

                                    <flux:input id="client-password-confirmation" name="password_confirmation"
                                        wire:model="password_confirmation" type="password" label="Konfirmasi Password"
                                        required minlength="8" maxlength="72" placeholder="Ulangi password" icon="lock-closed"
                                        viewable autocomplete="new-password" />
                                </div>

                                <flux:field variant="inline">
                                    <flux:checkbox id="client-terms" name="terms" wire:model="terms" required />
                                    <flux:label for="client-terms" class="!font-normal">
                                        <span>
                                            Saya menyetujui <span
                                                class="font-medium text-[#1a5c40] dark:text-[#25825a]">Syarat &amp;
                                                Ketentuan</span>
                                            serta <span
                                                class="font-medium text-[#1a5c40] dark:text-[#25825a]">Kebijakan
                                                Privasi</span> PawPaw.
                                        </span>
                                    </flux:label>
                                    <flux:error name="terms" />
                                </flux:field>
                            </div>

                            <flux:separator variant="subtle" />

                            <div class="flex items-center gap-3">
                                <flux:button type="submit" variant="primary" icon:trailing="arrow-right"
                                    wire:loading.attr="disabled" wire:target="register" class="flex-1 !bg-[#1a5c40]">
                                    Daftar Sekarang
                                </flux:button>
                            </div>

                            <div class="space-y-5">
                                <div class="flex items-center gap-3">
                                    <flux:separator variant="subtle" class="flex-1" />
                                    <flux:text class="shrink-0 text-xs">atau daftar dengan</flux:text>
                                    <flux:separator variant="subtle" class="flex-1" />
                                </div>

                                <flux:button type="button" variant="outline" class="w-full"
                                    wire:click="registerWithGoogle" wire:loading.attr="disabled" wire:target="registerWithGoogle" :loading="false">
                                    <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill="#4285F4"
                                            d="M21.6 12.23c0-.71-.06-1.39-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.02v2.51h3.24c1.9-1.75 2.98-4.32 2.98-7.36Z" />
                                        <path fill="#34A853"
                                            d="M12 22c2.7 0 4.96-.9 6.62-2.41l-3.24-2.51c-.9.6-2.05.96-3.38.96-2.6 0-4.8-1.76-5.59-4.13H3.07v2.59A10 10 0 0 0 12 22Z" />
                                        <path fill="#FBBC05"
                                            d="M6.41 13.91a6 6 0 0 1 0-3.82V7.5H3.07a10 10 0 0 0 0 9l3.34-2.59Z" />
                                        <path fill="#EA4335"
                                            d="M12 5.96c1.47 0 2.79.51 3.83 1.51l2.87-2.87A9.6 9.6 0 0 0 12 2a10 10 0 0 0-8.93 5.5l3.34 2.59C7.2 7.72 9.4 5.96 12 5.96Z" />
                                    </svg>
                                    Daftar dengan Google
                                </flux:button>
                            </div>

                            <p x-show="notice" x-text="notice" style="display: none" role="status"
                                aria-live="polite" class="text-sm text-zinc-500 dark:text-zinc-400"></p>
                        </form>
                    </flux:card>

                    <div class="space-y-2">
                        <flux:text class="text-center">
                            Sudah memiliki akun?
                            <flux:link class="!text-[#1a5c40]" href="{{ url('/client-login') }}">Masuk di sini</flux:link>
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
