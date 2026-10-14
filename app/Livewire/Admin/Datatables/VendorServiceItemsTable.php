<?php

namespace App\Livewire\Admin\Datatables;

use App\Actions\Admin\VendorServiceItem\DeleteVendorServiceItem;
use App\Enums\SystemMessage;
use App\Models\CategoryService;
use App\Models\VendorServiceItem;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class VendorServiceItemsTable extends PowerGridComponent
{
    public string $tableName = 'VendorServiceItemsTable';

    public ?int $categoryServiceId = null;

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $builder = VendorServiceItem::query()->with(['vendorService.categoryService']);

        if ($this->categoryServiceId) {
            $builder->whereHas('vendorService', function ($q) {
                $q->where('category_service_id', $this->categoryServiceId);
            });
        }

        return $builder;
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('price')
            ->add('price_formatted', fn (VendorServiceItem $model) => 'Rp '.number_format($model->price, 0, ',', '.'))
            ->add('duration')
            ->add('duration_formatted', fn (VendorServiceItem $model) => $model->duration.' menit')
            ->add('capacity')
            ->add('capacity_formatted', fn (VendorServiceItem $model) => $model->capacity ?? '-')
            ->add('category_name', fn (VendorServiceItem $model) => $model->vendorService?->categoryService?->name ?? '-')
            ->add('created_at')
            ->add('created_at_formatted', fn (VendorServiceItem $model) => Carbon::parse($model->created_at)->format('d M Y H:i'));
    }

    public function columns(): array
    {
        return [
            Column::add()
                ->title('Nama layanan')
                ->field('name')
                ->searchable()
                ->sortable(),

            Column::make('Harga', 'price_formatted', 'price')
                ->sortable(),

            Column::make('Durasi', 'duration_formatted', 'duration')
                ->sortable(),

            Column::make('Kapasitas', 'capacity_formatted', 'capacity')
                ->sortable(),

            // Column::make('Kategori', 'category_name')
            //     ->sortable(),

            // Column::make('Dibuat', 'created_at_formatted', 'created_at')
            //     ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function filters(): array
    {
        $filters = [];

        if (! $this->categoryServiceId) {
            $filters[] = Filter::select('category_name', 'vendorService.category_service_id')
                ->dataSource(CategoryService::query()->pluck('name', 'id')->toArray())
                ->optionLabel('name')
                ->optionValue('id');
        }

        return $filters;
    }

    #[On('editItem')]
    public function editItem($rowId): void
    {
        $this->dispatch('openEditForm', id: $rowId);
    }

    #[On('deleteItem')]
    public function deleteItem($rowId): void
    {
        app(DeleteVendorServiceItem::class)->execute($rowId);
        Flux::toast(SystemMessage::DATA_DELETED_SUCCESS->value, variant: 'success');
        $this->dispatch('pg:eventRefresh-VendorServiceItemsTable');
    }

    #[On('restoreItem')]
    public function restoreItem($rowId): void
    {
        $model = VendorServiceItem::withTrashed()->find($rowId);

        if ($model && $model->trashed()) {
            $model->restore();
            Flux::toast(SystemMessage::DATA_RESTORED_SUCCESS->value, variant: 'success');
            $this->dispatch('pg:eventRefresh-VendorServiceItemsTable');
        }
    }

    public function actions(VendorServiceItem $row): array
    {
        return [
            Button::add('edit')
                ->slot('<div class="flex items-center gap-1">
                <p>Edit</p>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>')
                ->class('inline-flex items-center justify-center p-1.5 text-blue-600 hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-md transition-colors')
                ->dispatch('editItem', ['rowId' => $row->id]),

            Button::add('delete')
                ->slot('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>')
                ->class('inline-flex items-center justify-center p-1.5 text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30 rounded-md transition-colors')
                ->dispatch('openDeleteConfirm', ['id' => $row->id])
                ->can(! $row->trashed()),

            Button::add('restore')
                ->slot('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6m-6 6h12a6 6 0 010 12h-3"/></svg>')
                ->class('inline-flex items-center justify-center p-1.5 text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-green-900/30 rounded-md transition-colors')
                ->dispatch('restoreItem', ['rowId' => $row->id])
                ->can($row->trashed())
                ->confirmPrompt(__('Pulihkan item ini?'), __('Pulihkan')),
        ];
    }
}
