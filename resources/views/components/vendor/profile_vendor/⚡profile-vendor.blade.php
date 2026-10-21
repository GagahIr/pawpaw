<?php

use App\Models\Vendor;
use App\Enums\SystemMessage;
use App\Actions\Admin\ProfileVendor\ProfileVendorAction;
use App\Services\ImageService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Flux\Flux;

new #[Layout('layouts::admin')] class extends Component {
    use WithFileUploads;

    public ?Vendor $vendor = null;

    public array $formData = [];

    public $logo;

    public function rules(): array
    {
        $vendorId = $this->vendor?->id;
        $userId = auth()->id();

        return [
            'formData.name' => 'required|string|max:255',
            'formData.email' => 'required|email|max:255|unique:vendors,email,' . $vendorId . '|unique:users,email,' . $userId,
            'formData.address' => 'required|string|max:500',
            'formData.contact' => 'required|string|max:20|unique:vendors,contact,' . $vendorId . '|unique:users,phone_number,' . $userId,
            'formData.description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'formData.name.required' => 'Nama vendor harus diisi',
            'formData.email.required' => 'Email harus diisi',
            'formData.email.email' => 'Format email tidak valid',
            'formData.email.unique' => 'Email sudah terdaftar',
            'formData.contact.unique' => 'Nomor kontak sudah terdaftar',
            'formData.address.required' => 'Alamat harus diisi',
            'formData.contact.required' => 'Kontak harus diisi',
            'logo.image' => 'File harus berupa gambar',
            'logo.max' => 'Ukuran gambar maksimal 2 MB',
        ];
    }

    public function mount()
    {
        $this->vendor = auth()->user()->vendor;
        $this->formData = [
            'name' => $this->vendor?->name,
            'email' => $this->vendor?->email,
            'address' => $this->vendor?->address,
            'contact' => $this->vendor?->contact,
            'description' => $this->vendor?->description,
        ];
    }

    public function save(ProfileVendorAction $action, ImageService $imageService)
    {
        $this->authorize('update', Vendor::class);

        $this->validate();

        $data = $this->formData;

        if ($this->logo) {
            if ($this->vendor->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->vendor->logo);
            }

            $data['logo'] = $imageService->uploadAndConvertToWebp($this->logo, 'vendors');
        }

        $action->execute($this->vendor->id, $data);

        $this->vendor->refresh();
        $this->logo = null;

        // Fire a native browser event so Alpine.js in the sidebar can update the logo reactively
        $newLogoUrl = $this->vendor->logo
            ? \Illuminate\Support\Facades\Storage::url($this->vendor->logo)
            : '';
        $this->js("window.dispatchEvent(new CustomEvent('vendor-logo-updated', { detail: { url: " . json_encode($newLogoUrl) . " } }))");

        Flux::toast(SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
    }

    private function getInitials(): string
    {
        $names = explode(' ', $this->vendor?->name ?? '');
        $initials = '';
        foreach (array_slice($names, 0, 2) as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        return $initials ?: 'V';
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    Profil Vendor
                </h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Kelola informasi dasar {{ $vendor?->name }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:badge
                    :color="$vendor?->status_verification?->value === 'verified' ? 'green' : ($vendor?->status_verification?->value === 'rejected' ? 'red' : 'yellow')"
                    size="lg">
                    {{ $vendor?->status_verification?->name ?? 'Pending' }}
                </flux:badge>

                <flux:badge :color="$vendor?->status_subscription?->value === 'active' ? 'green' : 'red'"
                    size="lg">
                    {{ $vendor?->status_subscription?->name ?? 'Inactive' }}
                </flux:badge>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <flux:card class="space-y-6">
                <fieldset @cannot('update', App\Models\Vendor::class) disabled @endcannot class="space-y-4">
                    <div>
                        <flux:label class="block mb-4">Logo Vendor</flux:label>

                        <div class="flex items-center gap-6">
                            <div class="relative">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview"
                                        class="w-24 h-24 rounded-lg object-cover border-2 border-zinc-200 dark:border-zinc-700" />
                                @elseif ($vendor?->logo)
                                    <img src="{{ asset('storage/' . $vendor->logo) }}" alt="{{ $vendor->name }}"
                                        class="w-24 h-24 rounded-lg object-cover border-2 border-zinc-200 dark:border-zinc-700" />
                                @else
                                    <div
                                        class="w-24 h-24 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center border-2 border-zinc-200 dark:border-zinc-700">
                                        <span class="text-white font-bold text-2xl">
                                            {{ $this->getInitials() }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1">
                                <flux:field>
                                    <flux:label size="sm" class="mb-2">Ubah Logo</flux:label>
                                    <flux:input wire:model="logo" type="file" accept="image/*" />
                                    @if ($logo)
                                        <p class="mt-2 text-xs text-green-600 dark:text-green-400">
                                            ✓ Gambar siap diupload
                                        </p>
                                    @endif
                                    <flux:error name="logo" />
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        Format: JPEG, PNG, WebP (Max 2 MB)
                                    </p>
                                </flux:field>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-zinc-200 dark:border-zinc-700" />

                    <form wire:submit="save" class="space-y-4">
                        <flux:field>
                            <flux:label>Nama Vendor <span class="text-red-600">*</span></flux:label>
                            <flux:input wire:model.blur="formData.name" placeholder="Contoh: PawPaw Grooming Studio"
                                required />
                            <flux:error name="formData.name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Email <span class="text-red-600">*</span></flux:label>
                            <flux:input wire:model.blur="formData.email" type="email" placeholder="vendor@example.com"
                                required />
                            <flux:error name="formData.email" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Alamat <span class="text-red-600">*</span></flux:label>
                            <flux:textarea wire:model.blur="formData.address"
                                placeholder="Jln. Sudirman No. 123, Jakarta Selatan" rows="3" required />
                            <flux:error name="formData.address" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Nomor Kontak <span class="text-red-600">*</span></flux:label>
                            <flux:input wire:model.blur="formData.contact" placeholder="+62812345678" required />
                            <flux:error name="formData.contact" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Deskripsi (opsional)</flux:label>
                            <flux:textarea wire:model.blur="formData.description"
                                placeholder="Deskripsi singkat tentang vendor Anda" rows="4" />
                            <flux:error name="formData.description" />
                        </flux:field>

                        @can('update', App\Models\Vendor::class)
                            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <flux:button type="submit" variant="primary">
                                    <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                                    <span wire:loading wire:target="save">Menyimpan...</span>
                                </flux:button>
                            </div>
                        @endcan
                    </form>
                </fieldset>
            </flux:card>
        </div>

        <div class="md:col-span-1">
            <flux:card class="bg-zinc-50 dark:bg-zinc-800/50 sticky top-6">
                <flux:heading size="sm" class="mb-4">Informasi Tambahan</flux:heading>

                <div class="space-y-4">
                    <div>
                        <flux:text class="text-xs text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                            Tanggal Bergabung
                        </flux:text>
                        <flux:heading size="xs" class="mt-2 font-mono">
                            {{ $vendor?->join_at?->format('d M Y') ?? '-' }}
                        </flux:heading>
                    </div>

                    <div class="border-t border-zinc-200 dark:border-zinc-700" />

                    <div>
                        <flux:text class="text-xs text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                            Slug URL
                        </flux:text>
                        <flux:heading size="xs" class="mt-2 font-mono text-xs break-all">
                            {{ $vendor?->slug ?? '-' }}
                        </flux:heading>
                    </div>

                    <div class="border-t border-zinc-200 dark:border-zinc-700" />

                    <div>
                        <flux:text class="text-xs text-zinc-600 dark:text-zinc-400 uppercase tracking-wide">
                            Vendor ID
                        </flux:text>
                        <flux:heading size="xs" class="mt-2 font-mono">
                            #{{ $vendor?->id ?? '-' }}
                        </flux:heading>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
