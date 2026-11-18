<?php

namespace App\Livewire\SuperAdmin\Datatables;

use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class TransactionsTable extends PowerGridComponent
{
    public string $tableName = 'TransactionsTable';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return VendorPayment::query()->with('vendor');
    }

    public function relationSearch(): array
    {
        return ['vendor' => ['name', 'email']];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('midtrans_order_id')
            ->add('vendor_name', fn (VendorPayment $row): string => $row->vendor?->name ?? '-')
            ->add('amount')
            ->add('amount_formatted', fn (VendorPayment $row): string => $row->currency.' '.number_format($row->amount, 0, ',', '.'))
            ->add('status')
            ->add('status_label', fn (VendorPayment $row): string => ucfirst(str_replace('_', ' ', $row->status)))
            ->add('paid_at')
            ->add('paid_at_formatted', fn (VendorPayment $row): string => $row->paid_at?->format('d M Y H:i') ?? '-')
            ->add('created_at')
            ->add('created_at_formatted', fn (VendorPayment $row): string => $row->created_at?->format('d M Y H:i') ?? '-');
    }

    public function columns(): array
    {
        return [
            Column::make('Order ID', 'midtrans_order_id')->searchable()->sortable(),
            Column::make('Mitra', 'vendor_name')->searchable(),
            Column::make('Nominal', 'amount_formatted', 'amount')->sortable(),
            Column::make('Status', 'status_label', 'status')->sortable(),
            Column::make('Dibayar', 'paid_at_formatted', 'paid_at')->sortable(),
            Column::make('Dibuat', 'created_at_formatted', 'created_at')->sortable(),
            Column::action('Aksi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status')
                ->dataSource(collect(['pending', 'settlement', 'capture', 'expire', 'cancel', 'deny', 'failure'])->map(
                    fn (string $status): array => ['id' => $status, 'name' => ucfirst($status)],
                ))
                ->optionLabel('name')
                ->optionValue('id'),
        ];
    }

    public function actions(VendorPayment $row): array
    {
        return [
            Button::add('view')
                ->slot('Detail')
                ->class('text-blue-700')
                ->dispatch('viewTransaction', ['rowId' => $row->id]),
        ];
    }
}
