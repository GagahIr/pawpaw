<?php

namespace App\Livewire\SuperAdmin\Datatables;

use App\Enums\SystemMessage;
use App\Enums\VendorVerificationStatus;
use App\Models\Vendor;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class VendorVerificationsTable extends PowerGridComponent
{
    public string $tableName = 'VendorVerificationsTable';

    public function setUp(): array
    {
        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return $this->builder();
    }

    public function builder(): Builder
    {
        return Vendor::query()
            ->where('status_verification', VendorVerificationStatus::Pending)
            ->withCount(['vendorServices']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('contact')
            ->add('status_subscription')
            ->add('status_subscription_label', fn (Vendor $row): string => ucfirst($row->status_subscription?->value ?? '-'))
            ->add('vendor_services_count')
            ->add('is_verified', fn (Vendor $row): int => $row->status_verification === VendorVerificationStatus::Approved ? 1 : 0);
    }

    public function columns(): array
    {
        return [
            Column::make('Nama Mitra', 'name')->searchable()->sortable(),
            Column::make('Kontak', 'contact')->searchable(),
            Column::make('Langganan', 'status_subscription_label', 'status_subscription')->sortable(),
            Column::make('Layanan', 'vendor_services_count')->sortable(),
            Column::make('Verifikasi', 'is_verified', 'status_verification')->toggleable(true, 'Terverifikasi', 'Menunggu Verifikasi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status_subscription_label', 'status_subscription')
                ->dataSource(collect(['inactive', 'active', 'suspended'])->map(fn (string $value): array => ['id' => $value, 'name' => ucfirst($value)]))
                ->optionLabel('name')->optionValue('id'),
        ];
    }

    public function onUpdatedToggleable(string $id, string $field, string $value): void
    {
        if (in_array($field, ['status_verification', 'is_verified'], true)) {
            $status = (int) $value === 1
                ? VendorVerificationStatus::Approved
                : VendorVerificationStatus::Pending;

            $vendor = Vendor::query()->find($id);

            if ($vendor) {
                $vendor->update([
                    'status_verification' => $status,
                ]);

                if (class_exists(Flux::class)) {
                    try {
                        Flux::toast(SystemMessage::DATA_UPDATED_SUCCESS->value, variant: 'success');
                    } catch (\Throwable) {
                        // Ignore if called outside livewire request context (e.g. tests)
                    }
                }
            }
        }
    }
}
