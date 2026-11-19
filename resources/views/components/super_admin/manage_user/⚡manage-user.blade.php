<?php

use App\Actions\SuperAdmin\Users\SaveManagedUserAction;
use App\Enums\StaffPosition;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.super-admin')] class extends Component {
    public bool $showEditor = false;
    public bool $showDetail = false;
    public bool $showDeleteConfirmation = false;
    public string $mode = 'create';
    public ?int $selectedUserId = null;
    public ?User $selectedUser = null;
    public array $form = [];

    #[On('viewUser')]
    public function viewUser(int $rowId): void
    {
        $this->selectedUser = $this->userQuery($rowId)->firstOrFail();
        $this->showDetail = true;
    }

    #[On('editUser')]
    public function editUser(int $rowId): void
    {
        $user = $this->userQuery($rowId)->firstOrFail();
        $this->selectedUserId = $user->id;
        $this->selectedUser = $user;
        $this->mode = 'edit';
        $this->form = $this->mapUser($user);
        $this->showDetail = false;
        $this->showEditor = true;
    }

    public function createUser(): void
    {
        $this->resetValidation();
        $this->selectedUserId = null;
        $this->selectedUser = null;
        $this->mode = 'create';
        $this->form = [
            'name' => '', 'username' => '', 'email' => '', 'phone_number' => '',
            'password' => '', 'account_type' => 'client', 'role_id' => app(SaveManagedUserAction::class)->clientRoleId(),
            'vendor_id' => '', 'position' => StaffPosition::FrontOffice->value,
        ];
        $this->showEditor = true;
    }

    public function saveUser(SaveManagedUserAction $action): void
    {
        $this->validate($this->rules());
        $this->selectedUser = $action->execute(
            $this->selectedUserId ? User::query()->findOrFail($this->selectedUserId) : null,
            $this->form,
        );
        $this->showEditor = false;
        $this->selectedUserId = $this->selectedUser->id;
        Flux::toast($this->mode === 'create' ? 'Akun berhasil dibuat.' : 'Akun berhasil diperbarui.', variant: 'success');
        $this->dispatch('pg:eventRefresh-UsersTable');
        $this->resetEditor();
    }

    #[On('confirmDeleteUser')]
    public function confirmDeleteUser(int $rowId): void
    {
        abort_if($rowId === auth('web')->id(), 403, 'Akun sendiri tidak dapat dihapus.');
        $this->selectedUserId = $rowId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteUser(): void
    {
        abort_if($this->selectedUserId === auth('web')->id(), 403, 'Akun sendiri tidak dapat dihapus.');
        User::query()->findOrFail($this->selectedUserId)->delete();
        $this->showDeleteConfirmation = false;
        Flux::toast('Akun berhasil dihapus.', variant: 'success');
        $this->dispatch('pg:eventRefresh-UsersTable');
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
        $this->resetEditor();
    }

    protected function userQuery(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()->with(['role', 'userClient', 'vendorStaff.vendor'])->whereKey($userId);
    }

    protected function mapUser(User $user): array
    {
        $staff = $user->vendorStaff->first();
        return [
            'name' => $user->name, 'username' => $user->username ?? '', 'email' => $user->email,
            'phone_number' => $user->phone_number, 'password' => '',
            'account_type' => $user->userClient ? 'client' : 'staff', 'role_id' => $user->role_id,
            'vendor_id' => $staff?->vendor_id ?? '', 'position' => $staff?->position?->value ?? StaffPosition::FrontOffice->value,
        ];
    }

    protected function rules(): array
    {
        $userId = $this->selectedUserId;
        $rules = [
            'form.name' => ['required', 'string', 'max:255'],
            'form.username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'form.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'form.phone_number' => ['required', 'string', Rule::unique('users', 'phone_number')->ignore($userId)],
            'form.password' => [$this->mode === 'create' ? 'required' : 'nullable', 'string', 'min:8'],
            'form.account_type' => ['required', Rule::in(['client', 'staff'])],
            'form.role_id' => ['required', 'exists:roles,id'],
        ];
        if (($this->form['account_type'] ?? 'client') === 'staff') {
            $rules['form.vendor_id'] = ['required', 'exists:vendors,id'];
            $rules['form.position'] = ['required', Rule::enum(StaffPosition::class)];
        }
        return $rules;
    }

    protected function resetEditor(): void
    {
        $this->resetValidation();
        $this->form = [];
        $this->selectedUserId = null;
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><flux:heading size="xl">Manage User</flux:heading><flux:subheading>Kelola akun client dan staff vendor dari satu tempat.</flux:subheading></div>
        <flux:button wire:click="createUser" variant="primary" icon="plus">Tambah akun</flux:button>
    </div>

    <flux:card><livewire:super-admin.datatables.users-table /></flux:card>

    <flux:modal wire:model="showDetail" class="w-full max-w-xl">
        @if ($selectedUser)
            <div class="space-y-5">
                <div><flux:heading size="lg">{{ $selectedUser->name }}</flux:heading><flux:subheading>{{ $selectedUser->email }}</flux:subheading></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><p class="text-xs text-zinc-500">Username</p><p>{{ $selectedUser->username ?: '-' }}</p></div><div><p class="text-xs text-zinc-500">Telepon</p><p>{{ $selectedUser->phone_number }}</p></div><div><p class="text-xs text-zinc-500">Tipe</p><p>{{ $selectedUser->userClient ? 'Client' : 'Staff vendor' }}</p></div><div><p class="text-xs text-zinc-500">Role</p><p>{{ $selectedUser->role?->name ?? '-' }}</p></div></div>
                @if ($selectedUser->vendorStaff->isNotEmpty())<div><p class="text-xs text-zinc-500">Vendor</p><p>{{ $selectedUser->vendorStaff->pluck('vendor.name')->filter()->join(', ') }}</p></div>@endif
                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700"><flux:button wire:click="editUser({{ $selectedUser->id }})" variant="primary">Edit</flux:button><flux:button wire:click="$set('showDetail', false)" variant="ghost">Tutup</flux:button></div>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model="showEditor" class="w-full max-w-2xl">
        <form wire:submit="saveUser" class="space-y-5"><div><flux:heading size="lg">{{ $mode === 'create' ? 'Tambah akun' : 'Edit akun' }}</flux:heading><flux:subheading>Field bertanda wajib diisi. Password kosong berarti tidak diubah.</flux:subheading></div>
            <div class="grid gap-4 sm:grid-cols-2"><flux:field><flux:label>Nama</flux:label><flux:input wire:model.blur="form.name" /><flux:error name="form.name" /></flux:field><flux:field><flux:label>Username</flux:label><flux:input wire:model.blur="form.username" /><flux:error name="form.username" /></flux:field><flux:field><flux:label>Email</flux:label><flux:input wire:model.blur="form.email" type="email" /><flux:error name="form.email" /></flux:field><flux:field><flux:label>Telepon</flux:label><flux:input wire:model.blur="form.phone_number" /><flux:error name="form.phone_number" /></flux:field><flux:field><flux:label>Role</flux:label><flux:select wire:model="form.role_id">@foreach (Role::query()->orderBy('name')->get() as $role)<flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>@endforeach</flux:select><flux:error name="form.role_id" /></flux:field><flux:field><flux:label>Tipe akun</flux:label><flux:select wire:model.live="form.account_type"><flux:select.option value="client">Client</flux:select.option><flux:select.option value="staff">Staff vendor</flux:select.option></flux:select></flux:field></div>
            @if (($form['account_type'] ?? 'client') === 'staff')<div class="grid gap-4 sm:grid-cols-2"><flux:field><flux:label>Vendor</flux:label><flux:select wire:model="form.vendor_id"><flux:select.option value="">Pilih vendor</flux:select.option>@foreach (Vendor::query()->orderBy('name')->get() as $vendor)<flux:select.option value="{{ $vendor->id }}">{{ $vendor->name }}</flux:select.option>@endforeach</flux:select><flux:error name="form.vendor_id" /></flux:field><flux:field><flux:label>Posisi</flux:label><flux:select wire:model="form.position">@foreach (StaffPosition::cases() as $position)<flux:select.option value="{{ $position->value }}">{{ str_replace('_', ' ', ucfirst($position->value)) }}</flux:select.option>@endforeach</flux:select><flux:error name="form.position" /></flux:field></div>@endif
            <flux:field><flux:label>Password</flux:label><flux:input wire:model="form.password" type="password" autocomplete="new-password" /><flux:error name="form.password" /></flux:field>
            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700"><flux:button type="button" wire:click="closeEditor" variant="ghost">Batal</flux:button><flux:button type="submit" variant="primary">Simpan</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteConfirmation" class="w-full max-w-md"><div class="space-y-4"><flux:heading size="lg">Hapus akun?</flux:heading><flux:subheading>Data akun akan dihapus. Pastikan akun yang dipilih benar.</flux:subheading><div class="flex justify-end gap-2"><flux:button wire:click="$set('showDeleteConfirmation', false)" variant="ghost">Batal</flux:button><flux:button wire:click="deleteUser" variant="danger">Hapus</flux:button></div></div></flux:modal>
</div>