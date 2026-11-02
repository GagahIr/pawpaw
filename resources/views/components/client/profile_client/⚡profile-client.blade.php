<?php

use App\Actions\Client\ProfileClient\ProfileClientAction;
use App\Actions\Account\CancelEmailChangeAction;
use App\Actions\Account\ResendEmailChangeAction;
use App\Actions\Account\UpdatePasswordAction;
use App\DTOs\UpdatePasswordData;
use App\DTOs\UpdateProfileData;
use App\Enums\SystemMessage;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.client')] class extends Component {
    use WithFileUploads;

    public ?User $user = null;

    public array $formData = [];

    public $avatar = null;

    public bool $isEditing = false;

    public bool $isChangingPassword = false;

    public string $currentPassword = '';

    public string $emailChangePassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function rules(): array
    {
        $userId = $this->user?->id ?? auth()->id();

        $rules = [
            'formData.name' => ['required', 'string', 'min:3', 'max:100'],
            'formData.username' => ['required', 'string', 'min:4', 'max:50', 'alpha_dash:ascii', 'unique:users,username,' . $userId],
            'formData.email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId, 'unique:vendors,email'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];

        if (filled($this->formData['phone_number'])) {
            $rules['formData.phone_number'] = ['string', 'min:8', 'max:15', 'regex:/\A[0-9]{8,15}\z/', 'unique:users,phone_number,' . $userId, 'unique:vendors,contact'];
        } else {
            $rules['formData.phone_number'] = ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'formData.name.string' => 'Nama lengkap harus berupa teks',
            'formData.name.required' => 'Nama lengkap harus diisi',
            'formData.name.min' => 'Nama lengkap minimal 3 karakter',
            'formData.name.max' => 'Nama lengkap maksimal 100 karakter',
            'formData.username.string' => 'Username harus berupa teks',
            'formData.username.required' => 'Username harus diisi',
            'formData.username.min' => 'Username minimal 4 karakter',
            'formData.username.max' => 'Username maksimal 50 karakter',
            'formData.username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_)',
            'formData.username.unique' => 'Username sudah digunakan',
            'formData.email.string' => 'Email harus berupa teks',
            'formData.email.max' => 'Email maksimal 255 karakter',
            'formData.email.required' => 'Email harus diisi',
            'formData.email.email' => 'Format email tidak valid',
            'formData.email.unique' => 'Email sudah terdaftar',
            'formData.phone_number.string' => 'Nomor WhatsApp / telepon harus berupa teks',
            'formData.phone_number.min' => 'Nomor WhatsApp / telepon minimal 8 digit',
            'formData.phone_number.max' => 'Nomor WhatsApp / telepon maksimal 15 digit',
            'formData.phone_number.regex' => 'Nomor WhatsApp / telepon hanya boleh berisi 8–15 digit angka',
            'formData.phone_number.unique' => 'Nomor WhatsApp / telepon sudah terdaftar',
            'avatar.image' => 'File harus berupa gambar',
            'avatar.mimes' => 'Format gambar harus JPEG, PNG, atau WebP',
            'avatar.max' => 'Ukuran gambar maksimal 2 MB',
        ];
    }

    public function mount(): void
    {
        $this->user = auth('client')->user();
        abort_unless($this->user?->canChangeEmail(), 403);
        $this->formData = [
            'name' => $this->user?->name ?? '',
            'username' => $this->user?->username ?? '',
            'email' => $this->user?->email ?? '',
            'phone_number' => $this->user?->phone_number ?? '',
        ];
    }

    public function updatedAvatar(): void
    {
        $this->validateOnly('avatar');
        $this->isEditing = true;
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
    }

    public function cancelEditing(): void
    {
        $this->avatar = null;
        $this->emailChangePassword = '';
        $this->formData = [
            'name' => $this->user?->name ?? '',
            'username' => $this->user?->username ?? '',
            'email' => $this->user?->email ?? '',
            'phone_number' => $this->user?->phone_number ?? '',
        ];
        $this->resetErrorBag();
        $this->isEditing = false;
    }

    public function save(ProfileClientAction $action): void
    {
        $this->formData['email'] = strtolower(trim($this->formData['email']));
        $this->validate();

        $data = [
            'name' => trim($this->formData['name']),
            'username' => strtolower(trim($this->formData['username'])),
            'email' => strtolower(trim($this->formData['email'])),
            'phone_number' => filled($this->formData['phone_number'])
                ? $this->formData['phone_number']
                : null,
        ];

        if ($data['email'] !== $this->user->email && blank($this->user->password)) {
            $this->startChangingPassword();
            $this->addError('formData.email', 'Buat kata sandi PawPaw di bagian Keamanan Akun terlebih dahulu sebelum mengganti email.');
            Flux::toast('Buat kata sandi PawPaw terlebih dahulu sebelum mengganti email.', variant: 'warning');
            return;
        }

        $action->execute($this->user->id, new UpdateProfileData(
            name: $data['name'],
            username: $data['username'],
            email: $data['email'],
            phoneNumber: $data['phone_number'],
            emailChangePassword: $this->emailChangePassword,
            avatar: $this->avatar,
        ));

        $this->user->refresh();
        $this->avatar = null;
        $this->formData = [
            'name' => $this->user->name,
            'username' => $this->user->username,
            'email' => $this->user->email,
            'phone_number' => $this->user->phone_number ?? '',
        ];
        $this->isEditing = false;

        $newAvatarUrl = $this->user->avatar
            ? Storage::url($this->user->avatar)
            : '';
        $this->js("window.dispatchEvent(new CustomEvent('client-avatar-updated', { detail: { url: " . json_encode($newAvatarUrl) . " } }))");

        Flux::toast($this->user->pending_email ? 'Profil disimpan. Periksa email baru untuk mengonfirmasi perubahan email.' : SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
    }

    public function getInitials(): string
    {
        $names = explode(' ', trim($this->user?->name ?? ''));
        $initials = '';
        foreach (array_slice($names, 0, 2) as $name) {
            if (! empty($name)) {
                $initials .= strtoupper(substr($name, 0, 1));
            }
        }
        return $initials ?: 'P';
    }

    public function startChangingPassword(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetErrorBag();
        $this->isChangingPassword = true;
        $this->dispatch('client-password-form-opened');
    }

    public function cancelChangingPassword(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetErrorBag();
        $this->isChangingPassword = false;
    }

    public function updatePassword(UpdatePasswordAction $action): void
    {
        $isCreatingPassword = blank($this->user->password);
        $action->execute($this->user, new UpdatePasswordData(
            $this->currentPassword, $this->newPassword, $this->newPasswordConfirmation,
        ));
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation', 'emailChangePassword']);
        $this->resetErrorBag();
        $this->isChangingPassword = false;
        Flux::toast($isCreatingPassword
            ? 'Kata sandi berhasil dibuat. Kamu sekarang dapat melanjutkan perubahan email.'
            : 'Kata sandi berhasil diperbarui.', variant: 'success');
    }

    public function resendEmailChange(ResendEmailChangeAction $action): void
    {
        $action->execute($this->user, $this->emailChangePassword);
        $this->resetErrorBag();
        Flux::toast('Tautan konfirmasi baru sudah dikirim.', variant: 'success');
    }

    public function cancelEmailChange(CancelEmailChangeAction $action): void
    {
        $action->execute($this->user);
        $this->emailChangePassword = '';
        $this->resetErrorBag();
        Flux::toast('Permintaan perubahan email dibatalkan.', variant: 'success');
    }

};
?>

<div class="min-h-screen py-8 sm:py-10" x-data
    x-on:client-password-form-opened.window="$nextTick(() => {
        const section = document.getElementById('keamanan-akun');
        const input = section?.querySelector('[data-password-focus]');
        if (!section || !input) return;
        history.replaceState(null, '', '#keamanan-akun');
        input.focus({ preventScroll: true });
        section.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    })">
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs sm:text-sm text-zinc-500">
            <a href="{{ route('landing_page') }}" class="hover:text-zinc-800 transition">Beranda</a>
            <span class="text-zinc-400">›</span>
            <span class="text-zinc-800 font-medium">Profil saya</span>
        </nav>

        <!-- Header -->
        <div>
            <p class="text-xs font-bold tracking-wider text-[#EE6D52] uppercase mb-1">
                AKUN PELANGGAN
            </p>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-[#1B3C2A] tracking-tight">
                Kenalan lebih dekat, yuk.
            </h1>
            <p class="mt-2 text-sm sm:text-base text-zinc-500">
                Kelola informasi dirimu untuk reservasi yang lebih praktis.
            </p>
        </div>

        @if (blank($user?->password))
            <aside role="status" aria-labelledby="password-reminder-title"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-950">
                <h2 id="password-reminder-title" class="text-sm font-semibold">Tambahkan kata sandi PawPaw</h2>
                <p class="mt-2 text-sm">Kamu tetap bisa masuk melalui Google. Buat kata sandi agar dapat mengganti email dan masuk menggunakan email serta kata sandi.</p>
                <a href="#keamanan-akun" wire:click.prevent="startChangingPassword"
                    class="mt-3 inline-block text-sm font-semibold underline underline-offset-4">Buat kata sandi</a>
            </aside>
        @endif

        <!-- Main Content (2 Columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left Column: Avatar & Navigation Menu -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Avatar Card -->
                <div class="bg-white rounded-3xl border border-zinc-200/80 p-6 sm:p-8 flex flex-col items-center text-center shadow-sm">
                    <!-- Avatar Display -->
                    <div class="relative w-28 h-28 sm:w-32 sm:h-32 rounded-full overflow-hidden flex items-center justify-center">
                        @if ($avatar && ! $errors->has('avatar'))
                            <img src="{{ $avatar->temporaryUrl() }}" alt="Preview Foto"
                                class="w-full h-full object-cover" />
                        @elseif ($user?->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
                                class="w-full h-full object-cover" />
                        @else
                            <div class="w-full h-full bg-[#FCECE5] flex items-center justify-center">
                                <span class="text-[#1B3C2A] font-extrabold text-3xl tracking-wider">
                                    {{ $this->getInitials() }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Name & Username -->
                    <h3 class="mt-4 text-xl sm:text-2xl font-bold text-[#1B3C2A]">
                        {{ $user?->name }}
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 font-normal">
                        {{ '@' . ($user?->username ?? 'username') }}
                    </p>

                    <!-- Role Badge -->
                    <div class="mt-3">
                        <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-semibold bg-[#E8F3EE] text-[#1B3C2A]">
                            Pelanggan PawPaw
                        </span>
                    </div>

                    <!-- Ubah Foto Action -->
                    <div class="mt-4 w-full flex flex-col items-center">
                        <label for="client-avatar-input"
                            class="inline-flex items-center justify-center px-5 py-1.5 rounded-full border border-[#2D5945] text-[#2D5945] hover:bg-[#2D5945]/5 text-sm font-semibold transition cursor-pointer">
                            Ubah foto
                        </label>
                        <input type="file" id="client-avatar-input" wire:model="avatar"
                            accept="image/jpeg,image/png,image/webp" class="hidden" />

                        @if ($avatar)
                            <p class="mt-2 text-xs text-green-600 font-medium">
                                ✓ Foto siap disimpan
                            </p>
                        @endif

                        <flux:error name="avatar" class="mt-2 text-xs text-red-600" />
                    </div>

                    <!-- Separator -->
                    <div class="w-full border-t border-zinc-200/80 my-6"></div>

                    <!-- Join Date (No Location) -->
                    <p class="text-xs sm:text-sm text-zinc-500 font-normal">
                        Bergabung sejak {{ $user?->created_at ? $user->created_at->format('d M Y') : '-' }}
                    </p>
                </div>

                <!-- Navigation Menu (Informasi pribadi, Keamanan akun, Keluar dari akun) -->
                <div class="space-y-1" x-data="{ activeSection: 'profile' }">
                    <a href="#informasi-pribadi"
                        @click="activeSection = 'profile'"
                        :class="activeSection === 'profile' ? 'bg-[#EAF1EC] text-[#1B3C2A] font-semibold' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/40 font-medium'"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition cursor-pointer">
                        <flux:icon icon="user" class="size-5" />
                        <span>Informasi pribadi</span>
                    </a>

                    <form method="POST" action="{{ route('client.logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-semibold text-[#C2410C] hover:bg-red-50/80 transition cursor-pointer text-left">
                            <flux:icon icon="arrow-right-start-on-rectangle" class="size-5 text-[#C2410C]" />
                            <span>Keluar dari akun</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Personal Info & Security Cards -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Card 1: Informasi pribadi -->
                <div id="informasi-pribadi" class="scroll-mt-6 bg-white rounded-3xl border border-zinc-200/80 p-6 sm:p-8 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="user" class="size-6 text-[#1B3C2A]" />
                            <h2 class="text-xl sm:text-2xl font-bold text-[#1B3C2A]">Informasi pribadi</h2>
                        </div>
                        <div class="ml-auto flex max-w-full shrink-0 flex-wrap items-center justify-end gap-3">
                            @if (! $isEditing)
                                <button type="button" wire:click="startEditing"
                                    class="whitespace-nowrap rounded-full border border-[#2D5945] px-5 py-2 text-sm font-semibold text-[#2D5945] hover:bg-[#2D5945]/5">Edit profil</button>
                            @else
                                <button type="button" wire:click="cancelEditing" class="whitespace-nowrap rounded-full border border-zinc-300 px-5 py-2 text-sm font-semibold text-zinc-700">Batal</button>
                                <button type="submit" form="client-profile-form" wire:loading.attr="disabled" wire:target="save"
                                    class="whitespace-nowrap rounded-full bg-[#1B3C2A] px-5 py-2 text-sm font-semibold text-white hover:bg-[#153022]">
                                    <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                                    <span wire:loading wire:target="save">Menyimpan...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-zinc-500">Data ini digunakan klinik untuk menghubungimu terkait reservasi.</p>

                    <!-- Form Inputs (No Address field) -->
                    <form id="client-profile-form" wire:submit="save" class="mt-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Nama Lengkap -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Nama lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model.blur="formData.name"
                                    @disabled(! $isEditing)
                                    placeholder="Amanda Dealova"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.name" class="mt-1" />
                            </div>

                            <!-- Username -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Username <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model.blur="formData.username"
                                    @disabled(! $isEditing)
                                    placeholder="amandadealova"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.username" class="mt-1" />
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" wire:model.blur="formData.email"
                                    @disabled(! $isEditing)
                                    placeholder="amanda.dealova@example.com"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.email" class="mt-1" />
                                <x-email-change-controls :user="$user" :is-editing="$isEditing" :show-notice="false" />
                            </div>

                            <!-- Nomor WhatsApp / Telepon -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Nomor WhatsApp / telepon
                                </label>
                                <input type="tel" inputmode="numeric" maxlength="15" wire:model.blur="formData.phone_number"
                                    x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').slice(0, 15)"
                                    @disabled(! $isEditing)
                                    placeholder="0812 3456 7890"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.phone_number" class="mt-1" />
                            </div>
                        </div>

                        @if ($user?->pending_email)
                            <x-email-change-controls :user="$user" :show-password="false" />
                        @endif
                    </form>
                </div>

                <!-- Card 2: Keamanan Akun -->
                <div id="keamanan-akun" class="scroll-mt-6 bg-white rounded-3xl border border-zinc-200/80 p-6 sm:p-8 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <flux:icon icon="shield-check" class="size-6 text-[#1B3C2A]" />
                            <h2 class="text-xl sm:text-2xl font-bold text-[#1B3C2A]">Keamanan akun</h2>
                        </div>
                        <div class="ml-auto flex max-w-full shrink-0 flex-wrap items-center justify-end gap-3">
                            @if (! $isChangingPassword)
                                <button type="button" wire:click="startChangingPassword"
                                    class="whitespace-nowrap rounded-full border border-[#2D5945] px-5 py-2 text-sm font-semibold text-[#2D5945] hover:bg-[#2D5945]/5">
                                    {{ blank($user?->password) ? 'Buat kata sandi' : 'Ubah kata sandi' }}
                                </button>
                            @else
                                <button type="button" wire:click="cancelChangingPassword" class="whitespace-nowrap rounded-full border border-zinc-300 px-5 py-2 text-sm font-semibold text-zinc-700">Batal</button>
                                <button type="submit" form="client-password-form" wire:loading.attr="disabled" wire:target="updatePassword"
                                    class="whitespace-nowrap rounded-full bg-[#1B3C2A] px-5 py-2 text-sm font-semibold text-white hover:bg-[#153022]">
                                    <span wire:loading.remove wire:target="updatePassword">Simpan Kata Sandi</span>
                                    <span wire:loading wire:target="updatePassword">Menyimpan...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-zinc-500">Gunakan kata sandi unik untuk menjaga akunmu tetap aman.</p>

                    <!-- Form Ubah Kata Sandi (Expandable) -->
                    @if ($isChangingPassword)
                        <form id="client-password-form" wire:submit="updatePassword" class="mt-6 pt-6 border-t border-zinc-100 space-y-4">
                            @if (! empty($user?->password))
                                <div>
                                    <label for="client-current-password" class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                        Kata sandi saat ini <span class="text-red-500">*</span>
                                    </label>
                                    <div x-data="{ show: false }" class="relative">
                                        <input id="client-current-password" data-password-focus autocomplete="current-password" :type="show ? 'text' : 'password'" wire:model.blur="currentPassword"
                                            placeholder="Masukkan kata sandi saat ini"
                                            class="w-full rounded-2xl border px-4 py-3 pr-10 text-sm transition outline-none border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20" />
                                        <button type="button" @click="show = !show"
                                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 transition cursor-pointer"
                                            aria-label="Tampilkan kata sandi">
                                            <template x-if="!show">
                                                <flux:icon icon="eye" class="size-4" />
                                            </template>
                                            <template x-if="show">
                                                <flux:icon icon="eye-slash" class="size-4" />
                                            </template>
                                        </button>
                                    </div>
                                    <flux:error name="currentPassword" class="mt-1" />
                                </div>
                            @else
                                <p class="text-xs text-amber-700 bg-amber-50 rounded-xl p-3 border border-amber-200">
                                    Akun Anda terhubung tanpa kata sandi awal (Google Login). Silakan buat kata sandi baru untuk akun Anda.
                                </p>
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="client-new-password" class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                        Kata sandi baru <span class="text-red-500">*</span>
                                    </label>
                                    <div x-data="{ show: false }" class="relative">
                                        <input id="client-new-password" data-password-focus autocomplete="new-password" :type="show ? 'text' : 'password'" wire:model.blur="newPassword"
                                            placeholder="Minimal 8 karakter"
                                            class="w-full rounded-2xl border px-4 py-3 pr-10 text-sm transition outline-none border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20" />
                                        <button type="button" @click="show = !show"
                                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 transition cursor-pointer"
                                            aria-label="Tampilkan kata sandi">
                                            <template x-if="!show">
                                                <flux:icon icon="eye" class="size-4" />
                                            </template>
                                            <template x-if="show">
                                                <flux:icon icon="eye-slash" class="size-4" />
                                            </template>
                                        </button>
                                    </div>
                                    <flux:error name="newPassword" class="mt-1" />
                                </div>

                                <div>
                                    <label for="client-password-confirmation" class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                        Konfirmasi kata sandi baru <span class="text-red-500">*</span>
                                    </label>
                                    <div x-data="{ show: false }" class="relative">
                                        <input id="client-password-confirmation" autocomplete="new-password" :type="show ? 'text' : 'password'" wire:model.blur="newPasswordConfirmation"
                                            placeholder="Ulangi kata sandi baru"
                                            class="w-full rounded-2xl border px-4 py-3 pr-10 text-sm transition outline-none border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20" />
                                        <button type="button" @click="show = !show"
                                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 transition cursor-pointer"
                                            aria-label="Tampilkan kata sandi">
                                            <template x-if="!show">
                                                <flux:icon icon="eye" class="size-4" />
                                            </template>
                                            <template x-if="show">
                                                <flux:icon icon="eye-slash" class="size-4" />
                                            </template>
                                        </button>
                                    </div>
                                    <flux:error name="newPasswordConfirmation" class="mt-1" />
                                </div>
                            </div>

                        </form>
                    @endif

                    <!-- Separator -->
                    <div class="w-full border-t border-zinc-100 my-4"></div>

                    <!-- Security Warning Note -->
                    <p class="text-xs text-zinc-400 font-normal">
                        Jangan bagikan kata sandi atau kode akses kepada siapa pun, termasuk pihak yang mengatasnamakan PawPaw.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
