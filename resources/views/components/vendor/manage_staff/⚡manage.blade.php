<?php

use App\Actions\Admin\ManageStaff\CreateManageStaffAction;
use App\Actions\Admin\ManageStaff\UpdateManageStaffAction;
use App\Actions\Admin\ManageStaff\DeleteManageStaffAction;
use App\DTOs\ManageStaffData;
use App\Enums\StaffPosition;
use App\Enums\SystemMessage;
use App\Models\VendorStaff;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Validation\Rule;

new #[Layout('layouts::admin')] class extends Component {
    public array $formData = [
        'name' => '',
        'email' => '',
        'phone_number' => '',
        'password' => '',
        'position' => 'front_office',
    ];

    public ?int $editingStaffId = null;

    public ?int $deletingStaffId = null;

    public bool $showForm = false;

    public function rules(): array
    {
        $userId = null;
        if ($this->editingStaffId) {
            $staff = VendorStaff::with('user')->find($this->editingStaffId);
            $userId = $staff?->user->id;
        }

        return [
            'formData.name' => 'required|string|max:255',
            'formData.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId), 'unique:vendors,email'],
            'formData.phone_number' => ['required', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($userId), 'unique:vendors,contact'],
            'formData.password' => $this->editingStaffId ? 'nullable|string|min:8' : 'required|string|min:8',
            'formData.position' => 'required|in:front_office',
        ];
    }

    public function messages(): array
    {
        return [
            'formData.name.required' => 'Nama staff harus diisi',
            'formData.email.required' => 'Email harus diisi',
            'formData.email.email' => 'Format email tidak valid',
            'formData.email.unique' => 'Email sudah terdaftar',
            'formData.phone_number.required' => 'Nomor telepon harus diisi',
            'formData.phone_number.unique' => 'Nomor telepon sudah terdaftar',
            'formData.password.required' => 'Password harus diisi',
            'formData.password.min' => 'Password minimal 8 karakter',
            'formData.password.nullable' => 'Password bersifat opsional saat edit',
            'formData.position.required' => 'Posisi harus dipilih',
            'formData.position.in' => 'Posisi tidak valid',
        ];
    }

    public function toggleForm(): void
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->reset('formData', 'editingStaffId');
            $this->formData['position'] = 'front_office';
        }
    }

    public function edit(int $staffId): void
    {
        $staff = VendorStaff::with('user')->findOrFail($staffId);
        $this->editingStaffId = $staffId;
        $this->formData = [
            'name' => $staff->user->name,
            'email' => $staff->user->email,
            'phone_number' => $staff->user->phone_number,
            'password' => '',
            'position' => 'front_office',
        ];
        $this->showForm = true;
    }

    public function confirmDelete(int $staffId): void
    {
        $this->deletingStaffId = $staffId;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(DeleteManageStaffAction $deleteAction): void
    {
        if (!$this->deletingStaffId) {
            return;
        }

        try {
            $deleteAction->delete($this->deletingStaffId);

            Flux::modal('confirm-delete')->close();
            $this->reset('deletingStaffId');

            Flux::toast(SystemMessage::DATA_DELETED_SUCCESS->value, variant: 'success');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function save(CreateManageStaffAction $createAction, UpdateManageStaffAction $updateAction): void
    {
        $this->validate();

        try {
            $vendorId = auth()->user()->vendor->id;

            if ($this->editingStaffId) {
                $data = $this->formData;
                if (empty($data['password'])) {
                    unset($data['password']);
                }
                $updateAction->update($this->editingStaffId, $data);
                $message = SystemMessage::DATA_UPDATED_SUCCESS->value;
            } else {
                $dto = ManageStaffData::fromArray($this->formData);
                $createAction->create($vendorId, $dto);
                $message = SystemMessage::DATA_CREATED_SUCCESS->value;
            }

            $this->reset('formData', 'showForm', 'editingStaffId');
            $this->formData['position'] = 'front_office';
            Flux::toast($message, variant: 'success');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function getStaffListProperty()
    {
        return VendorStaff::with('user')
            ->where('vendor_id', auth()->user()->vendor->id)
            ->where('position', StaffPosition::FrontOffice)
            ->latest()
            ->get();
    }

    public function getHasFrontOfficeStaffProperty(): bool
    {
        return VendorStaff::where('vendor_id', auth()->user()->vendor->id)
            ->where('position', StaffPosition::FrontOffice)
            ->exists();
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    Kelola Staff
                </h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Kelola staff mitra Anda
                </p>
            </div>

            @if (!$this->hasFrontOfficeStaff || $showForm)
                <div>
                    <flux:button wire:click="toggleForm" variant="primary">
                        <span wire:loading.remove wire:target="toggleForm">
                            {{ $showForm ? 'Tutup Form' : '+ Tambah Staff Front Office' }}
                        </span>
                        <span wire:loading wire:target="toggleForm">...</span>
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    @if ($showForm)
        <flux:card class="mb-6">
            <form wire:submit="save" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Nama Lengkap <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model.blur="formData.name" placeholder="Masukkan nama lengkap" required />
                        <flux:error name="formData.name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model.blur="formData.email" type="email" placeholder="staff@example.com"
                            required />
                        <flux:error name="formData.email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nomor Telepon <span class="text-red-600">*</span></flux:label>
                        <flux:input wire:model.blur="formData.phone_number" placeholder="+628****5678" required />
                        <flux:error name="formData.phone_number" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Password
                            @if (!$editingStaffId)
                                <span class="text-red-600">*</span>
                            @endif
                        </flux:label>

                        <flux:input wire:model.blur="formData.password" type="password"
                            :placeholder="$editingStaffId
                                ?
                                'Kosongkan jika tidak ingin mengubah' :
                                'Minimal 8 karakter'"
                            :required="!$editingStaffId" viewable autocomplete="new-password" />

                        <flux:error name="formData.password" />
                    </flux:field>
                </div>

                <input type="hidden" wire:model="formData.position" value="front_office" />

                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-2">
                    <flux:button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="save">
                            {{ $editingStaffId ? 'Perbarui' : 'Simpan' }}
                        </span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </flux:button>

                    <flux:button type="button" wire:click="toggleForm" variant="ghost">
                        Batal
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nama</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Telepon</flux:table.column>
                <flux:table.column>Posisi</flux:table.column>
                <flux:table.column>Tanggal Dibuat</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->staffList as $staff)
                    <flux:table.row :key="$staff->id">
                        <flux:table.cell>{{ $staff->user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $staff->user->email }}</flux:table.cell>
                        <flux:table.cell>{{ $staff->user->phone_number }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc">
                                {{ $staff->position->name }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $staff->created_at->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button wire:click="edit({{ $staff->id }})" size="sm" variant="ghost">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </flux:button>
                                <flux:button wire:click="confirmDelete({{ $staff->id }})" size="sm" variant="danger">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500 dark:text-zinc-400">
                            Belum ada staff Front Office
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
    <flux:modal name="confirm-delete" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Data?</flux:heading>
                <flux:text class="mt-2">
                    Anda akan menghapus data ini. <br>
                    Tindakan ini tidak dapat dibatalkan.
                </flux:text>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">
                    <span wire:loading.remove wire:target="delete">Ya, Hapus</span>
                    <span wire:loading wire:target="delete">Menghapus...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
