<?php

use App\Actions\Admin\OwnerProfile\OwnerProfileAction;
use App\Enums\StaffPosition;
use App\Enums\SystemMessage;
use App\Models\User;
use App\Services\ImageService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    public ?User $user = null;

    public array $formData = [];

    public $avatar = null;

    public bool $isEditing = false;

    public bool $isChangingPassword = false;

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function rules(): array
    {
        $userId = $this->user?->id;
        $vendorId = app('vendor.context')->id();

        return [
            'formData.name' => ['required', 'string', 'min:3', 'max:100'],
            'formData.username' => ['nullable', 'string', 'min:4', 'max:50', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($userId)],
            'formData.email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId), Rule::unique('vendors', 'email')->ignore($vendorId)],
            'formData.phone_number' => ['required', 'string', 'min:10', 'max:20', 'regex:/\A\+?[0-9]{10,20}\z/', Rule::unique('users', 'phone_number')->ignore($userId), Rule::unique('vendors', 'contact')->ignore($vendorId)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'formData.name.required' => 'Nama lengkap harus diisi',
            'formData.name.min' => 'Nama lengkap minimal 3 karakter',
            'formData.name.max' => 'Nama lengkap maksimal 100 karakter',
            'formData.username.min' => 'Username minimal 4 karakter',
            'formData.username.max' => 'Username maksimal 50 karakter',
            'formData.username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_)',
            'formData.username.unique' => 'Username sudah digunakan',
            'formData.email.required' => 'Email harus diisi',
            'formData.email.email' => 'Format email tidak valid',
            'formData.email.unique' => 'Email sudah terdaftar',
            'formData.phone_number.required' => 'Nomor WhatsApp / telepon harus diisi',
            'formData.phone_number.min' => 'Nomor WhatsApp / telepon minimal 10 digit',
            'formData.phone_number.max' => 'Nomor WhatsApp / telepon maksimal 20 digit',
            'formData.phone_number.regex' => 'Nomor WhatsApp / telepon harus berisi 10-20 digit angka, dapat diawali +',
            'formData.phone_number.unique' => 'Nomor WhatsApp / telepon sudah terdaftar',
            'avatar.image' => 'File harus berupa gambar',
            'avatar.mimes' => 'Format gambar harus JPEG, PNG, atau WebP',
            'avatar.max' => 'Ukuran gambar maksimal 2 MB',
        ];
    }

    public function mount(): void
    {
        $this->user = Auth::guard('vendor')->user();
        $this->fillFormData();
    }

    protected function fillFormData(): void
    {
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
        $this->fillFormData();
        $this->resetErrorBag();
        $this->isEditing = false;
    }

    public function save(OwnerProfileAction $action, ImageService $imageService): void
    {
        $this->validate();

        $data = [
            'name' => trim($this->formData['name']),
            'username' => filled($this->formData['username'])
                ? strtolower(trim($this->formData['username']))
                : null,
            'email' => strtolower(trim($this->formData['email'])),
            'phone_number' => preg_replace('/[\s()-]+/', '', trim($this->formData['phone_number'])),
        ];

        if ($this->avatar) {
            if ($this->user->avatar) {
                Storage::disk('public')->delete($this->user->avatar);
            }

            $data['avatar'] = $imageService->uploadAndConvertToWebp($this->avatar, 'avatars');
        }

        $this->user = $action->execute($this->user, $data);
        $this->avatar = null;
        $this->fillFormData();
        $this->isEditing = false;

        $newAvatarUrl = $this->user->avatar ? Storage::url($this->user->avatar) : '';
        $this->js("window.dispatchEvent(new CustomEvent('owner-avatar-updated', { detail: { url: " . json_encode($newAvatarUrl) . " } }))");

        Flux::toast(SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
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

    public function getRoleLabel(): string
    {
        return match ($this->user?->staffPosition()) {
            StaffPosition::Owner => 'Owner',
            StaffPosition::FrontOffice => 'Front Office',
            default => 'Staff',
        };
    }

    public function startChangingPassword(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetErrorBag();
        $this->isChangingPassword = true;
    }

    public function cancelChangingPassword(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetErrorBag();
        $this->isChangingPassword = false;
    }

    public function updatePassword(): void
    {
        $hasPassword = ! empty($this->user?->password);

        $rules = [
            'newPassword' => [
                'required',
                'string',
                'min:8',
                'max:72',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('Kata sandi terlalu panjang. Maksimal 72 byte.');
                    }
                },
            ],
            'newPasswordConfirmation' => ['required', 'string', 'min:8', 'same:newPassword'],
        ];

        if ($hasPassword) {
            $rules['currentPassword'] = ['required', 'string'];
        }

        $messages = [
            'currentPassword.required' => 'Kata sandi saat ini wajib diisi.',
            'newPassword.required' => 'Kata sandi baru wajib diisi.',
            'newPassword.min' => 'Kata sandi baru minimal 8 karakter.',
            'newPassword.max' => 'Kata sandi baru maksimal 72 karakter.',
            'newPasswordConfirmation.required' => 'Konfirmasi kata sandi wajib diisi.',
            'newPasswordConfirmation.same' => 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.',
        ];

        $this->validate($rules, $messages, [
            'currentPassword' => 'Kata sandi saat ini',
            'newPassword' => 'Kata sandi baru',
            'newPasswordConfirmation' => 'Konfirmasi kata sandi baru',
        ]);

        if ($hasPassword) {
            if (! Hash::check($this->currentPassword, $this->user->password)) {
                $this->addError('currentPassword', 'Kata sandi saat ini tidak sesuai.');

                return;
            }

            if (Hash::check($this->newPassword, $this->user->password)) {
                $this->addError('newPassword', 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.');

                return;
            }
        }

        $this->user->update(['password' => $this->newPassword]);

        $this->user->refresh();
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetErrorBag();
        $this->isChangingPassword = false;

        Flux::toast('Kata sandi berhasil diperbarui.', variant: 'success');
    }
};
?>

<div class="py-2 sm:py-4">
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <p class="text-xs font-bold tracking-wider text-[#EE6D52] uppercase mb-1">
                AKUN {{ strtoupper($this->getRoleLabel()) }}
            </p>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-[#1B3C2A] tracking-tight">
                Profil {{ $this->getRoleLabel() }}
            </h1>
            <p class="mt-2 text-sm sm:text-base text-zinc-500">
                Kelola informasi akunmu sebagai {{ $this->getRoleLabel() }} di {{ app('vendor.context')->get()?->name }}.
            </p>
        </div>

        <!-- Main Content (2 Columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left Column: Avatar & Navigation Menu -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Avatar Card -->
                <div class="bg-white rounded-3xl border border-zinc-200/80 p-6 sm:p-8 flex flex-col items-center text-center shadow-sm">
                    <div class="relative w-28 h-28 sm:w-32 sm:h-32 rounded-full overflow-hidden flex items-center justify-center">
                        @if ($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" alt="Preview Foto"
                                class="w-full h-full object-cover" />
                        @elseif ($user?->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}"
                                class="w-full h-full object-cover" />
                        @else
                            <div class="w-full h-full bg-[#FCECE5] flex items-center justify-center">
                                <span class="text-[#1B3C2A] font-extrabold text-3xl tracking-wider">
                                    {{ $this->getInitials() }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <h3 class="mt-4 text-xl sm:text-2xl font-bold text-[#1B3C2A]">
                        {{ $user?->name }}
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 font-normal">
                        {{ $user?->username ? '@' . $user->username : $user?->email }}
                    </p>

                    <div class="mt-3">
                        <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-semibold bg-[#E8F3EE] text-[#1B3C2A]">
                            {{ $this->getRoleLabel() }} PawPaw
                        </span>
                    </div>

                    <div class="mt-4 w-full flex flex-col items-center">
                        <label for="owner-avatar-input"
                            class="inline-flex items-center justify-center px-5 py-1.5 rounded-full border border-[#2D5945] text-[#2D5945] hover:bg-[#2D5945]/5 text-sm font-semibold transition cursor-pointer">
                            Ubah foto
                        </label>
                        <input type="file" id="owner-avatar-input" wire:model="avatar"
                            accept="image/jpeg,image/png,image/webp" class="hidden" />

                        @if ($avatar)
                            <p class="mt-2 text-xs text-green-600 font-medium">
                                ✓ Foto siap disimpan
                            </p>
                        @endif

                        <flux:error name="avatar" class="mt-2 text-xs text-red-600" />
                    </div>

                    <div class="w-full border-t border-zinc-200/80 my-6"></div>

                    <p class="text-xs sm:text-sm text-zinc-500 font-normal">
                        Bergabung sejak {{ $user?->created_at ? $user->created_at->format('d M Y') : '-' }}
                    </p>
                </div>

                <!-- Navigation Menu -->
                <div class="space-y-1" x-data="{ activeSection: 'profile' }">
                    <a href="#informasi-pribadi"
                        @click="activeSection = 'profile'"
                        :class="activeSection === 'profile' ? 'bg-[#EAF1EC] text-[#1B3C2A] font-semibold' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/40 font-medium'"
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition cursor-pointer">
                        <flux:icon icon="user" class="size-5" />
                        <span>Informasi pribadi</span>
                    </a>

                    <form method="POST" action="{{ route('vendor.admin.logout') }}">
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
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200/80">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-[#1B3C2A]">
                                Informasi pribadi
                            </h2>
                            <p class="mt-1 text-xs sm:text-sm text-zinc-500">
                                Data ini dipakai untuk akun login dan kontak pengelola mitra.
                            </p>
                        </div>

                        <div>
                            @if (! $isEditing)
                                <button type="button" wire:click="startEditing"
                                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full border border-[#2D5945] text-[#2D5945] hover:bg-[#2D5945]/5 text-sm font-semibold transition cursor-pointer">
                                    <span>Edit profil</span>
                                    <flux:icon icon="pencil" class="size-4 text-[#2D5945]" />
                                </button>
                            @else
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="cancelEditing"
                                        class="px-4 py-2 rounded-full text-zinc-600 hover:text-zinc-900 text-sm font-medium transition cursor-pointer">
                                        Batal
                                    </button>
                                    <button type="submit" form="owner-profile-form"
                                        class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-[#1B3C2A] text-white hover:bg-[#153022] text-sm font-semibold transition shadow-sm cursor-pointer">
                                        <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                                        <span wire:loading wire:target="save">Menyimpan...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <form id="owner-profile-form" wire:submit="save" class="mt-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Nama lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model.blur="formData.name"
                                    @disabled(! $isEditing)
                                    placeholder="Nama lengkap pengelola"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.name" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Username
                                </label>
                                <input type="text" wire:model.blur="formData.username"
                                    @disabled(! $isEditing)
                                    placeholder="username"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.username" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" wire:model.blur="formData.email"
                                    @disabled(! $isEditing)
                                    placeholder="owner@klinik.com"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.email" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Nomor WhatsApp / telepon <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model.blur="formData.phone_number"
                                    @disabled(! $isEditing)
                                    placeholder="0812 3456 7890"
                                    class="w-full rounded-2xl border px-4 py-3 text-sm transition outline-none {{ $isEditing ? 'border-[#D9D0C5] bg-[#FCFAF6] text-zinc-900 focus:border-[#2D5945] focus:ring-2 focus:ring-[#2D5945]/20' : 'border-[#EAE4DC] bg-[#FCFAF6] text-zinc-800 cursor-not-allowed' }}" />
                                <flux:error name="formData.phone_number" class="mt-1" />
                            </div>

                            <!-- Read-only: Peran & Mitra -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Peran
                                </label>
                                <input type="text" value="{{ $this->getRoleLabel() }}" disabled
                                    class="w-full rounded-2xl border border-[#EAE4DC] bg-[#F4F1EA] px-4 py-3 text-sm text-zinc-600 cursor-not-allowed" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                    Mitra
                                </label>
                                <input type="text" value="{{ app('vendor.context')->get()?->name }}" disabled
                                    class="w-full rounded-2xl border border-[#EAE4DC] bg-[#F4F1EA] px-4 py-3 text-sm text-zinc-600 cursor-not-allowed" />
                            </div>
                        </div>

                        @if ($isEditing)
                            <div class="pt-4 border-t border-zinc-100 flex justify-end gap-3 sm:hidden">
                                <button type="button" wire:click="cancelEditing"
                                    class="px-4 py-2 rounded-full text-zinc-600 hover:text-zinc-900 text-sm font-medium transition cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-[#1B3C2A] text-white hover:bg-[#153022] text-sm font-semibold transition shadow-sm cursor-pointer">
                                    <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                                    <span wire:loading wire:target="save">Menyimpan...</span>
                                </button>
                            </div>
                        @endif
                    </form>
                </div>

                <!-- Card 2: Keamanan Akun -->
                <div id="keamanan-akun" class="scroll-mt-6 bg-white rounded-3xl border border-zinc-200/80 p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <flux:icon icon="shield-check" class="size-6 text-[#1B3C2A]" />
                        <h2 class="text-xl sm:text-2xl font-bold text-[#1B3C2A]">
                            Keamanan akun
                        </h2>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="text-sm sm:text-base font-semibold text-zinc-800">
                                Kata sandi
                            </h3>
                            <p class="mt-0.5 text-xs sm:text-sm text-zinc-500">
                                Gunakan kata sandi unik untuk menjaga akunmu tetap aman.
                            </p>
                        </div>

                        <div>
                            @if (! $isChangingPassword)
                                <button type="button" wire:click="startChangingPassword"
                                    class="inline-flex items-center gap-2 px-5 py-2 rounded-full border border-[#2D5945] text-[#2D5945] hover:bg-[#2D5945]/5 text-sm font-semibold transition cursor-pointer">
                                    <span>Ubah kata sandi</span>
                                    <flux:icon icon="lock-closed" class="size-4 text-[#2D5945]" />
                                </button>
                            @else
                                <button type="button" wire:click="cancelChangingPassword"
                                    class="px-4 py-2 rounded-full text-zinc-600 hover:text-zinc-900 text-sm font-medium transition cursor-pointer">
                                    Batal
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($isChangingPassword)
                        <form wire:submit="updatePassword" class="mt-6 pt-6 border-t border-zinc-100 space-y-4">
                            @if (! empty($user?->password))
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                        Kata sandi saat ini <span class="text-red-500">*</span>
                                    </label>
                                    <div x-data="{ show: false }" class="relative">
                                        <input :type="show ? 'text' : 'password'" wire:model.blur="currentPassword"
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
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                        Kata sandi baru <span class="text-red-500">*</span>
                                    </label>
                                    <div x-data="{ show: false }" class="relative">
                                        <input :type="show ? 'text' : 'password'" wire:model.blur="newPassword"
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
                                    <label class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                                        Konfirmasi kata sandi baru <span class="text-red-500">*</span>
                                    </label>
                                    <div x-data="{ show: false }" class="relative">
                                        <input :type="show ? 'text' : 'password'" wire:model.blur="newPasswordConfirmation"
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

                            <div class="pt-2 flex justify-end gap-3">
                                <button type="button" wire:click="cancelChangingPassword"
                                    class="px-5 py-2.5 rounded-full border border-zinc-300 text-zinc-700 hover:bg-zinc-50 text-sm font-semibold transition cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-[#1B3C2A] text-white hover:bg-[#153022] text-sm font-semibold transition shadow-sm cursor-pointer">
                                    <span wire:loading.remove wire:target="updatePassword">Simpan Kata Sandi</span>
                                    <span wire:loading wire:target="updatePassword">Menyimpan...</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    <div class="w-full border-t border-zinc-100 my-4"></div>

                    <p class="text-xs text-zinc-400 font-normal">
                        Jangan bagikan kata sandi atau kode akses kepada siapa pun, termasuk pihak yang mengatasnamakan PawPaw.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
