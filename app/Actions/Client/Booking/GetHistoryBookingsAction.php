<?php

namespace App\Actions\Client\Booking;

use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use Illuminate\Support\Collection;

class GetHistoryBookingsAction
{
    /**
     * @return Collection<int, array{id: int, type: string, category_label: string, vendor_name: string, vendor_slug: string, service_item_name: string, price: int|float|null, status: string, date_label: string, sort_date: string}>
     */
    public function execute(int $userClientId): Collection
    {
        $eagerLoad = ['vendorServiceItem.vendorService.vendor', 'vendorServiceItem.vendorService.categoryService'];
        $historyStatuses = ['completed', 'cancelled', 'rejected'];

        $clinics = BookingClinic::query()
            ->where('user_client_id', $userClientId)
            ->whereIn('status', $historyStatuses)
            ->with($eagerLoad)
            ->get()
            ->toBase()
            ->map(fn ($b) => $this->normalizeBooking($b, 'clinic'));

        $groomings = BookingGrooming::query()
            ->where('user_client_id', $userClientId)
            ->whereIn('status', $historyStatuses)
            ->with($eagerLoad)
            ->get()
            ->toBase()
            ->map(fn ($b) => $this->normalizeBooking($b, 'grooming'));

        $petHotels = BookingPetHotel::query()
            ->where('user_client_id', $userClientId)
            ->whereIn('status', $historyStatuses)
            ->with($eagerLoad)
            ->get()
            ->toBase()
            ->map(fn ($b) => $this->normalizeBooking($b, 'pet_hotel'));

        return $clinics->merge($groomings)->merge($petHotels)
            ->sortByDesc('sort_date')
            ->values();
    }

    /**
     * @return array{id: int, type: string, category_label: string, vendor_name: string, vendor_slug: string, service_item_name: string, price: int|float|null, status: string, date_label: string, sort_date: string}
     */
    private function normalizeBooking(BookingClinic|BookingGrooming|BookingPetHotel $booking, string $type): array
    {
        $item = $booking->vendorServiceItem;
        $service = $item?->vendorService;
        $vendor = $service?->vendor;
        $category = $service?->categoryService;

        if ($type === 'pet_hotel') {
            $dateLabel = $booking->start_date->format('d M Y').' — '.$booking->end_date->format('d M Y');
            $sortDate = $booking->start_date->toDateTimeString();
        } else {
            $hours = intdiv($booking->time_slot, 60);
            $minutes = $booking->time_slot % 60;
            $timeFormatted = sprintf('%02d:%02d', $hours, $minutes);
            $dateLabel = $booking->booking_date->format('d M Y').', '.$timeFormatted;
            $sortDate = $booking->booking_date->toDateTimeString();
        }

        return [
            'id' => $booking->id,
            'type' => $type,
            'category_label' => $category?->name ?? '-',
            'vendor_name' => $vendor?->name ?? '-',
            'vendor_slug' => $vendor?->slug ?? '',
            'service_item_name' => $item?->name ?? '-',
            'price' => $item?->price,
            'status' => $booking->status,
            'date_label' => $dateLabel,
            'sort_date' => $sortDate,
        ];
    }
}
