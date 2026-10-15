<?php

use App\Models\CategoryService;
use App\Models\VendorService;
use App\Models\VendorServiceItem;
use App\Actions\Admin\VendorServiceItem\CreateVendorServiceItem;
use App\Actions\Admin\VendorServiceItem\UpdateVendorServiceItem;
use App\Actions\Admin\VendorServiceItem\DeleteVendorServiceItem;
use App\Enums\SystemMessage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Flux\Flux;

new #[Layout('layouts::admin')] class extends Component
{
    #[Url]
    public $categoryServiceId;

    public $items = [];

    public $category = null;

    public $showForm = false;

    public $confirmDeleteId = null;

    public $editingId = null;

    public $formData = [
        'name' => '',
        'price' => '',
        'duration' => '',
        'capacity' => '',
    ];

    public function rules(): array
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.price' => 'required|numeric|min:1',
            'formData.duration' => 'required|numeric|min:1',
            'formData.capacity' => 'nullable|numeric|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'formData.name.required' => 'Nama item harus diisi',
            'formData.name.string' => 'Nama item harus berupa teks',
            'formData.name.max' => 'Nama item maksimal 255 karakter',
            'formData.price.required' => 'Harga harus diisi',
            'formData.price.numeric' => 'Harga harus berupa angka',
            'formData.price.min' => 'Harga minimal 1',
            'formData.duration.required' => 'Durasi harus diisi',
            'formData.duration.numeric' => 'Durasi harus berupa angka',
            'formData.duration.min' => 'Durasi minimal 1 menit',
            'formData.capacity.numeric' => 'Kapasitas harus berupa angka',
            'formData.capacity.min' => 'Kapasitas minimal 1',
        ];
    }

    public function mount()
    {
        $this->category = CategoryService::find($this->categoryServiceId);
        abort_unless($this->category, 404);

        $this->loadItems();
    }

    public function loadItems()
    {
        $vendorService = VendorService::where('category_service_id', $this->categoryServiceId)->first();

        if ($vendorService) {
            $this->items = $vendorService
                ->vendorServiceItems()
                ->get()
                ->map(
                    fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'duration' => $item->duration,
                        'capacity' => $item->capacity,
                    ],
                )
                ->toArray();
        }
    }

    #[Livewire\Attributes\On('openEditForm')]
    public function openForm($id = null)
    {
        if ($id) {
            $item = VendorServiceItem::find($id);
            $this->editingId = $id;
            $this->formData = [
                'name' => $item->name,
                'price' => $item->price,
                'duration' => $item->duration,
                'capacity' => $item->capacity,
            ];
        } else {
            $this->editingId = null;
            $this->formData = ['name' => '', 'price' => '', 'duration' => '', 'capacity' => ''];
        }

        $this->showForm = true;
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->formData = ['name' => '', 'price' => '', 'duration' => '', 'capacity' => ''];
        $this->resetValidation();
    }

    #[Livewire\Attributes\On('openDeleteConfirm')]
    public function openDeleteConfirm($id): void
    {
        $this->confirmDeleteId = $id;
        $this->modal('confirm-delete')->show();
    }

    public function confirmDelete(DeleteVendorServiceItem $deleteVendorServiceItem): void
    {
        if ($this->confirmDeleteId) {
            $deleteVendorServiceItem->execute($this->confirmDeleteId);
            Flux::toast(SystemMessage::DATA_DELETED_SUCCESS->value, variant: 'success');
            $this->dispatch('pg:eventRefresh-VendorServiceItemsTable');
            $this->confirmDeleteId = null;
            $this->modal('confirm-delete')->close();
        }
    }

    public function save(CreateVendorServiceItem $createVendorServiceItem, UpdateVendorServiceItem $updateVendorServiceItem)
    {
        $this->validate();

        $dataToSave = $this->formData;
        if ($dataToSave['capacity'] === '') {
            $dataToSave['capacity'] = null;
        }

        if ($this->editingId) {
            $updateVendorServiceItem->execute($this->editingId, $dataToSave);
            Flux::toast(SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
        } else {
            $createVendorServiceItem->execute($this->categoryServiceId, $dataToSave);
            Flux::toast(SystemMessage::DATA_CREATED_SUCCESS->value, variant: 'success');
        }

        $this->closeForm();
        $this->loadItems();
        $this->dispatch('pg:eventRefresh-VendorServiceItemsTable');
    }

    public function delete($id)
    {
        VendorServiceItem::find($id)->delete();
        $this->loadItems();
    }
};
?>

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:button href="{{ route('vendor.admin.services') }}" variant="ghost" class="mb-3">
                ← Kembali ke Services
            </flux:button>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ $category?->name }} - Item Service
            </h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Kelola item untuk layanan {{ $category?->name }}</p>
        </div>

        <flux:button wire:click="openForm" variant="primary" icon="plus">
            Tambah Item
        </flux:button>
    </div>

    <flux:modal wire:model="showForm" class="min-w-md">
        <flux:modal.trigger>
            {{-- Triggered programmatically via wire:model --}}
        </flux:modal.trigger>

        <form wire:submit="save">
            <flux:heading size="lg">
                {{ $editingId ? 'Edit Item Service' : 'Tambah Item Service Baru' }}
            </flux:heading>

            <flux:subheading class="mb-6">
                {{ $editingId ? 'Perbarui informasi item service' : 'Isi form untuk menambah item service baru' }}
            </flux:subheading>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Nama Item <span class="text-red-600">*</span></flux:label>
                    <flux:input wire:model.blur="formData.name" placeholder="Contoh: Basic Grooming" required />
                    <flux:error name="formData.name" />
                </flux:field>

                <flux:field>
                    <flux:label>Harga (Rp) <span class="text-red-600">*</span></flux:label>
                    <flux:input wire:model.blur="formData.price" type="number" placeholder="100000" required
                        min="1" />
                    <flux:error name="formData.price" />
                </flux:field>

                <flux:field>
                    <flux:label>Durasi (menit) <span class="text-red-600">*</span></flux:label>
                    <flux:input wire:model.blur="formData.duration" type="number" placeholder="60" required
                        min="1" />
                    <flux:error name="formData.duration" />
                </flux:field>

                <flux:field>
                    <flux:label>Kapasitas (opsional)</flux:label>
                    <flux:input wire:model.blur="formData.capacity" type="number" placeholder="5" min="1" />
                    <flux:error name="formData.capacity" />
                </flux:field>
            </div>

            <div class="mt-6 flex gap-2">
                <flux:button type="submit" variant="primary">
                    Simpan
                </flux:button>
                <flux:button type="button" wire:click="closeForm" variant="ghost">
                    Batal
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <livewire:admin.datatables.vendor-service-items-table :categoryServiceId="$categoryServiceId" />

    <!-- Delete Confirmation Modal -->
    <flux:modal name="confirm-delete" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Item Service?</flux:heading>
                <flux:text class="mt-2">
                    Anda akan menghapus item service ini. <br>
                    Tindakan ini tidak dapat dibatalkan.
                </flux:text>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="confirmDelete" variant="danger">
                    Ya, Hapus
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
