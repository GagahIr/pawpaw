<?php

namespace App\Actions\Client\Booking;

use App\Models\BookingClinic;
use App\Models\BookingGrooming;
use App\Models\BookingPetHotel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetActiveBookingsAction
{
    /** Batas tunggu konfirmasi vendor; dipakai juga oleh command bookings:expire-pending. */
    public const PENDING_TTL_HOURS = 24;

    public const ACTIVE_STATUSES = ['pending', 'confirmed'];

    public const HISTORY_STATUSES = ['completed', 'cancelled', 'rejected', 'expired'];

    private const SOURCES = [
        'clinic' => BookingClinic::class,
        'grooming' => BookingGrooming::class,
        'pet_hotel' => BookingPetHotel::class,
    ];

    /**
     * @param  array{status?:string,type?:string,search?:string,from?:string,to?:string,sort?:string}  $filters
     *   sort: newest (terbaru dipesan, default) | soonest (jadwal terdekat) | latest (jadwal terjauh)
     * @return array{items: Collection, has_more: bool}
     */
    public function execute(int $userClientId, bool $history = false, array $filters = [], int $limit = 10): array
    {
        $f = array_merge(['status' => '', 'type' => '', 'search' => '', 'from' => '', 'to' => '', 'sort' => 'newest'], $filters);

        $from = $this->safeDate($f['from']);
        $to = $this->safeDate($f['to']);
        if ($from && $to && $to < $from) {
            [$from, $to] = [$to, $from];
        }

        $allowedStatuses = $history ? self::HISTORY_STATUSES : self::ACTIVE_STATUSES;
        $status = in_array($f['status'], $allowedStatuses, true) ? $f['status'] : null;
        $sources = isset(self::SOURCES[$f['type']]) ? [$f['type'] => self::SOURCES[$f['type']]] : self::SOURCES;

        $bookings = collect();

        foreach ($sources as $type => $model) {
            $isHotel = $type === 'pet_hotel';
            $query = $model::query()
                ->where('user_client_id', $userClientId)
                ->with(['vendorServiceItem.vendorService.vendor', 'vendorServiceItem.vendorService.categoryService']);

            // Pending yang sudah kedaluwarsa dihitung sebagai "expired" walau cron belum mengubah statusnya
            $stale = fn ($q) => $this->applyStale($q, $isHotel);

            if ($history) {
                if ($status === 'expired') {
                    $query->where(fn ($q) => $q->where('status', 'expired')->orWhere($stale));
                } elseif ($status) {
                    $query->where('status', $status);
                } else {
                    $query->where(fn ($q) => $q->whereNotIn('status', self::ACTIVE_STATUSES)->orWhere($stale));
                }
            } else {
                $query->whereIn('status', $status ? [$status] : self::ACTIVE_STATUSES)->whereNot($stale);
            }

            if ($term = trim($f['search'])) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                $query->whereHas('vendorServiceItem', fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhereHas('vendorService.vendor', fn ($v) => $v->where('name', 'like', $like)));
            }

            // Filter tanggal berdasarkan jadwal (pet hotel: rentang menginap yang beririsan)
            if ($isHotel) {
                $from && $query->whereDate('end_date', '>=', $from);
                $to && $query->whereDate('start_date', '<=', $to);
            } else {
                $from && $query->whereDate('booking_date', '>=', $from);
                $to && $query->whereDate('booking_date', '<=', $to);
            }

            // Urutan di level DB agar limit per jenis tetap benar
            match ($f['sort']) {
                'soonest' => $isHotel
                    ? $query->orderBy('start_date')
                    : $query->orderBy('booking_date')->orderBy('time_slot'),
                'latest' => $isHotel
                    ? $query->orderByDesc('start_date')
                    : $query->orderByDesc('booking_date')->orderByDesc('time_slot'),
                default => $query->orderByDesc('created_at')->orderByDesc('id'),
            };

            $bookings = $bookings->merge(
                $query->limit($limit + 1)->get()->toBase()->map(fn ($b) => $this->normalizeBooking($b, $type))
            );
        }

        // Booking pending yang jadwalnya sudah lewat (cron belum jalan) tidak ditampilkan sebagai aktif
        if (! $history) {
            $bookings = $bookings->reject(fn ($b) => $b['status'] === 'expired');
        }

        $bookings = (match ($f['sort']) {
            'soonest' => $bookings->sortBy('sort_date'),
            'latest' => $bookings->sortByDesc('sort_date'),
            default => $bookings->sortByDesc('created_ts'),
        })->values();

        return [
            'items' => $bookings->take($limit),
            'has_more' => $bookings->count() > $limit,
        ];
    }

    /** Kondisi SQL: booking pending yang sudah melewati batas waktu konfirmasi. */
    private function applyStale(Builder $query, bool $isHotel): void
    {
        $now = now();
        $today = $now->toDateString();

        $query->where('status', 'pending')->where(function ($w) use ($isHotel, $now, $today) {
            $w->where('created_at', '<=', $now->copy()->subHours(self::PENDING_TTL_HOURS));

            if ($isHotel) {
                $w->orWhereDate('start_date', '<', $today);
            } else {
                $w->orWhereDate('booking_date', '<', $today)
                    ->orWhere(fn ($x) => $x->whereDate('booking_date', $today)
                        ->where('time_slot', '<=', $now->hour * 60 + $now->minute));
            }
        });
    }

    private function normalizeBooking(BookingClinic|BookingGrooming|BookingPetHotel $booking, string $type): array
    {
        $item = $booking->vendorServiceItem;
        $service = $item?->vendorService;
        $vendor = $service?->vendor;
        $category = $service?->categoryService;
        $unitPrice = $item?->price;
        $duration = $item?->duration;

        $nights = null;
        $timeLabel = null;

        if ($type === 'pet_hotel') {
            $start = $booking->start_date;
            $end = $booking->end_date;
            $nights = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));

            $dateLabel = $this->date($start).' → '.$this->date($end);
            $startsAt = $start->copy()->startOfDay();
            $deadline = $start->copy()->endOfDay(); // lewat hari check-in tanpa konfirmasi = kedaluwarsa
            $total = $unitPrice !== null ? $unitPrice * $nights : null;
        } else {
            $startsAt = $booking->booking_date->copy()->startOfDay()->addMinutes($booking->time_slot);
            $deadline = $startsAt->copy();
            $endsAt = $duration ? $startsAt->copy()->addMinutes($duration) : $startsAt->copy();

            $dateLabel = $this->date($startsAt);
            $timeLabel = $startsAt->format('H:i').($duration ? ' – '.$endsAt->format('H:i') : '');
            $total = $unitPrice;
        }

        $status = $booking->status;
        if ($status === 'pending' && ($deadline->isPast() || $booking->created_at->lte(now()->subHours(self::PENDING_TTL_HOURS)))) {
            $status = 'expired';
        }

        $prefix = ['clinic' => 'KLN', 'grooming' => 'GRM', 'pet_hotel' => 'HTL'][$type];

        return [
            'id' => $booking->id,
            'type' => $type,
            'code' => sprintf('#%s-%04d', $prefix, $booking->id),
            'category_label' => $category?->name ?? '-',
            'vendor_name' => $vendor?->name ?? '-',
            'vendor_slug' => $vendor?->slug ?? '',
            'whatsapp_url' => $this->whatsappUrl($vendor?->contact),
            'maps_url' => $vendor?->latitude && $vendor?->longitude
                ? 'https://www.google.com/maps/search/?api=1&query='.$vendor->latitude.','.$vendor->longitude
                : null,
            'service_item_name' => $item?->name ?? '-',
            'unit_price' => $unitPrice,
            'total_price' => $total,
            'nights' => $nights,
            'status' => $status,
            'date_label' => $dateLabel,
            'time_label' => $timeLabel,
            'created_human' => $booking->created_at?->copy()->locale('id')->diffForHumans(),
            'created_ts' => $booking->created_at?->timestamp ?? 0,
            'sort_date' => $startsAt->toDateTimeString(),
        ];
    }

    private function safeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function date(Carbon $date): string
    {
        return $date->copy()->locale('id')->isoFormat('ddd, D MMM YYYY');
    }

    private function whatsappUrl(?string $contact): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $contact);

        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits;
    }
}