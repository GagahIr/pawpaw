<?php

use App\Actions\Client\ResetClientPasswordAction;
use App\DTOs\ResetClientPasswordData;
use App\Enums\SystemMessage;
use Flux\Flux;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $email = request()->query('email');
        $this->email = is_string($email) ? strtolower(trim($email)) : '';
    }

    public function resetPassword(ResetClientPasswordAction $action): ?RedirectResponse
    {
        $this->email = strtolower(trim($this->email));
        try {
            $this->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
                'token' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('Password terlalu panjang. Maksimal 72 byte; emoji atau huruf beraksen dapat memakai lebih dari satu byte.');
                    }
                }],
                'password_confirmation' => ['required', 'string', 'min:8', 'max:72', 'same:password'],
            ], [
                'required' => ':attribute wajib diisi.', 'email.email' => 'Format email tidak valid.',
                'min' => ':attribute minimal :min karakter.', 'max' => ':attribute maksimal :max karakter.',
                'password.confirmed' => 'Konfirmasi password harus sama dengan password.',
                'password_confirmation.same' => 'Konfirmasi password harus sama dengan password.',
            ], ['email' => 'Email', 'password' => 'Password', 'password_confirmation' => 'Konfirmasi password']);

            $action->execute(new ResetClientPasswordData($this->email, $this->token, $this->password));
        } catch (ValidationException $e) {
            $this->reset('password', 'password_confirmation');
            Flux::toast($e->validator->errors()->first(), variant: 'danger');

            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->reset('password', 'password_confirmation');
            Flux::toast(SystemMessage::ERROR_SYSTEM->value, variant: 'danger');

            return null;
        }

        $this->reset('password', 'password_confirmation');

        return redirect()->route('client.login')->with('client_password_reset_success', 'Password berhasil diperbarui. Silakan masuk dengan password baru.');
    }
};
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-xl space-y-6">
        <flux:link href="{{ route('client.login') }}" variant="subtle" class="inline-flex items-center gap-1 text-sm">
            <flux:icon.arrow-left variant="micro" /> Kembali ke Login
        </flux:link>
        <div class="text-center">
            <flux:heading size="xl" level="1">Atur Password Baru</flux:heading>
            <flux:subheading>Gunakan password minimal 8 karakter untuk akun pelanggan Anda.</flux:subheading>
        </div>
        <flux:card class="space-y-8">
            <form wire:submit.prevent="resetPassword" class="space-y-8" novalidate>
                <flux:input wire:model="email" name="email" label="Email" type="email" required maxlength="255" icon="envelope" autocomplete="email" />
                <flux:input wire:model="password" name="password" label="Password Baru" type="password" required minlength="8" maxlength="72" icon="lock-closed" autocomplete="new-password" viewable />
                <flux:input wire:model="password_confirmation" name="password_confirmation" label="Konfirmasi Password" type="password" required minlength="8" maxlength="72" icon="lock-closed" autocomplete="new-password" viewable />
                <flux:button type="submit" variant="primary" class="w-full !bg-[#1a5c40]"
                    wire:loading.attr="disabled" wire:target="resetPassword">Simpan Password Baru</flux:button>
            </form>
        </flux:card>
    </div>
</div>
