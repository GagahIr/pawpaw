<?php

namespace App\Livewire\SuperAdmin\Datatables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class UsersTable extends PowerGridComponent
{
    public string $tableName = 'UsersTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return User::query()
            ->with(['role', 'userClient', 'vendorStaff.vendor'])
            ->whereHas('role', fn (Builder $query) => $query->where('name', '!=', 'super_admin'))
            ->where(fn (Builder $query) => $query
                ->whereHas('userClient')
                ->orWhereHas('vendorStaff'));
    }

    public function relationSearch(): array
    {
        return ['role' => ['name'], 'vendorStaff.vendor' => ['name']];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('name')
            ->add('username')
            ->add('email')
            ->add('phone_number')
            ->add('role_name', fn (User $row): string => $row->role?->name ?? '-')
            ->add('account_type', function (User $row): string {
                if ($row->userClient) {
                    return 'Client';
                }

                return $row->vendorStaff->isNotEmpty() ? 'Staff vendor' : 'Belum dipetakan';
            })
            ->add('vendor_name', fn (User $row): string => $row->vendorStaff->pluck('vendor.name')->filter()->unique()->join(', ') ?: '-')
            ->add('created_at_formatted', fn (User $row): string => $row->created_at?->format('d M Y') ?? '-');
    }

    public function columns(): array
    {
        return [
            Column::make('Nama', 'name')->searchable()->sortable(),
            Column::make('Kontak', 'email')->searchable(),
            Column::make('Tipe', 'account_type'),
            Column::make('Vendor', 'vendor_name'),
            Column::make('Role', 'role_name')->sortable(),
            Column::make('Terdaftar', 'created_at_formatted', 'created_at')->sortable(),
            Column::action('Aksi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('email'),
        ];
    }

    public function actions(User $row): array
    {
        return [
            Button::add('view')->slot('Detail')->class('text-blue-700')->dispatch('viewUser', ['rowId' => $row->id]),
            Button::add('edit')->slot('Edit')->class('text-emerald-700')->dispatch('editUser', ['rowId' => $row->id]),
            Button::add('delete')->slot('Hapus')->class('text-red-700')->dispatch('confirmDeleteUser', ['rowId' => $row->id]),
        ];
    }
}
