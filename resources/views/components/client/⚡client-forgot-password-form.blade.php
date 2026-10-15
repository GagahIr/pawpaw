<?php

use App\Actions\Client\SendClientPasswordResetLinkAction;
use App\DTOs\SendClientPasswordResetLinkData;
use App\Enums\SystemMessage;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    public function sendResetLink(SendClientPasswordResetLinkAction $action): void
    {
        $this->email = strtolower(trim($this->email));
        try {
            $this->validate(['email' => ['required', 'string', 'email', 'max:255']], [
                'email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.',
                'email.max' => 'Email maksimal 255 karakter.',
            ]);
            $action->execute(new SendClientPasswordResetLinkData($this->email));
        } catch (ValidationException $e) {
            Flux::toast($e->validator->errors()->first(), variant: 'danger');

            throw $e;
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(SystemMessage::ERROR_SYSTEM->value, variant: 'danger');

            return;
        }

        Flux::toast('Jika email terdaftar sebagai pelanggan, tautan reset password akan dikirim. Periksa email Anda.', variant: 'success');
    }
};
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-xl space-y-6">
        <flux:link href="{{ route('client.login') }}" variant="subtle" class="inline-flex items-center gap-1 text-sm">
            <flux:icon.arrow-left variant="micro" /> Kembali ke Login
        </flux:link>
        <div class="text-center">
            <flux:heading size="xl" level="1">Lupa Password?</flux:heading>
            <flux:subheading>Masukkan email akun pelanggan untuk menerima tautan reset password.</flux:subheading>
        </div>
        <flux:card class="space-y-8">
            <form wire:submit.prevent="sendResetLink" class="space-y-8" novalidate>
                <flux:input wire:model="email" name="email" label="Email" type="email" required maxlength="255"
                    icon="envelope" autocomplete="email" placeholder="johndoe@example.com" />
                <flux:button type="submit" variant="primary" class="w-full !bg-[#1a5c40]"
                    wire:loading.attr="disabled" wire:target="sendResetLink">Kirim Tautan Reset Password</flux:button>
            </form>
        </flux:card>
    </div>
</div>
