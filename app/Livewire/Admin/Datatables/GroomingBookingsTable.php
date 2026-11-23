<?php

namespace App\Livewire\Admin\Datatables;

use App\Actions\Admin\Booking\UpdateGroomingBookingStatusAction;
use App\Models\BookingGrooming;
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

final class GroomingBookingsTable extends PowerGridComponent
{
    public string $tableName = 'GroomingBookingsTable';

    public string $sortField = 'booking_date';

    public string $sortDirection = 'desc';

    private const STATUS_LABELS = [
        'pending' => 'Pending',
        'confirmed' => 'Dikonfirmasi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'rejected' => 'Ditolak',
        'no_show' => 'Tidak Hadir',
    ];

    private const STATUS_CLASSES = [
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'cancelled' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/40 dark:text-zinc-300',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'no_show' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
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
        return BookingGrooming::query()
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
            ->add('customer', fn(BookingGrooming $row) => '<p class="font-medium">' . e($row->userClient?->user?->name ?? '-') . '</p>'
                . '<p class="text-xs text-zinc-500">' . e($row->userClient?->user?->phone_number ?? '') . '</p>')
            ->add('service', fn(BookingGrooming $row) => '<p class="font-medium">' . e($row->vendorServiceItem?->name ?? '-') . '</p>'
                . '<p class="text-xs text-zinc-500">' . e($row->vendorServiceItem?->vendorService?->categoryService?->name ?? '') . '</p>')
            ->add('service_name', fn(BookingGrooming $row) => $row->vendorServiceItem?->name)
            ->add('booking_date')
            ->add('schedule', fn(BookingGrooming $row) => $row->booking_date->translatedFormat('d M Y')
                . ' · ' . sprintf('%02d:%02d', intdiv($row->time_slot, 60), $row->time_slot % 60))
            ->add('status')
            ->add('status_label', fn(BookingGrooming $row) => self::STATUS_LABELS[$row->status] ?? $row->status)
            ->add('status_badge', fn(BookingGrooming $row) => '<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium '
                . (self::STATUS_CLASSES[$row->status] ?? '') . '">'
                . e(self::STATUS_LABELS[$row->status] ?? $row->status) . '</span>');
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
                        fn(Builder $q) => $q->where('name', 'like', '%' . $keyword . '%')
                    );
                }),

            Filter::select('service', 'vendor_service_item_id')
                ->dataSource(
                    VendorServiceItem::query()
                        ->whereHas('vendorService', fn(Builder $query) => $query->where('vendor_id', app('vendor.context')->id()))
                        ->orderBy('name')
                        ->get(['id', 'name'])
                )
                ->optionLabel('name')
                ->optionValue('id'),

            Filter::datepicker('schedule', 'booking_date'),

            Filter::select('status_badge', 'status')
                ->dataSource(collect(self::STATUS_LABELS)
                    ->map(fn(string $label, string $id) => ['id' => $id, 'name' => $label])
                    ->values())
                ->optionLabel('name')
                ->optionValue('id'),
        ];
    }

    #[On('updateBookingStatus')]
    public function updateBookingStatus(int $rowId, string $status): void
    {
        try {
            app(UpdateGroomingBookingStatusAction::class)
                ->execute($rowId, app('vendor.context')->id(), $status);
        } catch (ValidationException $exception) {
            Flux::toast($exception->validator->errors()->first(), variant: 'danger');

            return;
        }

        Flux::toast('Status booking berhasil diperbarui.', variant: 'success');
    }

    public function actions(BookingGrooming $row): array
    {
        $base = 'inline-flex items-center justify-center px-2.5 py-1 text-xs font-medium rounded-md transition-colors ';

        // Cek apakah jadwal booking sudah lewat (booking_date + time_slot)
        $bookingDateTime = $row->booking_date->copy()->addMinutes($row->time_slot);
        $isPastSchedule = now()->greaterThan($bookingDateTime);

        $whatsappUrl = $this->whatsappUrl($row->userClient?->user?->phone_number);

        return [
            Button::add('confirm')
                ->slot('Konfirmasi')
                ->class($base . 'text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-900/50')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'confirmed'])
                ->can($row->status === 'pending'),

            Button::add('reject')
                ->slot('Tolak')
                ->class($base . 'text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30 dark:hover:bg-red-900/50')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'rejected'])
                ->can($row->status === 'pending'),

            Button::add('complete')
                ->slot('Selesai')
                ->class($base . 'text-green-700 bg-green-50 hover:bg-green-100 dark:text-green-300 dark:bg-green-900/30 dark:hover:bg-green-900/50')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'completed'])
                ->can($row->status === 'confirmed'),

            Button::add('no_show')
                ->slot('Tidak Hadir')
                ->class($base . 'text-orange-700 bg-orange-50 hover:bg-orange-100 dark:text-orange-300 dark:bg-orange-900/30 dark:hover:bg-orange-900/50')
                ->dispatch('updateBookingStatus', ['rowId' => $row->id, 'status' => 'no_show'])
                ->can($row->status === 'confirmed' && $isPastSchedule),

            Button::add('whatsapp')
                ->slot('Hubungi via <svg class="inline-block w-4 h-4 ml-1 fill-current" viewBox="0 0 448 512" xmlns="http://w3.org"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>')
                ->class($base . 'inline-flex items-center text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50')
                ->dispatch('open-whatsapp', ['url' => $whatsappUrl])
                ->can($whatsappUrl !== null),
        ];
    }

    private function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        // Panjang wajar nomor internasional (E.164 maks 15 digit)
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return 'https://wa.me/' . $digits;
    }
}
