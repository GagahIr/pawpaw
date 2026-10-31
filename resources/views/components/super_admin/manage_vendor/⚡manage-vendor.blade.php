<?php

use App\Actions\Admin\Vendor\UpdateVendorManagementAction;
use App\Enums\DayOfWeek;
use App\Enums\SystemMessage;
use App\Models\Vendor;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.super-admin')] class extends Component {
    public bool $showDetailModal = false;
    public bool $showEditModal = false;
    public ?int $selectedVendorId = null;
    public ?Vendor $selectedVendor = null;
    public string $editTab = 'profile';
    public array $editData = ['vendor' => [], 'staff' => [], 'services' => [], 'hours' => []];

    #[On('viewVendor')]
    public function viewVendor(int $rowId): void
    {
        $this->selectedVendorId = $rowId;
        $this->selectedVendor = $this->vendorQuery($rowId)->firstOrFail();
        $this->showDetailModal = true;
    }

    #[On('editVendor')]
    public function editVendor(int $rowId): void
    {
        $vendor = $this->vendorQuery($rowId)->firstOrFail();
        $this->selectedVendorId = $vendor->id;
        $this->selectedVendor = $vendor;
        $this->editData = $this->mapVendorForEdit($vendor);
        $this->editTab = 'profile';
        $this->showEditModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
    }

    public function closeEditor(): void
    {
        $this->showEditModal = false;
        $this->resetValidation();
    }

    public function saveVendor(UpdateVendorManagementAction $action): void
    {
        $this->validate();
        abort_unless($this->selectedVendorId, 404);

        $this->selectedVendor = $action->execute(
            $this->selectedVendorId,
            $this->editData['vendor'],
            $this->editData['staff'],
            $this->editData['services'],
            $this->editData['hours'],
        );

        $this->showEditModal = false;
        Flux::toast(SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
        $this->dispatch('pg:eventRefresh-VendorsTable');
    }

    protected function rules(): array
    {
        $vendorId = $this->selectedVendorId;
        $rules = [
            'editData.vendor.name' => ['required', 'string', 'max:255', Rule::unique('vendors', 'name')->ignore($vendorId)],
            'editData.vendor.email' => ['required', 'email', 'max:255', Rule::unique('vendors', 'email')->ignore($vendorId)],
            'editData.vendor.contact' => ['required', 'string', 'max:30', Rule::unique('vendors', 'contact')->ignore($vendorId)],
            'editData.vendor.address' => ['required', 'string', 'max:1000'],
            'editData.vendor.description' => ['nullable', 'string', 'max:2000'],
            'editData.vendor.latitude' => ['nullable', 'string', 'max:50'],
            'editData.vendor.longitude' => ['nullable', 'string', 'max:50'],
            'editData.vendor.status_verification' => ['required', 'in:pending,approved,rejected'],
            'editData.vendor.status_subscription' => ['required', 'in:inactive,active,suspended'],
            'editData.services.*.status_service' => ['boolean'],
            'editData.services.*.items.*.name' => ['required', 'string', 'max:255'],
            'editData.services.*.items.*.price' => ['required', 'integer', 'min:0'],
            'editData.services.*.items.*.duration' => ['required', 'integer', 'min:1'],
            'editData.services.*.items.*.capacity' => ['nullable', 'integer', 'min:1'],
            'editData.hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'editData.hours.*.close_time' => ['nullable', 'date_format:H:i'],
            'editData.hours.*.is_closed' => ['boolean'],
        ];

        foreach ($this->editData['staff'] as $index => $staff) {
            $rules["editData.staff.$index.name"] = ['required', 'string', 'max:255'];
            $rules["editData.staff.$index.email"] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff['user_id'] ?? null), Rule::unique('vendors', 'email')->ignore($vendorId)];
            $rules["editData.staff.$index.phone_number"] = ['required', 'string', 'max:30', Rule::unique('users', 'phone_number')->ignore($staff['user_id'] ?? null), Rule::unique('vendors', 'contact')->ignore($vendorId)];
            $rules["editData.staff.$index.password"] = ['nullable', 'string', 'min:8'];
        }

        return $rules;
    }

    protected function vendorQuery(int $vendorId): \Illuminate\Database\Eloquent\Builder
    {
        return Vendor::query()->with([
            'vendorStaff.user.role',
            'vendorServices.categoryService',
            'vendorServices.vendorServiceItems',
            'vendorOperatingHours',
        ])->whereKey($vendorId);
    }

    protected function mapVendorForEdit(Vendor $vendor): array
    {
        return [
            'vendor' => [
                'name' => $vendor->name, 'email' => $vendor->email, 'contact' => $vendor->contact,
                'address' => $vendor->address, 'description' => $vendor->description,
                'latitude' => $vendor->latitude, 'longitude' => $vendor->longitude,
                'status_verification' => $vendor->status_verification?->value,
                'status_subscription' => $vendor->status_subscription?->value,
            ],
            'staff' => $vendor->vendorStaff->map(fn ($staff): array => [
                'id' => $staff->id, 'user_id' => $staff->user_id,
                'position' => $staff->position?->value ?? (string) $staff->position,
                'name' => $staff->user?->name ?? '', 'email' => $staff->user?->email ?? '',
                'phone_number' => $staff->user?->phone_number ?? '', 'password' => '',
            ])->values()->all(),
            'services' => $vendor->vendorServices->map(fn ($service): array => [
                'id' => $service->id, 'category_name' => $service->categoryService?->name ?? 'Tanpa kategori',
                'status_service' => (bool) $service->status_service,
                'items' => $service->vendorServiceItems->map(fn ($item): array => [
                    'id' => $item->id, 'name' => $item->name, 'price' => $item->price,
                    'duration' => $item->duration, 'capacity' => $item->capacity,
                ])->values()->all(),
            ])->values()->all(),
            'hours' => collect(DayOfWeek::cases())->map(function (DayOfWeek $day) use ($vendor): array {
                $hour = $vendor->vendorOperatingHours->first(fn ($operatingHour): bool => $operatingHour->day_of_week?->value === $day->value);
                return [
                    'day_of_week' => $day->value, 'day_name' => $day->label(),
                    'open_time' => $hour?->open_time ? substr((string) $hour->open_time, 0, 5) : '09:00',
                    'close_time' => $hour?->close_time ? substr((string) $hour->close_time, 0, 5) : '17:00',
                    'is_closed' => (bool) ($hour?->is_closed ?? false),
                ];
            })->values()->all(),
        ];
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Manage Mitra</flux:heading>
        <flux:subheading>Kelola profil, staff, layanan, dan jam operasional mitra.</flux:subheading>
    </div>

    <flux:card><livewire:super-admin.datatables.vendors-table /></flux:card>

    <flux:modal wire:model="showDetailModal" class="w-full max-w-5xl">
        @if ($selectedVendor)
            <div class="space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><flux:heading size="lg">{{ $selectedVendor->name }}</flux:heading><flux:subheading>{{ $selectedVendor->slug }} · Bergabung {{ $selectedVendor->join_at?->format('d M Y') }}</flux:subheading></div>
                    <div class="flex gap-2"><flux:badge color="{{ $selectedVendor->status_verification?->value === 'approved' ? 'green' : ($selectedVendor->status_verification?->value === 'rejected' ? 'red' : 'yellow') }}">{{ $selectedVendor->status_verification?->label() }}</flux:badge><flux:badge color="{{ $selectedVendor->status_subscription?->value === 'active' ? 'green' : 'zinc' }}">{{ ucfirst($selectedVendor->status_subscription?->value ?? '-') }}</flux:badge></div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:card class="space-y-2"><flux:heading size="sm">Informasi vendor</flux:heading><p class="text-sm"><strong>Email:</strong> {{ $selectedVendor->email }}</p><p class="text-sm"><strong>Kontak:</strong> {{ $selectedVendor->contact }}</p><p class="text-sm"><strong>Alamat:</strong> {{ $selectedVendor->address }}</p><p class="text-sm"><strong>Koordinat:</strong> {{ $selectedVendor->latitude ?? '-' }}, {{ $selectedVendor->longitude ?? '-' }}</p><p class="text-sm"><strong>Deskripsi:</strong> {{ $selectedVendor->description ?: '-' }}</p></flux:card>
                    <flux:card><flux:heading size="sm">Staff dan akun pengguna</flux:heading><div class="mt-3 divide-y divide-zinc-200 dark:divide-zinc-700">@forelse ($selectedVendor->vendorStaff as $staff)<div class="py-3 first:pt-0 last:pb-0"><p class="font-medium">{{ $staff->user?->name ?? '-' }} <span class="text-xs text-zinc-500">({{ $staff->position?->value ?? '-' }})</span></p><p class="text-sm text-zinc-500">{{ $staff->user?->email }} · {{ $staff->user?->phone_number }}</p><p class="text-xs text-zinc-500">Role: {{ $staff->user?->role?->name ?? '-' }}</p></div>@empty <p class="py-3 text-sm text-zinc-500">Belum ada staff.</p>@endforelse</div></flux:card>
                </div>
                <flux:card><flux:heading size="sm">Layanan dan item layanan</flux:heading><div class="mt-3 space-y-4">@forelse ($selectedVendor->vendorServices as $service)<div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"><div class="flex items-center justify-between gap-3"><p class="font-medium">{{ $service->categoryService?->name ?? '-' }}</p><flux:badge color="{{ $service->status_service ? 'green' : 'zinc' }}">{{ $service->status_service ? 'Aktif' : 'Nonaktif' }}</flux:badge></div><div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">@forelse ($service->vendorServiceItems as $item)<div class="rounded-md bg-zinc-50 p-3 text-sm dark:bg-zinc-800"><p class="font-medium">{{ $item->name }}</p><p>Rp {{ number_format($item->price, 0, ',', '.') }}</p><p class="text-zinc-500">{{ $item->duration }} menit · Kapasitas {{ $item->capacity ?? '-' }}</p></div>@empty <p class="text-sm text-zinc-500">Belum ada item layanan.</p>@endforelse</div></div>@empty <p class="text-sm text-zinc-500">Belum ada layanan.</p>@endforelse</div></flux:card>
                <flux:card><flux:heading size="sm">Jam operasional</flux:heading><div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">@foreach ($selectedVendor->vendorOperatingHours->sortBy('day_of_week') as $hour)<div class="rounded-md bg-zinc-50 p-3 text-sm dark:bg-zinc-800"><p class="font-medium">{{ $hour->day_of_week?->label() ?? '-' }}</p><p>{{ $hour->is_closed ? 'Tutup' : substr((string) $hour->open_time, 0, 5).' - '.substr((string) $hour->close_time, 0, 5) }}</p></div>@endforeach</div></flux:card>
                <div class="flex justify-end gap-2"><flux:button type="button" wire:click="editVendor({{ $selectedVendor->id }})" variant="primary">Edit mitra</flux:button><flux:button type="button" wire:click="closeDetail" variant="ghost">Tutup</flux:button></div>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model="showEditModal" class="w-full max-w-6xl">
        <form wire:submit="saveVendor" class="space-y-6">
            <div><flux:heading size="lg">Edit mitra</flux:heading><flux:subheading>Gunakan tab agar perubahan tetap fokus dan mudah ditinjau.</flux:subheading></div>
            <div class="flex flex-wrap gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-700">@foreach (['profile' => 'Profil', 'staff' => 'Staff & akun', 'services' => 'Layanan', 'hours' => 'Jam operasional'] as $tab => $label)<flux:button type="button" size="sm" variant="{{ $editTab === $tab ? 'primary' : 'ghost' }}" wire:click="$set('editTab', '{{ $tab }}')">{{ $label }}</flux:button>@endforeach</div>

            @if ($editTab === 'profile')
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:field><flux:label>Nama mitra</flux:label><flux:input wire:model.blur="editData.vendor.name" /><flux:error name="editData.vendor.name" /></flux:field><flux:field><flux:label>Email</flux:label><flux:input wire:model.blur="editData.vendor.email" type="email" /><flux:error name="editData.vendor.email" /></flux:field><flux:field><flux:label>Kontak</flux:label><flux:input wire:model.blur="editData.vendor.contact" /><flux:error name="editData.vendor.contact" /></flux:field><flux:field><flux:label>Alamat</flux:label><flux:textarea wire:model.blur="editData.vendor.address" rows="2" /><flux:error name="editData.vendor.address" /></flux:field><flux:field><flux:label>Latitude</flux:label><flux:input wire:model.blur="editData.vendor.latitude" /></flux:field><flux:field><flux:label>Longitude</flux:label><flux:input wire:model.blur="editData.vendor.longitude" /></flux:field><flux:field><flux:label>Status verifikasi</flux:label><flux:select wire:model="editData.vendor.status_verification"><flux:select.option value="pending">Menunggu</flux:select.option><flux:select.option value="approved">Disetujui</flux:select.option><flux:select.option value="rejected">Ditolak</flux:select.option></flux:select></flux:field><flux:field><flux:label>Status langganan</flux:label><flux:select wire:model="editData.vendor.status_subscription"><flux:select.option value="inactive">Inactive</flux:select.option><flux:select.option value="active">Active</flux:select.option><flux:select.option value="suspended">Suspended</flux:select.option></flux:select></flux:field><flux:field class="md:col-span-2"><flux:label>Deskripsi</flux:label><flux:textarea wire:model.blur="editData.vendor.description" rows="4" /><flux:error name="editData.vendor.description" /></flux:field>
                </div>
            @elseif ($editTab === 'staff')
                <div class="space-y-5">@forelse ($editData['staff'] as $index => $staff)<flux:card class="space-y-4"><div class="flex items-center justify-between"><flux:heading size="sm">{{ ucfirst(str_replace('_', ' ', $staff['position'])) }}</flux:heading><flux:badge color="zinc">Akun pengguna</flux:badge></div><div class="grid gap-4 md:grid-cols-2"><flux:field><flux:label>Nama</flux:label><flux:input wire:model.blur="editData.staff.{{ $index }}.name" /><flux:error name="editData.staff.{{ $index }}.name" /></flux:field><flux:field><flux:label>Email</flux:label><flux:input wire:model.blur="editData.staff.{{ $index }}.email" type="email" /><flux:error name="editData.staff.{{ $index }}.email" /></flux:field><flux:field><flux:label>Nomor telepon</flux:label><flux:input wire:model.blur="editData.staff.{{ $index }}.phone_number" /><flux:error name="editData.staff.{{ $index }}.phone_number" /></flux:field><flux:field><flux:label>Password baru</flux:label><flux:input wire:model.blur="editData.staff.{{ $index }}.password" type="password" placeholder="Kosongkan jika tidak berubah" viewable autocomplete="new-password" /><flux:error name="editData.staff.{{ $index }}.password" /></flux:field></div></flux:card>@empty <p class="text-sm text-zinc-500">Belum ada staff.</p>@endforelse</div>
            @elseif ($editTab === 'services')
                <div class="space-y-5">@forelse ($editData['services'] as $serviceIndex => $service)<flux:card class="space-y-4"><div class="flex items-center justify-between gap-3"><flux:heading size="sm">{{ $service['category_name'] }}</flux:heading><flux:switch wire:model="editData.services.{{ $serviceIndex }}.status_service" label="Aktif" /></div><div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">@foreach ($service['items'] as $itemIndex => $item)<div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700"><flux:field><flux:label>Nama item</flux:label><flux:input wire:model.blur="editData.services.{{ $serviceIndex }}.items.{{ $itemIndex }}.name" /><flux:error name="editData.services.{{ $serviceIndex }}.items.{{ $itemIndex }}.name" /></flux:field><div class="mt-3 grid grid-cols-2 gap-2"><flux:field><flux:label>Harga</flux:label><flux:input wire:model.blur="editData.services.{{ $serviceIndex }}.items.{{ $itemIndex }}.price" type="number" min="0" /></flux:field><flux:field><flux:label>Durasi</flux:label><flux:input wire:model.blur="editData.services.{{ $serviceIndex }}.items.{{ $itemIndex }}.duration" type="number" min="1" /></flux:field></div><flux:field class="mt-3"><flux:label>Kapasitas</flux:label><flux:input wire:model.blur="editData.services.{{ $serviceIndex }}.items.{{ $itemIndex }}.capacity" type="number" min="1" /></flux:field></div>@endforeach</div></flux:card>@empty <p class="text-sm text-zinc-500">Belum ada layanan.</p>@endforelse</div>
            @else
                <div class="space-y-3">@foreach ($editData['hours'] as $hourIndex => $hour)<div class="grid items-center gap-3 rounded-lg border border-zinc-200 p-3 md:grid-cols-[1fr_1fr_1fr_auto] dark:border-zinc-700"><div><p class="font-medium">{{ $hour['day_name'] }}</p><p class="text-xs text-zinc-500">Hari operasional</p></div><flux:input wire:model="editData.hours.{{ $hourIndex }}.open_time" type="time" :disabled="$hour['is_closed']" /><flux:input wire:model="editData.hours.{{ $hourIndex }}.close_time" type="time" :disabled="$hour['is_closed']" /><flux:switch wire:model="editData.hours.{{ $hourIndex }}.is_closed" label="Tutup" /></div>@endforeach</div>
            @endif
            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700"><flux:button type="button" wire:click="closeEditor" variant="ghost">Batal</flux:button><flux:button type="submit" variant="primary"><span wire:loading.remove wire:target="saveVendor">Simpan perubahan</span><span wire:loading wire:target="saveVendor">Menyimpan...</span></flux:button></div>
        </form>
    </flux:modal>
</div>