<?php

use App\Actions\Client\ProfileClient\ProfileClientAction;
use App\Enums\SystemMessage;
use App\Models\User;
use App\Services\ImageService;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
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

        if (! empty($this->formData['phone_number'])) {
            $rules['formData.phone_number'] = ['string', 'min:10', 'max:20', 'regex:/\A\+?[0-9]{10,20}\z/', 'unique:users,phone_number,' . $userId, 'unique:vendors,contact'];
        } else {
            $rules['formData.phone_number'] = ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'formData.name.required' => 'Nama lengkap harus diisi',
            'formData.name.min' => 'Nama lengkap minimal 3 karakter',
            'formData.name.max' => 'Nama lengkap maksimal 100 karakter',
            'formData.username.required' => 'Username harus diisi',
            'formData.username.min' => 'Username minimal 4 karakter',
            'formData.username.max' => 'Username maksimal 50 karakter',
            'formData.username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_)',
            'formData.username.unique' => 'Username sudah digunakan',
            'formData.email.required' => 'Email harus diisi',
            'formData.email.email' => 'Format email tidak valid',
            'formData.email.unique' => 'Email sudah terdaftar',
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
        $this->user = auth()->user();
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
        $this->formData = [
            'name' => $this->user?->name ?? '',
            'username' => $this->user?->username ?? '',
            'email' => $this->user?->email ?? '',
            'phone_number' => $this->user?->phone_number ?? '',
        ];
        $this->resetErrorBag();
        $this->isEditing = false;
    }

    public function save(ProfileClientAction $action, ImageService $imageService): void
    {
        if (is_string($this->formData['phone_number'] ?? null)) {
            $this->formData['phone_number'] = preg_replace('/[\s()-]+/', '', trim($this->formData['phone_number']));
        }

        $this->validate();

        $data = [
            'name' => trim($this->formData['name']),
            'username' => strtolower(trim($this->formData['username'])),
            'email' => strtolower(trim($this->formData['email'])),
            'phone_number' => ! empty($this->formData['phone_number'])
                ? preg_replace('/[\s()-]+/', '', trim($this->formData['phone_number']))
                : null,
        ];

        if ($this->avatar) {
            if ($this->user->avatar) {
                Storage::disk('public')->delete($this->user->avatar);
            }

            $data['avatar'] = $imageService->uploadAndConvertToWebp($this->avatar, 'avatars');
        }

        $action->execute($this->user->id, $data);

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

        $this->user->update([
            'password' => $this->newPassword,
        ]);

        $this->user->refresh();
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->resetErrorBag();
        $this->isChangingPassword = false;

        Flux::toast('Kata sandi berhasil diperbarui.', variant: 'success');
    }
};
?>

<div class="profile-page" x-data="{
    activeSection: 'informasi-pribadi',
    updateSection() {
        const security = document.getElementById('keamanan-akun');
        this.activeSection = security && security.getBoundingClientRect().top <= 200 ? 'keamanan-akun' : 'informasi-pribadi';
    }
}" x-init="$nextTick(() => updateSection())" @scroll.window.throttle.150ms="updateSection()">
    <div class="clinic-request-progress" wire:loading.delay aria-hidden="true"><span></span></div>
    @include('components.client.components.clinic-atmosphere')
    <div class="profile-wrap">
        <nav aria-label="Breadcrumb" class="profile-breadcrumb">
            <a href="{{ route('landing_page') }}" wire:navigate>Beranda</a>
            <span aria-hidden="true">/</span><span aria-current="page">Profil saya</span>
        </nav>
        <header class="profile-intro" data-animate="fade-up" data-motion-once>
            <p class="profile-eyebrow">AKUN PELANGGAN</p>
            <h1>Kenalan lebih dekat<span>, yuk.</span></h1>
            <p>Informasimu tersimpan rapi, perawatan si kesayangan jadi lebih praktis.</p>
            <img class="profile-intro-paw" src="{{ asset('assets/pawkucing.webp') }}" alt="" width="42" height="42" aria-hidden="true">
        </header>

        <div class="profile-layout">
            <aside class="profile-identity" aria-label="Identitas akun">
                <div class="profile-identity-card">
                    <div class="profile-avatar-blob">
                        @if ($avatar && ! $errors->has('avatar'))
                            <div class="profile-avatar-preview" x-data="{ failed: false }" wire:key="profile-avatar-preview-{{ $avatar->getFilename() }}">
                                <span aria-hidden="true">{{ $this->getInitials() }}</span>
                                <img src="{{ $avatar->temporaryUrl() }}" alt="Pratinjau foto profil baru" x-show="!failed" x-on:error="failed = true">
                            </div>
                        @else
                            <x-client.components.account-avatar :user="$user" class="profile-avatar-image" />
                        @endif
                        <span class="profile-avatar-spark" aria-hidden="true">✦</span>
                    </div>
                    <div class="profile-identity-copy">
                        <h2>{{ $user?->name }}</h2>
                        <p class="profile-username">{{ '@'.($user?->username ?? '') }}</p>
                        <span class="profile-role">Pelanggan PawPaw</span>
                    </div>
                    <div class="profile-photo-picker" x-data="{ uploading: false, progress: 0, uploadError: false }"
                        x-on:livewire-upload-start="uploading = true; uploadError = false; progress = 0"
                        x-on:livewire-upload-finish="uploading = false"
                        x-on:livewire-upload-cancel="uploading = false"
                        x-on:livewire-upload-error="uploading = false; uploadError = true"
                        x-on:livewire-upload-progress="progress = $event.detail.progress">
                        <input type="file" id="client-avatar-input" wire:model="avatar" class="sr-only"
                            accept="image/jpeg,image/png,image/webp" aria-describedby="profile-photo-help profile-photo-error" wire:loading.attr="disabled" wire:target="save">
                        <label for="client-avatar-input" class="profile-button profile-button-outline"><flux:icon.camera class="size-4" />Ubah foto</label>
                        <p id="profile-photo-help" class="profile-help">JPG, PNG, atau WebP · Maks. 2 MB</p>
                        <div x-show="uploading" x-cloak role="status" class="profile-upload-status">
                            <span>Mengunggah foto… <span x-text="progress + '%'"></span></span>
                            <progress max="100" :value="progress" aria-label="Progres unggah foto"></progress>
                        </div>
                        <p x-show="uploadError" x-cloak role="alert" class="profile-error">Foto belum berhasil diunggah. Coba pilih lagi.</p>
                        @if ($avatar && ! $errors->has('avatar'))
                            <p class="profile-photo-ready" role="status">Foto siap. Tekan Simpan perubahan di formulir.</p>
                        @endif
                        <div id="profile-photo-error"><flux:error name="avatar" /></div>
                    </div>
                    <p class="profile-joined">Bergabung sejak <span>{{ $user?->created_at ? $user->created_at->locale('id')->isoFormat('D MMMM YYYY') : '—' }}</span></p>
                </div>
                <nav class="profile-section-nav" aria-label="Bagian profil">
                    <a href="#informasi-pribadi" :aria-current="activeSection === 'informasi-pribadi' ? 'location' : null" :class="{ 'is-active': activeSection === 'informasi-pribadi' }">
                        <flux:icon.user class="size-5" />Informasi pribadi<flux:icon.arrow-down class="size-4" />
                    </a>
                    <a href="#keamanan-akun" :aria-current="activeSection === 'keamanan-akun' ? 'location' : null" :class="{ 'is-active': activeSection === 'keamanan-akun' }">
                        <flux:icon.shield-check class="size-5" />Keamanan akun<flux:icon.arrow-down class="size-4" />
                    </a>
                </nav>
            </aside>

            <div class="profile-content">
                <section id="informasi-pribadi" class="profile-panel" aria-labelledby="profile-information-heading">
                    <div class="profile-panel-heading">
                        <span class="profile-section-icon" aria-hidden="true"><flux:icon.user class="size-6" /></span>
                        <div><h2 id="profile-information-heading">Informasi pribadi</h2><p>Supaya klinik mudah menghubungimu terkait reservasi.</p></div>
                        @if (! $isEditing)
                            <button type="button" wire:click="startEditing" class="profile-button profile-button-outline"><flux:icon.pencil class="size-4" />Edit profil</button>
                        @else
                            <span class="profile-edit-badge" role="status">Mode edit</span>
                        @endif
                    </div>
                    @php
                        $profileFields = [
                            'name' => ['label' => 'Nama lengkap', 'type' => 'text', 'autocomplete' => 'name', 'max' => 100, 'help' => '', 'placeholder' => 'Nama lengkapmu'],
                            'username' => ['label' => 'Username', 'type' => 'text', 'autocomplete' => 'username', 'max' => 50, 'help' => '4–50 karakter: huruf, angka, tanda - atau _.', 'placeholder' => 'Username pilihanmu'],
                            'email' => ['label' => 'Email', 'type' => 'email', 'autocomplete' => 'email', 'max' => 255, 'help' => 'Gunakan email aktif yang bisa kamu akses.', 'placeholder' => 'nama@contoh.com'],
                            'phone_number' => ['label' => 'WhatsApp / telepon', 'type' => 'tel', 'autocomplete' => 'tel', 'max' => 30, 'help' => '10–20 digit, misalnya 081234567890 atau +6281234567890.', 'placeholder' => '081234567890'],
                        ];
                    @endphp
                    @if ($isEditing)
                        <form id="client-profile-form" wire:submit="save" class="profile-edit-form">
                            <p class="profile-form-note">Perbarui informasi yang diperlukan. <span aria-hidden="true">*</span> menandai kolom wajib.</p>
                            <div class="profile-fields">
                                @foreach ($profileFields as $key => $field)
                                    <div class="profile-field" wire:key="profile-field-{{ $key }}">
                                        <label for="profile-{{ $key }}">{{ $field['label'] }} @if ($key !== 'phone_number')<span aria-hidden="true">*</span>@endif</label>
                                        <input id="profile-{{ $key }}" name="{{ $key }}" type="{{ $field['type'] }}" wire:model.blur="formData.{{ $key }}"
                                            autocomplete="{{ $field['autocomplete'] }}" maxlength="{{ $field['max'] }}" placeholder="{{ $field['placeholder'] }}"
                                            @required($key !== 'phone_number') @if ($key === 'phone_number') inputmode="tel" @endif
                                            aria-invalid="{{ $errors->has('formData.'.$key) ? 'true' : 'false' }}" aria-describedby="profile-{{ $key }}-help profile-{{ $key }}-error">
                                        <p id="profile-{{ $key }}-help" class="profile-help">{{ $field['help'] }}</p>
                                        <div id="profile-{{ $key }}-error"><flux:error name="formData.{{ $key }}" /></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="profile-form-actions">
                                <button type="button" wire:click="cancelEditing" wire:loading.attr="disabled" wire:target="save,avatar" class="profile-button profile-button-outline">Batal</button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="save,avatar" class="profile-button profile-button-primary">
                                    <span wire:loading.remove wire:target="save">Simpan perubahan</span><span wire:loading wire:target="save" role="status">Menyimpan…</span>
                                    <flux:icon.check class="size-4" />
                                </button>
                            </div>
                        </form>
                    @else
                        <dl class="profile-data">
                            @foreach ($profileFields as $key => $field)
                                <div><dt>{{ $field['label'] }}</dt><dd>{{ $formData[$key] ?: 'Belum ditambahkan' }}</dd></div>
                            @endforeach
                        </dl>
                        <p class="profile-view-hint"><flux:icon.pencil class="size-4" />Klik Edit profil untuk memperbarui informasi.</p>
                    @endif
                </section>

                <section id="keamanan-akun" class="profile-panel profile-security" aria-labelledby="profile-security-heading">
                    <div class="profile-panel-heading">
                        <span class="profile-section-icon" aria-hidden="true"><flux:icon.shield-check class="size-6" /></span>
                        <div><h2 id="profile-security-heading">Keamanan akun</h2><p>Jaga akunmu, biar tetap nyaman merawatnya.</p></div>
                    </div>
                    @if (! $isChangingPassword)
                        <div class="profile-security-summary"><div><h3>{{ empty($user?->password) ? 'Buat kata sandi' : 'Kata sandi' }}</h3><p>Gunakan kata sandi unik yang tidak dipakai di akun lain.</p></div>
                            <button type="button" wire:click="startChangingPassword" class="profile-button profile-button-outline"><flux:icon.lock-closed class="size-4" />{{ empty($user?->password) ? 'Buat kata sandi' : 'Ubah kata sandi' }}</button>
                        </div>
                    @else
                        <form wire:submit="updatePassword" class="profile-password-form">
                            @if (empty($user?->password))
                                <p class="profile-google-note">Kamu masuk melalui Google dan belum memiliki kata sandi. Buat kata sandi jika ingin masuk dengan email dan kata sandi.</p>
                            @endif
                            <p class="profile-form-note">Semua kolom kata sandi wajib diisi.</p>
                            <div class="profile-fields">
                                @foreach ([
                                    'currentPassword' => ['label' => 'Kata sandi saat ini', 'autocomplete' => 'current-password', 'help' => 'Masukkan kata sandi yang sedang kamu gunakan.'],
                                    'newPassword' => ['label' => 'Kata sandi baru', 'autocomplete' => 'new-password', 'help' => 'Minimal 8 karakter. Gunakan kata sandi unik yang sulit ditebak.'],
                                    'newPasswordConfirmation' => ['label' => 'Konfirmasi kata sandi baru', 'autocomplete' => 'new-password', 'help' => 'Ketik kembali kata sandi baru yang sama.'],
                                ] as $key => $field)
                                    @continue($key === 'currentPassword' && empty($user?->password))
                                    <div class="profile-field {{ $key === 'currentPassword' ? 'profile-field-wide' : '' }}" wire:key="profile-password-{{ $key }}">
                                        <label for="profile-{{ $key }}">{{ $field['label'] }} <span aria-hidden="true">*</span></label>
                                        <div class="profile-password-control" x-data="{ show: false }">
                                            <input id="profile-{{ $key }}" name="{{ $key }}" :type="show ? 'text' : 'password'" type="password" wire:model.blur="{{ $key }}" required
                                                autocomplete="{{ $field['autocomplete'] }}" @if ($key !== 'currentPassword') minlength="8" @endif
                                                aria-invalid="{{ $errors->has($key) ? 'true' : 'false' }}" aria-describedby="profile-{{ $key }}-help profile-{{ $key }}-error">
                                            <button type="button" @click="show = !show" :aria-pressed="show.toString()"
                                                :aria-label="show ? @js('Sembunyikan '.$field['label']) : @js('Tampilkan '.$field['label'])" aria-controls="profile-{{ $key }}">
                                                <flux:icon.eye x-show="!show" class="size-5" /><flux:icon.eye-slash x-show="show" x-cloak class="size-5" />
                                            </button>
                                        </div>
                                        <p id="profile-{{ $key }}-help" class="profile-help">{{ $field['help'] }}</p>
                                        <div id="profile-{{ $key }}-error"><flux:error name="{{ $key }}" /></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="profile-form-actions">
                                <button type="button" wire:click="cancelChangingPassword" wire:loading.attr="disabled" wire:target="updatePassword" class="profile-button profile-button-outline">Batal</button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" class="profile-button profile-button-primary"><span wire:loading.remove wire:target="updatePassword">Simpan kata sandi</span><span wire:loading wire:target="updatePassword" role="status">Menyimpan…</span></button>
                            </div>
                        </form>
                    @endif
                    <p class="profile-security-note"><flux:icon.lock-closed class="size-4" />Jangan bagikan kata sandi atau kode akses kepada siapa pun, termasuk pihak yang mengatasnamakan PawPaw.</p>
                </section>
            </div>
        </div>
        <div class="profile-session"><div><strong>Selesai mengelola akun?</strong><p>Keluar jika perangkat ini digunakan bersama.</p></div><form method="POST" action="{{ route('client.logout') }}">@csrf<button type="submit" class="profile-button profile-button-exit"><flux:icon.arrow-right-start-on-rectangle class="size-4" />Keluar dari akun</button></form></div>
    </div>
</div>
