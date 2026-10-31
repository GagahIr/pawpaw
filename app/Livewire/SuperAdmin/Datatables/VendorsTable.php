<?php

namespace App\Livewire\SuperAdmin\Datatables;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class VendorsTable extends PowerGridComponent
{
    public string $tableName = 'VendorsTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Vendor::query()->withCount(['vendorStaff', 'vendorServices']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('email')
            ->add('contact')
            ->add('status_verification')
            ->add('status_verification_label', fn (Vendor $row) => $row->status_verification?->label() ?? '-')
            ->add('status_subscription')
            ->add('status_subscription_label', fn (Vendor $row) => ucfirst($row->status_subscription?->value ?? '-'))
            ->add('vendor_staff_count')
            ->add('vendor_services_count');
    }

    public function columns(): array
    {
        return [
            Column::make('Nama Mitra', 'name')->searchable()->sortable(),
            Column::make('Kontak', 'contact')->searchable(),
            Column::make('Verifikasi', 'status_verification_label', 'status_verification')->sortable(),
            Column::make('Langganan', 'status_subscription_label', 'status_subscription')->sortable(),
            Column::make('Staff', 'vendor_staff_count')->sortable(),
            Column::make('Layanan', 'vendor_services_count')->sortable(),
            Column::action('Aksi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status_verification_label', 'status_verification')
                ->dataSource(collect(['pending', 'approved', 'rejected'])->map(fn (string $value) => ['id' => $value, 'name' => ucfirst($value)]))
                ->optionLabel('name')->optionValue('id'),
            Filter::select('status_subscription_label', 'status_subscription')
                ->dataSource(collect(['inactive', 'active', 'suspended'])->map(fn (string $value) => ['id' => $value, 'name' => ucfirst($value)]))
                ->optionLabel('name')->optionValue('id'),
        ];
    }

    public function actions(Vendor $row): array
    {
        return [
            Button::add('view')->slot('Detail')->class('text-blue-700')->dispatch('viewVendor', ['rowId' => $row->id]),
            Button::add('edit')->slot('Edit')->class('text-green-700')->dispatch('editVendor', ['rowId' => $row->id]),
        ];
    }
}
