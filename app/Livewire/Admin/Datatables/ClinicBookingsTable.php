<?php

namespace App\Livewire\Admin\Datatables;

use App\Actions\Admin\Booking\UpdateClinicBookingStatusAction;
use App\Models\BookingClinic;
use App\Models\VendorServiceItem;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ClinicBookingsTable extends PowerGridComponent
{
    public string $tableName = 'ClinicBookingsTable';

    public string $sortField = 'booking_date';

    public string $sortDirection = 'desc';

    private const STATUS_LABELS = [
        'pending' => 'Pending',
        'confirmed' => 'Dikonfirmasi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'rejected' => 'Ditolak',
    ];

    private const STATUS_CLASSES = [
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'cancelled' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/40 dark:text-zinc-300',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    ];

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
        // Scope vendor WAJIB tetap ada: ini batas keamanan antar vendor.
        return BookingClinic::query()
            ->forVendor(app('vendor.context')->id())
            ->with(['userClient.user', 'vendorServiceItem.vendorService.categoryService']);
    }

    public function relationSearch(): array
    {
        return [
            'userClient.user' => ['name'],
            'vendorServiceItem' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('customer', fn (BookingClinic $row) => '<p class="font-medium">'.e($row->userClient?->user?->name ?? '-').'</p>'
                .'<p class="text-xs text-zinc-500">'.e($row->userClient?->user?->phone_number ?? '').'</p>')
            ->add('service', fn (BookingClinic $row) => '<p class="font-medium">'.e($row->vendorServiceItem?->name ?? '-').'</p>'
                .'<p class="text-xs text-zinc-500">'.e($row->vendorServiceItem?->vendorService?->categoryService?->name ?? '').'</p>')
            ->add('service_name', fn (BookingClinic $row) => $row->vendorServiceItem?->name)
            ->add('booking_date')
            ->add('schedule', fn (BookingClinic $row) => $row->booking_date->translatedFormat('d M Y')
                .' · '.sprintf('%02d:%02d', intdiv($row->time_slot, 60), $row->time_slot % 60))
            ->add('status')
            ->add('status_label', fn (BookingClinic $row) => self::STATUS_LABELS[$row->status] ?? $row->status)
            ->add('status_badge', fn (BookingClinic $row) => '<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium '
                .(self::STATUS_CLASSES[$row->status] ?? '').'">'
                .e(self::STATUS_LABELS[$row->status] ?? $row->status).'</span>');
    }

    public function columns(): array
    {
        return [
            Column::make('Pelanggan', 'customer'),

            Column::make('Layanan', 'service'),

            Column::make('Jadwal', 'schedule', 'booking_date')
                ->sortable(),

            Column::make('Status', 'status_badge', 'status')
                ->sortable()
                ->searchable(),

            Column::action('Aksi'),
        ];
    }

    public function filters(): array
    {
        return [
            // Catatan: parameter pertama WAJIB sama dengan field pada Column::make(...)
            // agar filter tampil di tabel.
            Filter::inputText('customer')
                ->placeholder('Nama pelanggan')
                ->operators(['contains'])
                ->builder(function (Builder $query, $value) {
                    // PowerGrid mengirim array (mis. ['value' => '...', 'operator' => '...']).
                    $keyword = is_array($value) ? ($value['value'] ?? '') : $value;
                    $keyword = trim((string) $keyword);

                    if ($keyword === '') {
                        return;
                    }

                    $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);

                    $query->whereHas(
                        'userClient.user',
                        fn (Builder $q) => $q->where('name', 'like', '%'.$keyword.'%')
                    );
                }),

            Filter::select('service', 'vendor_service_item_id')
                ->dataSource(
                    VendorServiceItem::query()
                        ->whereHas('vendorService', fn (Builder $query) => $query->where('vendor_id', app('vendor.context')->id()))
                        ->orderBy('name')
                        ->get(['id', 'name'])
                )
                ->optionLabel('name')
                ->optionValue('id'),

            Filter::datepicker('schedule', 'booking_date'),

            Filter::select('status_badge', 'status')
                ->dataSource(collect(self::STATUS_LABELS)
                    ->map(fn (string $label, string $id) => ['id' => $id, 'name' => $label])
                    ->values())
                ->optionLabel('name')
                ->optionValue('id'),
        ];
    }

    #[On('updateBookingStatus')]
    public function updateBookingStatus(int $rowId, string $status): void
    {
        try {
            app(UpdateClinicBookingStatusAction::class)
                ->execute($rowId, app('vendor.context')->id(), $status);
        } catch (ValidationException $exception) {
            Flux::toast($exception->validator->errors()->first(), variant: 'danger');

            return;
        }

        Flux::toast('Status booking berhasil diperbarui.', variant: 'success');
    }

    public function actions(BookingClinic $row): array
    {
        $base = 'inline-flex items-center justify-center px-2.5 py-1 text-xs font-medium rounded-md transition-colors ';

        return [
            Button::add('confirm')
                ->slot('Konfirmasi')
                ->class($base.'text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'confirmed'])
                ->can($row->status === 'pending'),

            Button::add('reject')
                ->slot('Tolak')
                ->class($base.'text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30 dark:hover:bg-red-900/50')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'rejected'])
                ->can($row->status === 'pending'),

            Button::add('complete')
                ->slot('Selesai')
                ->class($base.'text-green-700 bg-green-50 hover:bg-green-100 dark:text-green-300 dark:bg-green-900/30 dark:hover:bg-green-900/50')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'completed'])
                ->can($row->status === 'confirmed'),

            Button::add('cancel')
                ->slot('Batalkan')
                ->class($base.'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700/40')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'cancelled'])
                ->can($row->status === 'confirmed'),
        ];
    }
}
