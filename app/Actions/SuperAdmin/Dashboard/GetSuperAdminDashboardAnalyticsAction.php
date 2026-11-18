<?php

namespace App\Actions\SuperAdmin\Dashboard;

use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetSuperAdminDashboardAnalyticsAction
{
    private const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const MAX_DAILY_RANGE_DAYS = 31;

    private const MAX_WEEKLY_RANGE_DAYS = 120;

    private const PAYMENT_STATUSES = [
        'success' => ['label' => 'Berhasil', 'color' => '#1B5E43'],
        'pending' => ['label' => 'Menunggu', 'color' => '#F59E0B'],
        'failed' => ['label' => 'Gagal', 'color' => '#DC2626'],
        'expired' => ['label' => 'Kedaluwarsa', 'color' => '#A1A1AA'],
        'refunded' => ['label' => 'Refund', 'color' => '#3B82F6'],
    ];

    private const BOOKING_STATUSES = [
        'completed' => ['label' => 'Selesai', 'color' => '#1B5E43'],
        'confirmed' => ['label' => 'Dikonfirmasi', 'color' => '#3B82F6'],
        'pending' => ['label' => 'Menunggu', 'color' => '#F59E0B'],
        'cancelled' => ['label' => 'Dibatalkan', 'color' => '#A1A1AA'],
        'rejected' => ['label' => 'Ditolak', 'color' => '#DC2626'],
    ];

    /**
     * Tanggal yang dipakai tiap jenis booking sebagai tanggal kunjungan.
     *
     * @var array<string, array{table: string, date_column: string, label: string, color: string}>
     */
    private const BOOKING_TYPES = [
        'clinic' => ['table' => 'bookings_clinic', 'date_column' => 'booking_date', 'label' => 'Klinik', 'color' => '#1B5E43'],
        'grooming' => ['table' => 'bookings_grooming', 'date_column' => 'booking_date', 'label' => 'Grooming', 'color' => '#F59E0B'],
        'pet_hotel' => ['table' => 'bookings_pet_hotel', 'date_column' => 'start_date', 'label' => 'Pet Hotel', 'color' => '#3B82F6'],
    ];

    /**
     * Rentang tanggal untuk filter dashboard, mengikuti pola dashboard owner.
     *
     * Preset yang dikenal: this_month, last_month, last_7_days, last_30_days, last_12_months, this_year, custom.
     * Preset tidak dikenal atau tanggal kustom yang tidak valid jatuh ke bulan ini.
     *
     * @return array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     range_label: string,
     *     prev_range_label: string,
     *     comparison_label: string,
     *     granularity: string
     * }
     */
    public function resolveDateRange(
        string $preset = 'this_month',
        ?string $customStart = null,
        ?string $customEnd = null,
        ?CarbonInterface $referenceNow = null,
    ): array {
        $now = CarbonImmutable::instance($referenceNow ?? now())->locale('id');

        return match ($preset) {
            'last_month' => $this->buildMonthWindow($preset, $now->subMonthNoOverflow()),
            'last_7_days' => $this->buildRollingWindow($preset, $now->subDays(6)->startOfDay(), $now->endOfDay(), 'vs 7 hari sebelumnya'),
            'last_30_days' => $this->buildRollingWindow($preset, $now->subDays(29)->startOfDay(), $now->endOfDay(), 'vs 30 hari sebelumnya'),
            'last_12_months' => $this->buildTwelveMonthWindow($preset, $now),
            'this_year' => $this->buildYearWindow($preset, $now),
            'custom' => $this->buildCustomWindow($customStart, $customEnd, $now),
            default => $this->buildMonthWindow('this_month', $now),
        };
    }

    /**
     * Seluruh data dashboard super admin dalam satu panggilan.
     *
     * Metrik pendapatan, booking, pembayaran, tabel, dan statistik pertumbuhan mengikuti rentang tanggal.
     * Status mitra dan antrean verifikasi adalah kondisi saat ini, sehingga tidak ikut berubah.
     *
     * @param  array<string, mixed>|null  $dateRange  Hasil resolveDateRange(); bulan ini bila kosong.
     * @return array{
     *     metrics: array{revenue: int, active_vendors: int, pending_verification: int, bookings: int},
     *     revenue: array<string, mixed>,
     *     vendor_status: array{subscription: array<string, mixed>, verification: array<string, mixed>},
     *     booking_distribution: array{by_type: array<string, mixed>, by_status: array<string, mixed>},
     *     payment_health: array<string, mixed>,
     *     tables: array<string, Collection>,
     *     growth: array{new_vendors: int, payment_success_rate: float|null, avg_booking_per_vendor: float}
     * }
     */
    public function getOverview(?array $dateRange = null, ?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::instance($now ?? now());
        $dateRange ??= $this->resolveDateRange('this_month', null, null, $now);

        $vendorStatus = $this->getVendorStatus($now);
        $bookingDistribution = $this->getBookingDistribution($dateRange['start'], $dateRange['end']);
        $paymentHealth = $this->getPaymentHealth($dateRange['start'], $dateRange['end']);
        $revenue = $this->getRevenueTrend($dateRange);
        $activeVendors = $vendorStatus['subscription']['counts']['active'];

        return [
            'metrics' => [
                'revenue' => $revenue['current_total'],
                'active_vendors' => $activeVendors,
                'pending_verification' => $vendorStatus['verification']['counts']['pending'],
                'bookings' => $bookingDistribution['total'],
            ],
            'revenue' => $revenue,
            'vendor_status' => $vendorStatus,
            'booking_distribution' => [
                'by_type' => $bookingDistribution['by_type'],
                'by_status' => $bookingDistribution['by_status'],
            ],
            'payment_health' => $paymentHealth,
            'tables' => [
                'top_vendors' => $this->getTopVendors($dateRange),
                'recent_payments' => $this->getRecentPayments($dateRange),
                'pending_verifications' => $this->getPendingVerifications(),
                'recent_bookings' => $this->getRecentBookings($dateRange),
            ],
            'growth' => [
                'new_vendors' => Vendor::query()
                    ->whereDate('join_at', '>=', $dateRange['start']->toDateString())
                    ->whereDate('join_at', '<=', $dateRange['end']->toDateString())
                    ->count(),
                'payment_success_rate' => $paymentHealth['success_rate'],
                'avg_booking_per_vendor' => $activeVendors > 0
                    ? round($bookingDistribution['total'] / $activeVendors, 1)
                    : 0.0,
            ],
        ];
    }

    /**
     * Pendapatan (pembayaran berstatus success) pada rentang terpilih, dikelompokkan per hari, minggu, atau bulan,
     * beserta perbandingan dengan periode sebelumnya yang sama panjang.
     *
     * @param  array<string, mixed>  $dateRange  Hasil resolveDateRange().
     * @return array{
     *     categories: list<string>,
     *     revenues: list<int>,
     *     current_total: int,
     *     previous_total: int,
     *     change_percent: float|null,
     *     has_data: bool,
     *     granularity: string,
     *     subtitle: string
     * }
     */
    public function getRevenueTrend(array $dateRange): array
    {
        /** @var CarbonImmutable $start */
        $start = $dateRange['start'];
        /** @var CarbonImmutable $end */
        $end = $dateRange['end'];
        $granularity = $dateRange['granularity'];

        $bucketCount = $this->countBuckets($start, $end, $granularity);
        $revenues = array_fill(0, $bucketCount, 0);

        VendorPayment::query()
            ->where('status', 'success')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$start, $end])
            ->get(['amount', DB::raw('COALESCE(paid_at, created_at) as revenue_at')])
            ->each(function (VendorPayment $payment) use (&$revenues, $start, $granularity): void {
                $index = $this->resolveBucketIndex(CarbonImmutable::parse($payment->getAttribute('revenue_at')), $start, $granularity);

                if (array_key_exists($index, $revenues)) {
                    $revenues[$index] += (int) $payment->amount;
                }
            });

        $previousTotal = (int) VendorPayment::query()
            ->where('status', 'success')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$dateRange['prev_start'], $dateRange['prev_end']])
            ->sum('amount');
        $currentTotal = array_sum($revenues);

        return [
            'categories' => $this->buildCategories($start, $bucketCount, $granularity),
            'revenues' => $revenues,
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'change_percent' => $previousTotal > 0 ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1) : null,
            'has_data' => $currentTotal > 0,
            'granularity' => $granularity,
            'subtitle' => 'Total pembayaran berhasil per '.match ($granularity) {
                'daily' => 'hari',
                'weekly' => 'minggu',
                default => 'bulan',
            },
        ];
    }

    /**
     * Status langganan dan verifikasi mitra saat ini.
     *
     * Mitra berstatus active yang masa langganannya sudah lewat dihitung Expired,
     * sama seperti aturan akses di EnsureVendorPaidSubscription.
     *
     * @return array{
     *     subscription: array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>},
     *     verification: array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>}
     * }
     */
    public function getVendorStatus(CarbonInterface $now): array
    {
        $vendors = Vendor::query()->get(['status_subscription', 'subscription_expired_at', 'status_verification']);

        $subscriptionCounts = ['active' => 0, 'expired' => 0, 'suspended' => 0, 'inactive' => 0];

        foreach ($vendors as $vendor) {
            $subscriptionCounts[$this->resolveEffectiveSubscription($vendor, $now)]++;
        }

        $verificationCounts = [
            'approved' => $vendors->where('status_verification', VendorVerificationStatus::Approved)->count(),
            'pending' => $vendors->where('status_verification', VendorVerificationStatus::Pending)->count(),
            'rejected' => $vendors->where('status_verification', VendorVerificationStatus::Rejected)->count(),
        ];

        return [
            'subscription' => $this->buildBreakdown($subscriptionCounts, [
                'active' => ['label' => 'Aktif', 'color' => '#1B5E43'],
                'expired' => ['label' => 'Kedaluwarsa', 'color' => '#F59E0B'],
                'suspended' => ['label' => 'Ditangguhkan', 'color' => '#DC2626'],
                'inactive' => ['label' => 'Belum aktif', 'color' => '#D4D4D8'],
            ]),
            'verification' => $this->buildBreakdown($verificationCounts, [
                'approved' => ['label' => 'Terverifikasi', 'color' => '#1B5E43'],
                'pending' => ['label' => 'Menunggu', 'color' => '#F59E0B'],
                'rejected' => ['label' => 'Ditolak', 'color' => '#DC2626'],
            ]),
        ];
    }

    /**
     * Booking pada rentang tanggal kunjungan per jenis dan per status dari tiga tabel booking.
     *
     * @return array{
     *     total: int,
     *     by_type: array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>},
     *     by_status: array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>}
     * }
     */
    public function getBookingDistribution(CarbonInterface $start, CarbonInterface $end): array
    {
        $typeCounts = [];
        $statusCounts = array_fill_keys(array_keys(self::BOOKING_STATUSES), 0);

        foreach (self::BOOKING_TYPES as $type => $config) {
            $perStatus = DB::table($config['table'])
                ->whereNull('deleted_at')
                ->whereDate($config['date_column'], '>=', $start->toDateString())
                ->whereDate($config['date_column'], '<=', $end->toDateString())
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $typeCounts[$type] = (int) $perStatus->sum();

            foreach ($perStatus as $status => $total) {
                if (array_key_exists($status, $statusCounts)) {
                    $statusCounts[$status] += (int) $total;
                }
            }
        }

        return [
            'total' => array_sum($typeCounts),
            'by_type' => $this->buildBreakdown($typeCounts, self::BOOKING_TYPES),
            'by_status' => $this->buildBreakdown($statusCounts, self::BOOKING_STATUSES),
        ];
    }

    /**
     * Jumlah pembayaran per status pada rentang tanggal dan tingkat keberhasilannya.
     *
     * Tingkat keberhasilan = success / (success + failed + expired), pembayaran yang masih pending belum dihitung.
     *
     * @return array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>, success_rate: float|null}
     */
    public function getPaymentHealth(CarbonInterface $start, CarbonInterface $end): array
    {
        $counts = array_fill_keys(array_keys(self::PAYMENT_STATUSES), 0);

        VendorPayment::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->each(function (mixed $total, string $status) use (&$counts): void {
                if (array_key_exists($status, $counts)) {
                    $counts[$status] = (int) $total;
                }
            });

        $finalised = $counts['success'] + $counts['failed'] + $counts['expired'];

        return $this->buildBreakdown($counts, self::PAYMENT_STATUSES) + [
            'success_rate' => $finalised > 0 ? round(($counts['success'] / $finalised) * 100, 1) : null,
        ];
    }

    /**
     * Lima mitra dengan total pendapatan (pembayaran success pada rentang tanggal) tertinggi.
     * Tiap mitra membawa atribut revenue_total dan effective_subscription.
     *
     * @param  array<string, mixed>  $dateRange  Hasil resolveDateRange().
     * @return Collection<int, Vendor>
     */
    public function getTopVendors(array $dateRange, int $limit = 5): Collection
    {
        $now = CarbonImmutable::now();
        $paidInRange = fn ($query) => $query
            ->where('status', 'success')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$dateRange['start'], $dateRange['end']]);

        return Vendor::query()
            ->whereHas('payments', $paidInRange)
            ->withSum(['payments as revenue_total' => $paidInRange], 'amount')
            ->orderByDesc('revenue_total')
            ->limit($limit)
            ->get()
            ->each(fn (Vendor $vendor) => $vendor->setAttribute('effective_subscription', $this->resolveEffectiveSubscription($vendor, $now)));
    }

    /**
     * @param  array<string, mixed>  $dateRange  Hasil resolveDateRange().
     * @return Collection<int, VendorPayment>
     */
    public function getRecentPayments(array $dateRange, int $limit = 5): Collection
    {
        return VendorPayment::query()
            ->with(['vendor' => fn ($query) => $query->withTrashed()])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Antrean verifikasi saat ini, yang paling lama menunggu di urutan teratas.
     *
     * @return Collection<int, Vendor>
     */
    public function getPendingVerifications(int $limit = 5): Collection
    {
        return Vendor::query()
            ->where('status_verification', VendorVerificationStatus::Pending)
            ->orderBy('join_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Booking terbaru dari ketiga jenis layanan pada rentang tanggal kunjungan, diurutkan berdasarkan waktu dibuat.
     *
     * @param  array<string, mixed>  $dateRange  Hasil resolveDateRange().
     * @return Collection<int, array{vendor_name: string, type: string, type_label: string, booked_at: CarbonImmutable, status: string, status_label: string}>
     */
    public function getRecentBookings(array $dateRange, int $limit = 5): Collection
    {
        return collect(self::BOOKING_TYPES)
            ->flatMap(function (array $config, string $type) use ($dateRange, $limit): Collection {
                return DB::table($config['table'].' as booking')
                    ->join('vendor_service_items as service_item', 'service_item.id', '=', 'booking.vendor_service_item_id')
                    ->join('vendor_services as service', 'service.id', '=', 'service_item.vendor_service_id')
                    ->join('vendors as vendor', 'vendor.id', '=', 'service.vendor_id')
                    ->whereNull('booking.deleted_at')
                    ->whereDate('booking.'.$config['date_column'], '>=', $dateRange['start']->toDateString())
                    ->whereDate('booking.'.$config['date_column'], '<=', $dateRange['end']->toDateString())
                    ->orderByDesc('booking.created_at')
                    ->limit($limit)
                    ->get([
                        'vendor.name as vendor_name',
                        'booking.status as status',
                        'booking.'.$config['date_column'].' as booked_at',
                        'booking.created_at as created_at',
                    ])
                    ->map(fn (object $row): array => [
                        'vendor_name' => $row->vendor_name,
                        'type' => $type,
                        'type_label' => $config['label'],
                        'booked_at' => CarbonImmutable::parse($row->booked_at),
                        'status' => $row->status,
                        'status_label' => self::BOOKING_STATUSES[$row->status]['label'] ?? ucfirst($row->status),
                        'created_at' => $row->created_at,
                    ]);
            })
            ->sortByDesc('created_at')
            ->take($limit)
            ->map(fn (array $row): array => collect($row)->except('created_at')->all())
            ->values();
    }

    private function resolveEffectiveSubscription(Vendor $vendor, CarbonInterface $now): string
    {
        return match ($vendor->status_subscription) {
            VendorSubscriptionStatus::Active => $vendor->subscription_expired_at?->isBefore($now) ? 'expired' : 'active',
            VendorSubscriptionStatus::Expired => 'expired',
            VendorSubscriptionStatus::Suspended => 'suspended',
            default => 'inactive',
        };
    }

    /**
     * Bentuk payload chart: label, nilai, dan warna dalam urutan yang sama.
     *
     * @param  array<string, int>  $counts
     * @param  array<string, array{label: string, color: string}>  $definitions
     * @return array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>}
     */
    private function buildBreakdown(array $counts, array $definitions): array
    {
        $labels = [];
        $values = [];
        $colors = [];

        foreach ($definitions as $key => $definition) {
            $labels[] = $definition['label'];
            $values[] = $counts[$key] ?? 0;
            $colors[] = $definition['color'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'total' => array_sum($values),
            'has_data' => array_sum($values) > 0,
            'counts' => $counts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMonthWindow(string $preset, CarbonImmutable $monthReference): array
    {
        $previousMonth = $monthReference->subMonthNoOverflow();

        return $this->makeWindow(
            $preset,
            $monthReference->startOfMonth()->startOfDay(),
            $monthReference->endOfMonth()->endOfDay(),
            $previousMonth->startOfMonth()->startOfDay(),
            $previousMonth->endOfMonth()->endOfDay(),
            'vs bulan lalu',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildYearWindow(string $preset, CarbonImmutable $yearReference): array
    {
        $previousYear = $yearReference->subYear();

        return $this->makeWindow(
            $preset,
            $yearReference->startOfYear()->startOfDay(),
            $yearReference->endOfYear()->endOfDay(),
            $previousYear->startOfYear()->startOfDay(),
            $previousYear->endOfYear()->endOfDay(),
            'vs tahun lalu',
        );
    }

    /**
     * Dua belas bulan kalender yang berakhir pada bulan berjalan, dibandingkan dengan dua belas bulan sebelumnya.
     *
     * @return array<string, mixed>
     */
    private function buildTwelveMonthWindow(string $preset, CarbonImmutable $now): array
    {
        $start = $now->startOfMonth()->subMonthsNoOverflow(11)->startOfDay();

        return $this->makeWindow(
            $preset,
            $start,
            $now->endOfMonth()->endOfDay(),
            $start->subMonthsNoOverflow(12)->startOfDay(),
            $start->subDay()->endOfDay(),
            'vs 12 bulan sebelumnya',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRollingWindow(string $preset, CarbonImmutable $start, CarbonImmutable $end, string $comparisonLabel): array
    {
        $daySpan = max(1, (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1);
        $previousEnd = $start->subDay()->endOfDay();

        return $this->makeWindow(
            $preset,
            $start,
            $end,
            $previousEnd->subDays($daySpan - 1)->startOfDay(),
            $previousEnd,
            $comparisonLabel,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCustomWindow(?string $customStart, ?string $customEnd, CarbonImmutable $fallbackNow): array
    {
        try {
            $start = $customStart
                ? CarbonImmutable::parse($customStart)->locale('id')->startOfDay()
                : $fallbackNow->startOfMonth()->startOfDay();
            $end = $customEnd
                ? CarbonImmutable::parse($customEnd)->locale('id')->endOfDay()
                : $fallbackNow->endOfMonth()->endOfDay();
        } catch (\Throwable) {
            return $this->buildMonthWindow('this_month', $fallbackNow);
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return $this->buildRollingWindow('custom', $start, $end, 'vs periode sebelumnya');
    }

    /**
     * @return array<string, mixed>
     */
    private function makeWindow(
        string $preset,
        CarbonImmutable $start,
        CarbonImmutable $end,
        CarbonImmutable $previousStart,
        CarbonImmutable $previousEnd,
        string $comparisonLabel,
    ): array {
        return [
            'preset' => $preset,
            'start' => $start,
            'end' => $end,
            'prev_start' => $previousStart,
            'prev_end' => $previousEnd,
            'range_label' => $this->formatRangeLabel($start, $end),
            'prev_range_label' => $this->formatRangeLabel($previousStart, $previousEnd),
            'comparison_label' => $comparisonLabel,
            'granularity' => $this->resolveGranularity($start, $end),
        ];
    }

    private function resolveGranularity(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $days = (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

        return match (true) {
            $days <= self::MAX_DAILY_RANGE_DAYS => 'daily',
            $days <= self::MAX_WEEKLY_RANGE_DAYS => 'weekly',
            default => 'monthly',
        };
    }

    private function countBuckets(CarbonImmutable $start, CarbonImmutable $end, string $granularity): int
    {
        $days = (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

        return match ($granularity) {
            'daily' => $days,
            'weekly' => (int) ceil($days / 7),
            default => ($end->year - $start->year) * 12 + ($end->month - $start->month) + 1,
        };
    }

    private function resolveBucketIndex(CarbonImmutable $date, CarbonImmutable $start, string $granularity): int
    {
        $dayOffset = (int) $start->startOfDay()->diffInDays($date->startOfDay());

        return match ($granularity) {
            'daily' => $dayOffset,
            'weekly' => intdiv($dayOffset, 7),
            default => ($date->year - $start->year) * 12 + ($date->month - $start->month),
        };
    }

    /**
     * @return list<string>
     */
    private function buildCategories(CarbonImmutable $start, int $bucketCount, string $granularity): array
    {
        $categories = [];

        for ($index = 0; $index < $bucketCount; $index++) {
            if ($granularity === 'monthly') {
                $month = $start->startOfMonth()->addMonthsNoOverflow($index);
                $categories[] = self::MONTH_NAMES[$month->month - 1].' '.$month->format('y');

                continue;
            }

            $day = $start->addDays($granularity === 'weekly' ? $index * 7 : $index);
            $categories[] = $day->day.' '.self::MONTH_NAMES[$day->month - 1];
        }

        return $categories;
    }

    private function formatRangeLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $startLabel = $start->day.' '.self::MONTH_NAMES[$start->month - 1];
        $endLabel = $end->day.' '.self::MONTH_NAMES[$end->month - 1].' '.$end->year;

        return match (true) {
            $start->isSameDay($end) => $endLabel,
            $start->year === $end->year && $start->month === $end->month => $start->day.'-'.$endLabel,
            $start->year === $end->year => $startLabel.' - '.$endLabel,
            default => $startLabel.' '.$start->year.' - '.$endLabel,
        };
    }
}
