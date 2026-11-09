<?php

namespace App\Actions\Admin\Dashboard;

use App\Models\Vendor;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetOwnerDashboardAnalyticsAction
{
    private const CATEGORY_PET_HOTEL = 'pet_hotel';

    private const CATEGORY_CLINIC = 'clinic';

    private const CATEGORY_GROOMING = 'grooming';

    /**
     * Resolve start/end bounds and comparison window for a selected filter preset.
     *
     * @return array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     range_label: string,
     *     comparison_label: string
     * }
     */
    public function resolveDateRange(
        string $preset = 'this_month',
        ?string $customStart = null,
        ?string $customEnd = null,
        ?CarbonImmutable $referenceNow = null,
    ): array {
        $now = ($referenceNow ?? CarbonImmutable::now())->locale('id');

        return match ($preset) {
            'last_month' => $this->buildMonthWindow($preset, $now->subMonthNoOverflow()),
            'last_7_days' => $this->buildRollingDaysWindow($preset, $now->subDays(6)->startOfDay(), $now->endOfDay(), 'vs 7 hari sebelumnya'),
            'last_30_days' => $this->buildRollingDaysWindow($preset, $now->subDays(29)->startOfDay(), $now->endOfDay(), 'vs 30 hari sebelumnya'),
            'this_year' => $this->buildYearWindow($preset, $now),
            'custom' => $this->buildCustomWindow($customStart, $customEnd, $now),
            default => $this->buildMonthWindow('this_month', $now),
        };
    }

    /**
     * Gather KPI summary cards, trend series, service contribution, and popular services.
     *
     * @param  array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     range_label: string,
     *     comparison_label: string
     * }  $dateRange
     * @return array{
     *     summary: array{
     *         total_revenue: array{value: int, formatted: string, change: array{has_previous: bool, direction: string, label: string, comparison_label: string, tone: string}},
     *         completed_orders: array{value: int, formatted: string, change: array{has_previous: bool, direction: string, label: string, comparison_label: string, tone: string}},
     *         average_transaction: array{value: int, formatted: string, change: array{has_previous: bool, direction: string, label: string, comparison_label: string, tone: string}},
     *         cancellation_rate: array{value: float, formatted: string, cancelled_count: int, total_bookings: int, change: array{has_previous: bool, direction: string, label: string, comparison_label: string, tone: string}}
     *     },
     *     trend: array{
     *         granularity: string,
     *         subtitle: string,
     *         categories: list<string>,
     *         revenues: list<int>,
     *         orders: list<int>,
     *         has_data: bool
     *     },
     *     contribution: array{
     *         total_revenue: int,
     *         total_formatted: string,
     *         has_data: bool,
     *         top_contributor_note: string,
     *         items: list<array{key: string, label: string, color: string, revenue: int, formatted: string, percentage: float, percentage_label: string, completed_orders: int}>
     *     },
     *     popular_services: list<array{rank: string, name: string, category_label: string, completed_orders: int, revenue: int, revenue_formatted: string}>
     * }
     */
    public function getOverviewAnalytics(int $vendorId, array $dateRange, string $trendGranularity = 'weekly'): array
    {
        $currentStats = $this->aggregateSummaryPeriod($vendorId, $dateRange['start'], $dateRange['end']);
        $previousStats = $this->aggregateSummaryPeriod($vendorId, $dateRange['prev_start'], $dateRange['prev_end']);

        $comparisonLabel = $dateRange['comparison_label'];

        $summary = [
            'total_revenue' => [
                'value' => $currentStats['revenue'],
                'formatted' => $this->formatRupiah($currentStats['revenue']),
                'change' => $this->calculatePercentageDelta(
                    $currentStats['revenue'],
                    $previousStats['revenue'],
                    $previousStats['total_bookings'] > 0,
                    $comparisonLabel,
                    true,
                ),
            ],
            'completed_orders' => [
                'value' => $currentStats['completed_count'],
                'formatted' => number_format($currentStats['completed_count'], 0, ',', '.'),
                'change' => $this->calculatePercentageDelta(
                    $currentStats['completed_count'],
                    $previousStats['completed_count'],
                    $previousStats['total_bookings'] > 0,
                    $comparisonLabel,
                    true,
                ),
            ],
            'average_transaction' => [
                'value' => $currentStats['average_transaction'],
                'formatted' => $this->formatRupiah($currentStats['average_transaction']),
                'change' => $this->calculatePercentageDelta(
                    $currentStats['average_transaction'],
                    $previousStats['average_transaction'],
                    $previousStats['completed_count'] > 0,
                    $comparisonLabel,
                    true,
                ),
            ],
            'cancellation_rate' => [
                'value' => $currentStats['cancellation_rate'],
                'formatted' => $this->formatDecimal($currentStats['cancellation_rate']).'%',
                'cancelled_count' => $currentStats['cancelled_count'],
                'total_bookings' => $currentStats['total_bookings'],
                'change' => $this->calculatePointDelta(
                    $currentStats['cancellation_rate'],
                    $previousStats['cancellation_rate'],
                    $previousStats['total_bookings'] > 0 && $currentStats['total_bookings'] > 0,
                    $comparisonLabel,
                ),
            ],
        ];

        return [
            'summary' => $summary,
            'trend' => $this->buildTrendSeries($vendorId, $dateRange['start'], $dateRange['end'], $trendGranularity),
            'contribution' => $this->buildServiceContribution($vendorId, $dateRange['start'], $dateRange['end']),
            'popular_services' => $this->getPopularServices($vendorId, $dateRange['start'], $dateRange['end']),
        ];
    }

    /**
     * Paginate recent transactions across all vendor service categories.
     *
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $dateRange
     */
    public function getRecentTransactions(
        int $vendorId,
        array $dateRange,
        string $search = '',
        string $paymentFilter = 'all',
        string $bookingStatusFilter = 'all',
        int $perPage = 5,
    ): LengthAwarePaginator {
        $paginator = $this->buildFilteredTransactionsQuery(
            $vendorId,
            $dateRange['start'],
            $dateRange['end'],
            $search,
            $paymentFilter,
            $bookingStatusFilter,
        )
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->orderByDesc('booking_id')
            ->paginate($perPage);

        $paginator->through(fn (object $row): array => $this->transformTransactionRow($row));

        return $paginator;
    }

    /**
     * Collect all matching transactions in the active filter for CSV export.
     *
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}  $dateRange
     * @return Collection<int, array<string, mixed>>
     */
    public function getExportTransactions(
        int $vendorId,
        array $dateRange,
        string $search = '',
        string $paymentFilter = 'all',
        string $bookingStatusFilter = 'all',
    ): Collection {
        return $this->buildFilteredTransactionsQuery(
            $vendorId,
            $dateRange['start'],
            $dateRange['end'],
            $search,
            $paymentFilter,
            $bookingStatusFilter,
        )
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->orderByDesc('booking_id')
            ->get()
            ->map(fn (object $row): array => $this->transformTransactionRow($row));
    }

    /**
     * Determine current operational status of the active vendor today.
     *
     * @return array{is_open_today: bool, status_text: string, schedule_text: string}
     */
    public function getVendorOperationalInfo(?Vendor $vendor): array
    {
        if (! $vendor) {
            return [
                'is_open_today' => false,
                'status_text' => 'Mitra Tidak Aktif',
                'schedule_text' => '-',
            ];
        }

        $todayIndex = CarbonImmutable::now()->dayOfWeekIso - 1;
        $todayHour = $vendor->vendorOperatingHours()
            ->where('day_of_week', $todayIndex)
            ->first();

        if (! $todayHour || $todayHour->is_closed || ! $todayHour->open_time || ! $todayHour->close_time) {
            return [
                'is_open_today' => false,
                'status_text' => 'Tutup Hari Ini',
                'schedule_text' => 'Jadwal libur operasional',
            ];
        }

        $openFormatted = substr((string) $todayHour->open_time, 0, 5);
        $closeFormatted = substr((string) $todayHour->close_time, 0, 5);

        return [
            'is_open_today' => true,
            'status_text' => 'Mitra Aktif',
            'schedule_text' => "Jam buka hari ini {$openFormatted}–{$closeFormatted}",
        ];
    }

    /**
     * Format an integer Rupiah value with Indonesian thousands separator.
     */
    public function formatRupiah(int|float $amount): string
    {
        return 'Rp '.number_format((int) round($amount), 0, ',', '.');
    }

    /**
     * @return array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     range_label: string,
     *     comparison_label: string
     * }
     */
    private function buildMonthWindow(string $preset, CarbonImmutable $monthReference): array
    {
        $start = $monthReference->startOfMonth()->startOfDay();
        $end = $monthReference->endOfMonth()->endOfDay();
        $prevMonth = $monthReference->subMonthNoOverflow();

        return [
            'preset' => $preset,
            'start' => $start,
            'end' => $end,
            'prev_start' => $prevMonth->startOfMonth()->startOfDay(),
            'prev_end' => $prevMonth->endOfMonth()->endOfDay(),
            'range_label' => $this->formatDateRangeLabel($start, $end),
            'comparison_label' => 'vs bulan lalu',
        ];
    }

    /**
     * @return array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     range_label: string,
     *     comparison_label: string
     * }
     */
    private function buildYearWindow(string $preset, CarbonImmutable $yearReference): array
    {
        $start = $yearReference->startOfYear()->startOfDay();
        $end = $yearReference->endOfYear()->endOfDay();
        $prevYear = $yearReference->subYear();

        return [
            'preset' => $preset,
            'start' => $start,
            'end' => $end,
            'prev_start' => $prevYear->startOfYear()->startOfDay(),
            'prev_end' => $prevYear->endOfYear()->endOfDay(),
            'range_label' => $this->formatDateRangeLabel($start, $end),
            'comparison_label' => 'vs tahun lalu',
        ];
    }

    /**
     * @return array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     range_label: string,
     *     comparison_label: string
     * }
     */
    private function buildRollingDaysWindow(
        string $preset,
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $comparisonLabel,
    ): array {
        $daySpan = max(1, (int) $start->startOfDay()->diffInDays($end->startOfDay()) + 1);
        $prevEnd = $start->subDay()->endOfDay();
        $prevStart = $prevEnd->subDays($daySpan - 1)->startOfDay();

        return [
            'preset' => $preset,
            'start' => $start,
            'end' => $end,
            'prev_start' => $prevStart,
            'prev_end' => $prevEnd,
            'range_label' => $this->formatDateRangeLabel($start, $end),
            'comparison_label' => $comparisonLabel,
        ];
    }

    /**
     * @return array{
     *     preset: string,
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     prev_start: CarbonImmutable,
     *     prev_end: CarbonImmutable,
     *     range_label: string,
     *     comparison_label: string
     * }
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

        return $this->buildRollingDaysWindow('custom', $start, $end, 'vs periode sebelumnya');
    }

    private function formatDateRangeLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $startLocale = $start->locale('id');
        $endLocale = $end->locale('id');

        if ($startLocale->isSameDay($endLocale)) {
            return $startLocale->translatedFormat('j M Y');
        }

        if ($startLocale->year === $endLocale->year && $startLocale->month === $endLocale->month) {
            return $startLocale->format('j').'–'.$endLocale->translatedFormat('j M Y');
        }

        if ($startLocale->year === $endLocale->year) {
            return $startLocale->translatedFormat('j M').' – '.$endLocale->translatedFormat('j M Y');
        }

        return $startLocale->translatedFormat('j M Y').' – '.$endLocale->translatedFormat('j M Y');
    }

    /**
     * Build unified ANSI-SQL query across Clinic, Grooming, and Pet Hotel bookings for a vendor.
     */
    private function buildUnifiedBookingsQuery(int $vendorId): Builder
    {
        $clinicQuery = DB::table('bookings_clinic')
            ->join('vendor_service_items', 'vendor_service_items.id', '=', 'bookings_clinic.vendor_service_item_id')
            ->join('vendor_services', 'vendor_services.id', '=', 'vendor_service_items.vendor_service_id')
            ->join('category_services', 'category_services.id', '=', 'vendor_services.category_service_id')
            ->leftJoin('user_clients', 'user_clients.id', '=', 'bookings_clinic.user_client_id')
            ->leftJoin('users', 'users.id', '=', 'user_clients.user_id')
            ->whereNull('bookings_clinic.deleted_at')
            ->where('vendor_services.vendor_id', $vendorId)
            ->select([
                'bookings_clinic.id as booking_id',
                DB::raw("'clinic' as service_type"),
                DB::raw("'CLN' as type_prefix"),
                'bookings_clinic.booking_date as transaction_date',
                DB::raw($this->typedNull('timestamp').' as end_date'),
                'bookings_clinic.time_slot as time_slot',
                'bookings_clinic.status as status',
                'bookings_clinic.created_at as created_at',
                'vendor_service_items.id as service_item_id',
                'vendor_service_items.name as service_item_name',
                'vendor_service_items.price as price',
                'category_services.name as category_name',
                'users.name as customer_name',
            ]);

        $groomingQuery = DB::table('bookings_grooming')
            ->join('vendor_service_items', 'vendor_service_items.id', '=', 'bookings_grooming.vendor_service_item_id')
            ->join('vendor_services', 'vendor_services.id', '=', 'vendor_service_items.vendor_service_id')
            ->join('category_services', 'category_services.id', '=', 'vendor_services.category_service_id')
            ->leftJoin('user_clients', 'user_clients.id', '=', 'bookings_grooming.user_client_id')
            ->leftJoin('users', 'users.id', '=', 'user_clients.user_id')
            ->whereNull('bookings_grooming.deleted_at')
            ->where('vendor_services.vendor_id', $vendorId)
            ->select([
                'bookings_grooming.id as booking_id',
                DB::raw("'grooming' as service_type"),
                DB::raw("'GRM' as type_prefix"),
                'bookings_grooming.booking_date as transaction_date',
                DB::raw($this->typedNull('timestamp').' as end_date'),
                'bookings_grooming.time_slot as time_slot',
                'bookings_grooming.status as status',
                'bookings_grooming.created_at as created_at',
                'vendor_service_items.id as service_item_id',
                'vendor_service_items.name as service_item_name',
                'vendor_service_items.price as price',
                'category_services.name as category_name',
                'users.name as customer_name',
            ]);

        $petHotelQuery = DB::table('bookings_pet_hotel')
            ->join('vendor_service_items', 'vendor_service_items.id', '=', 'bookings_pet_hotel.vendor_service_item_id')
            ->join('vendor_services', 'vendor_services.id', '=', 'vendor_service_items.vendor_service_id')
            ->join('category_services', 'category_services.id', '=', 'vendor_services.category_service_id')
            ->leftJoin('user_clients', 'user_clients.id', '=', 'bookings_pet_hotel.user_client_id')
            ->leftJoin('users', 'users.id', '=', 'user_clients.user_id')
            ->whereNull('bookings_pet_hotel.deleted_at')
            ->where('vendor_services.vendor_id', $vendorId)
            ->select([
                'bookings_pet_hotel.id as booking_id',
                DB::raw("'pet_hotel' as service_type"),
                DB::raw("'HTL' as type_prefix"),
                'bookings_pet_hotel.start_date as transaction_date',
                'bookings_pet_hotel.end_date as end_date',
                DB::raw($this->typedNull('integer').' as time_slot'),
                'bookings_pet_hotel.status as status',
                'bookings_pet_hotel.created_at as created_at',
                'vendor_service_items.id as service_item_id',
                'vendor_service_items.name as service_item_name',
                'vendor_service_items.price as price',
                'category_services.name as category_name',
                'users.name as customer_name',
            ]);

        $unionQuery = $clinicQuery->unionAll($groomingQuery)->unionAll($petHotelQuery);

        return DB::query()->fromSub($unionQuery, 'unified_bookings');
    }

    /**
     * @return array{
     *     revenue: int,
     *     completed_count: int,
     *     average_transaction: int,
     *     cancelled_count: int,
     *     total_bookings: int,
     *     cancellation_rate: float
     * }
     */
    private function aggregateSummaryPeriod(int $vendorId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $row = $this->buildUnifiedBookingsQuery($vendorId)
            ->whereBetween('transaction_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count,
                COUNT(*) as total_bookings
            ")
            ->first();

        $revenue = (int) ($row->total_revenue ?? 0);
        $completedCount = (int) ($row->completed_count ?? 0);
        $cancelledCount = (int) ($row->cancelled_count ?? 0);
        $totalBookings = (int) ($row->total_bookings ?? 0);

        $averageTransaction = $completedCount > 0
            ? (int) round($revenue / $completedCount)
            : 0;

        $cancellationRate = $totalBookings > 0
            ? round(($cancelledCount / $totalBookings) * 100, 1)
            : 0.0;

        return [
            'revenue' => $revenue,
            'completed_count' => $completedCount,
            'average_transaction' => $averageTransaction,
            'cancelled_count' => $cancelledCount,
            'total_bookings' => $totalBookings,
            'cancellation_rate' => $cancellationRate,
        ];
    }

    /**
     * @return array{has_previous: bool, direction: string, label: string, comparison_label: string, tone: string}
     */
    private function calculatePercentageDelta(
        int|float $current,
        int|float $previous,
        bool $hasPreviousActivity,
        string $comparisonLabel,
        bool $higherIsBetter,
    ): array {
        if (! $hasPreviousActivity && $previous <= 0) {
            return [
                'has_previous' => false,
                'direction' => 'neutral',
                'label' => $current > 0 ? 'Baru periode ini' : 'Belum ada data pembanding',
                'comparison_label' => '',
                'tone' => 'neutral',
            ];
        }

        if ($previous <= 0) {
            if ($current <= 0) {
                return [
                    'has_previous' => true,
                    'direction' => 'flat',
                    'label' => '0%',
                    'comparison_label' => $comparisonLabel,
                    'tone' => 'neutral',
                ];
            }

            return [
                'has_previous' => true,
                'direction' => 'up',
                'label' => '+100%',
                'comparison_label' => $comparisonLabel,
                'tone' => $higherIsBetter ? 'positive' : 'negative',
            ];
        }

        $delta = round((($current - $previous) / $previous) * 100, 1);

        if (abs($delta) < 0.05) {
            return [
                'has_previous' => true,
                'direction' => 'flat',
                'label' => '0%',
                'comparison_label' => $comparisonLabel,
                'tone' => 'neutral',
            ];
        }

        $isUp = $delta > 0;
        $tone = ($isUp === $higherIsBetter) ? 'positive' : 'negative';

        return [
            'has_previous' => true,
            'direction' => $isUp ? 'up' : 'down',
            'label' => $this->formatDecimal(abs($delta)).'%',
            'comparison_label' => $comparisonLabel,
            'tone' => $tone,
        ];
    }

    /**
     * @return array{has_previous: bool, direction: string, label: string, comparison_label: string, tone: string}
     */
    private function calculatePointDelta(
        float $currentRate,
        float $previousRate,
        bool $hasComparableData,
        string $comparisonLabel,
    ): array {
        if (! $hasComparableData) {
            return [
                'has_previous' => false,
                'direction' => 'neutral',
                'label' => 'Belum ada data pembanding',
                'comparison_label' => '',
                'tone' => 'neutral',
            ];
        }

        $pointDiff = round($currentRate - $previousRate, 1);

        if (abs($pointDiff) < 0.05) {
            return [
                'has_previous' => true,
                'direction' => 'flat',
                'label' => '0 poin',
                'comparison_label' => $comparisonLabel,
                'tone' => 'neutral',
            ];
        }

        $isUp = $pointDiff > 0;

        return [
            'has_previous' => true,
            'direction' => $isUp ? 'up' : 'down',
            'label' => $this->formatDecimal(abs($pointDiff)).' poin',
            'comparison_label' => $comparisonLabel,
            'tone' => $isUp ? 'negative' : 'positive',
        ];
    }

    /**
     * Build revenue & completed order trend buckets for ApexCharts mixed chart.
     *
     * @return array{
     *     granularity: string,
     *     subtitle: string,
     *     categories: list<string>,
     *     revenues: list<int>,
     *     orders: list<int>,
     *     has_data: bool
     * }
     */
    private function buildTrendSeries(
        int $vendorId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $granularity,
    ): array {
        $normalizedGranularity = in_array($granularity, ['daily', 'weekly', 'monthly'], true)
            ? $granularity
            : 'weekly';

        $queryStart = $start;
        $queryEnd = $end;

        if ($normalizedGranularity === 'monthly' && $start->format('Y-m') === $end->format('Y-m')) {
            $queryStart = $end->subMonthsNoOverflow(5)->startOfMonth()->startOfDay();
        }

        if ($normalizedGranularity === 'daily' && $start->diffInDays($end) > 60) {
            $queryStart = $end->subDays(29)->startOfDay();
        }

        $completedRows = $this->buildUnifiedBookingsQuery($vendorId)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$queryStart->toDateTimeString(), $queryEnd->toDateTimeString()])
            ->select(['transaction_date', 'price'])
            ->get();

        if ($normalizedGranularity === 'daily') {
            return $this->bucketByDay($completedRows, $queryStart, $queryEnd);
        }

        if ($normalizedGranularity === 'monthly') {
            return $this->bucketByMonth($completedRows, $queryStart, $queryEnd);
        }

        return $this->bucketByWeek($completedRows, $queryStart, $queryEnd);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{granularity: string, subtitle: string, categories: list<string>, revenues: list<int>, orders: list<int>, has_data: bool}
     */
    private function bucketByDay(Collection $rows, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $buckets = [];
        $cursor = $start->startOfDay();
        $endDay = $end->startOfDay();
        $totalDays = (int) $cursor->diffInDays($endDay) + 1;

        while ($cursor->lte($endDay)) {
            $key = $cursor->toDateString();
            $buckets[$key] = [
                'label' => $totalDays <= 10
                    ? $cursor->locale('id')->translatedFormat('d M')
                    : $cursor->format('j').' '.$cursor->locale('id')->translatedFormat('M'),
                'revenue' => 0,
                'orders' => 0,
            ];
            $cursor = $cursor->addDay();
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->transaction_date)->toDateString();
            if (isset($buckets[$key])) {
                $buckets[$key]['revenue'] += (int) $row->price;
                $buckets[$key]['orders']++;
            }
        }

        return $this->finalizeTrendBuckets(
            'daily',
            'Tren harian • '.$this->formatDateRangeLabel($start, $end),
            array_values($buckets),
        );
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{granularity: string, subtitle: string, categories: list<string>, revenues: list<int>, orders: list<int>, has_data: bool}
     */
    private function bucketByWeek(Collection $rows, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $sameMonth = $start->format('Y-m') === $end->format('Y-m');
        $buckets = [];

        if ($sameMonth) {
            for ($week = 1; $week <= 4; $week++) {
                $buckets[$week] = [
                    'label' => 'Minggu '.$week,
                    'revenue' => 0,
                    'orders' => 0,
                ];
            }

            foreach ($rows as $row) {
                $date = Carbon::parse($row->transaction_date);
                $weekIndex = min(4, max(1, (int) ceil($date->day / 7)));
                $buckets[$weekIndex]['revenue'] += (int) $row->price;
                $buckets[$weekIndex]['orders']++;
            }

            $subtitle = $start->locale('id')->translatedFormat('F Y');
        } else {
            $cursor = $start->startOfDay();
            $endDay = $end->endOfDay();
            $index = 1;

            while ($cursor->lte($endDay)) {
                $chunkEnd = $cursor->addDays(6)->endOfDay();
                if ($chunkEnd->gt($endDay)) {
                    $chunkEnd = $endDay;
                }

                $buckets[$index] = [
                    'start' => $cursor,
                    'end' => $chunkEnd,
                    'label' => $cursor->locale('id')->translatedFormat('j M').'–'.$chunkEnd->locale('id')->translatedFormat('j M'),
                    'revenue' => 0,
                    'orders' => 0,
                ];

                $cursor = $chunkEnd->addSecond()->startOfDay();
                $index++;
            }

            foreach ($rows as $row) {
                $date = CarbonImmutable::parse($row->transaction_date);
                foreach ($buckets as $bucketKey => $bucket) {
                    if ($date->betweenIncluded($bucket['start'], $bucket['end'])) {
                        $buckets[$bucketKey]['revenue'] += (int) $row->price;
                        $buckets[$bucketKey]['orders']++;
                        break;
                    }
                }
            }

            $subtitle = 'Tren mingguan • '.$this->formatDateRangeLabel($start, $end);
        }

        return $this->finalizeTrendBuckets('weekly', $subtitle, array_values($buckets));
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{granularity: string, subtitle: string, categories: list<string>, revenues: list<int>, orders: list<int>, has_data: bool}
     */
    private function bucketByMonth(Collection $rows, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $buckets = [];
        $cursor = $start->startOfMonth();
        $endMonth = $end->startOfMonth();
        $spansMultipleYears = $cursor->year !== $endMonth->year;

        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $buckets[$key] = [
                'label' => $spansMultipleYears
                    ? $cursor->locale('id')->translatedFormat('M Y')
                    : $cursor->locale('id')->translatedFormat('M'),
                'revenue' => 0,
                'orders' => 0,
            ];
            $cursor = $cursor->addMonthNoOverflow();
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->transaction_date)->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['revenue'] += (int) $row->price;
                $buckets[$key]['orders']++;
            }
        }

        $subtitle = $start->locale('id')->translatedFormat('M Y').' – '.$end->locale('id')->translatedFormat('M Y');

        return $this->finalizeTrendBuckets('monthly', $subtitle, array_values($buckets));
    }

    /**
     * @param  list<array{label: string, revenue: int, orders: int}>  $bucketList
     * @return array{granularity: string, subtitle: string, categories: list<string>, revenues: list<int>, orders: list<int>, has_data: bool}
     */
    private function finalizeTrendBuckets(string $granularity, string $subtitle, array $bucketList): array
    {
        $categories = [];
        $revenues = [];
        $orders = [];
        $hasData = false;

        foreach ($bucketList as $bucket) {
            $categories[] = $bucket['label'];
            $revenues[] = $bucket['revenue'];
            $orders[] = $bucket['orders'];
            if ($bucket['revenue'] > 0 || $bucket['orders'] > 0) {
                $hasData = true;
            }
        }

        return [
            'granularity' => $granularity,
            'subtitle' => $subtitle,
            'categories' => $categories,
            'revenues' => $revenues,
            'orders' => $orders,
            'has_data' => $hasData,
        ];
    }

    /**
     * Build donut chart breakdown by PawPaw service category.
     *
     * @return array{
     *     total_revenue: int,
     *     total_formatted: string,
     *     has_data: bool,
     *     top_contributor_note: string,
     *     items: list<array{key: string, label: string, color: string, revenue: int, formatted: string, percentage: float, percentage_label: string, completed_orders: int}>
     * }
     */
    private function buildServiceContribution(int $vendorId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $definitions = [
            self::CATEGORY_PET_HOTEL => [
                'key' => self::CATEGORY_PET_HOTEL,
                'label' => 'Pet Hotel',
                'color' => '#1B5E43',
                'revenue' => 0,
                'completed_orders' => 0,
            ],
            self::CATEGORY_CLINIC => [
                'key' => self::CATEGORY_CLINIC,
                'label' => 'Klinik Hewan',
                'color' => '#EE6D52',
                'revenue' => 0,
                'completed_orders' => 0,
            ],
            self::CATEGORY_GROOMING => [
                'key' => self::CATEGORY_GROOMING,
                'label' => 'Grooming',
                'color' => '#5C7C6B',
                'revenue' => 0,
                'completed_orders' => 0,
            ],
        ];

        $rows = $this->buildUnifiedBookingsQuery($vendorId)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->selectRaw('service_type, COALESCE(SUM(price), 0) as total_revenue, COUNT(*) as completed_orders')
            ->groupBy('service_type')
            ->get();

        $totalRevenue = 0;

        foreach ($rows as $row) {
            $type = (string) $row->service_type;
            if (isset($definitions[$type])) {
                $revenue = (int) $row->total_revenue;
                $definitions[$type]['revenue'] = $revenue;
                $definitions[$type]['completed_orders'] = (int) $row->completed_orders;
                $totalRevenue += $revenue;
            }
        }

        $items = [];
        $topItem = null;

        foreach ($definitions as $definition) {
            $percentage = $totalRevenue > 0
                ? round(($definition['revenue'] / $totalRevenue) * 100, 1)
                : 0.0;

            $item = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'color' => $definition['color'],
                'revenue' => $definition['revenue'],
                'formatted' => $this->formatRupiah($definition['revenue']),
                'percentage' => $percentage,
                'percentage_label' => $this->formatDecimal($percentage).'%',
                'completed_orders' => $definition['completed_orders'],
            ];

            if ($item['revenue'] > 0 && ($topItem === null || $item['revenue'] > $topItem['revenue'])) {
                $topItem = $item;
            }

            $items[] = $item;
        }

        $note = $topItem !== null
            ? "{$topItem['label']} menjadi kontributor terbesar ({$topItem['percentage_label']})."
            : 'Belum ada transaksi selesai untuk dihitung kontribusinya pada periode ini.';

        return [
            'total_revenue' => $totalRevenue,
            'total_formatted' => $this->formatRupiah($totalRevenue),
            'has_data' => $totalRevenue > 0,
            'top_contributor_note' => $note,
            'items' => $items,
        ];
    }

    /**
     * Fetch top 5 service items ordered by completed booking count.
     *
     * @return list<array{rank: string, name: string, category_label: string, completed_orders: int, revenue: int, revenue_formatted: string}>
     */
    private function getPopularServices(int $vendorId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $this->buildUnifiedBookingsQuery($vendorId)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->selectRaw('
                service_item_id,
                service_item_name,
                service_type,
                COUNT(*) as completed_orders,
                COALESCE(SUM(price), 0) as total_revenue
            ')
            ->groupBy('service_item_id', 'service_item_name', 'service_type')
            ->orderByDesc('completed_orders')
            ->orderByDesc('total_revenue')
            ->orderBy('service_item_name')
            ->limit(5)
            ->get();

        $popular = [];

        foreach ($rows->values() as $index => $row) {
            $popular[] = [
                'rank' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'name' => (string) $row->service_item_name,
                'category_label' => $this->mapCategoryDisplayLabel((string) $row->service_type),
                'completed_orders' => (int) $row->completed_orders,
                'revenue' => (int) $row->total_revenue,
                'revenue_formatted' => $this->formatRupiah((int) $row->total_revenue),
            ];
        }

        return $popular;
    }

    private function buildFilteredTransactionsQuery(
        int $vendorId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $search,
        string $paymentFilter,
        string $bookingStatusFilter,
    ): Builder {
        $query = $this->buildUnifiedBookingsQuery($vendorId)
            ->whereBetween('transaction_date', [$start->toDateTimeString(), $end->toDateTimeString()]);

        if ($paymentFilter !== '' && $paymentFilter !== 'all') {
            $statusesForPayment = match ($paymentFilter) {
                'paid' => ['completed'],
                'unpaid' => ['pending', 'confirmed', 'rejected'],
                'refunded' => ['cancelled'],
                default => null,
            };

            if ($statusesForPayment !== null) {
                $query->whereIn('status', $statusesForPayment);
            }
        }

        if ($bookingStatusFilter !== '' && $bookingStatusFilter !== 'all') {
            $statusesForBooking = match ($bookingStatusFilter) {
                'completed' => ['completed'],
                'processing' => ['pending', 'confirmed'],
                'cancelled' => ['cancelled', 'rejected'],
                default => null,
            };

            if ($statusesForBooking !== null) {
                $query->whereIn('status', $statusesForBooking);
            }
        }

        $trimmedSearch = trim($search);

        if ($trimmedSearch !== '') {
            $escapedLike = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($trimmedSearch)).'%';
            $parsedCode = $this->parseTransactionCodeSearch($trimmedSearch);

            $query->where(function (Builder $subQuery) use ($escapedLike, $parsedCode): void {
                $subQuery->whereRaw('LOWER(COALESCE(customer_name, \'\')) LIKE ?', [$escapedLike])
                    ->orWhereRaw('LOWER(service_item_name) LIKE ?', [$escapedLike]);

                if ($parsedCode['prefix'] !== null && $parsedCode['id'] !== null) {
                    $subQuery->orWhere(function (Builder $codeQuery) use ($parsedCode): void {
                        $codeQuery->where('type_prefix', $parsedCode['prefix'])
                            ->where('booking_id', $parsedCode['id']);
                    });
                } elseif ($parsedCode['prefix'] !== null) {
                    $subQuery->orWhere('type_prefix', $parsedCode['prefix']);
                } elseif ($parsedCode['id'] !== null) {
                    $subQuery->orWhere('booking_id', $parsedCode['id']);
                }
            });
        }

        return $query;
    }

    /**
     * Parse human-formatted transaction code searches such as "#PP-CLN-0012", "CLN-12", or "0012".
     *
     * @return array{prefix: ?string, id: ?int}
     */
    private function parseTransactionCodeSearch(string $keyword): array
    {
        $normalized = strtoupper(trim($keyword));

        if (preg_match('/^#?(?:PP-)?(CLN|GRM|HTL)-?0*(\d+)$/', $normalized, $matches)) {
            return [
                'prefix' => $matches[1],
                'id' => (int) $matches[2],
            ];
        }

        if (preg_match('/^#?(?:PP-)?(CLN|GRM|HTL)-?$/', $normalized, $matches)) {
            return [
                'prefix' => $matches[1],
                'id' => null,
            ];
        }

        if (preg_match('/^#?0*(\d+)$/', $normalized, $matches)) {
            return [
                'prefix' => null,
                'id' => (int) $matches[1],
            ];
        }

        return [
            'prefix' => null,
            'id' => null,
        ];
    }

    /**
     * Transform a raw unified SQL booking row into UI-ready array shape.
     *
     * @return array{
     *     booking_id: int,
     *     transaction_code: string,
     *     service_type: string,
     *     category_label: string,
     *     booking_route: string,
     *     date_formatted: string,
     *     schedule_detail: string,
     *     service_name: string,
     *     customer_name: string,
     *     amount: int,
     *     amount_formatted: string,
     *     payment_key: string,
     *     payment_label: string,
     *     booking_status_key: string,
     *     booking_status_label: string,
     *     raw_status: string,
     *     raw_status_note: string
     * }
     */
    private function transformTransactionRow(object $row): array
    {
        $bookingId = (int) $row->booking_id;
        $prefix = (string) $row->type_prefix;
        $serviceType = (string) $row->service_type;
        $rawStatus = (string) $row->status;
        $amount = (int) $row->price;

        $transactionDate = CarbonImmutable::parse($row->transaction_date)->locale('id');
        $dateFormatted = $transactionDate->translatedFormat('d M Y');

        if ($serviceType === self::CATEGORY_PET_HOTEL && $row->end_date !== null) {
            $endDate = CarbonImmutable::parse($row->end_date)->locale('id');
            $nights = max(1, (int) abs($transactionDate->startOfDay()->diffInDays($endDate->startOfDay())));
            $scheduleDetail = $endDate->translatedFormat('d M Y')." ({$nights} malam)";
        } elseif ($row->time_slot !== null) {
            $slotMinutes = (int) $row->time_slot;
            $scheduleDetail = sprintf('%02d:%02d WIB', intdiv($slotMinutes, 60), $slotMinutes % 60);
        } else {
            $scheduleDetail = '';
        }

        [$paymentKey, $paymentLabel] = match ($rawStatus) {
            'completed' => ['paid', 'Lunas'],
            'cancelled' => ['refunded', 'Dikembalikan'],
            default => ['unpaid', 'Belum lunas'],
        };

        [$bookingStatusKey, $bookingStatusLabel, $rawStatusNote] = match ($rawStatus) {
            'completed' => ['completed', 'Selesai', 'Layanan selesai'],
            'pending' => ['processing', 'Diproses', 'Menunggu konfirmasi'],
            'confirmed' => ['processing', 'Diproses', 'Terkonfirmasi'],
            'cancelled' => ['cancelled', 'Dibatalkan', 'Dibatalkan setelah konfirmasi'],
            'rejected' => ['cancelled', 'Dibatalkan', 'Reservasi ditolak'],
            default => ['processing', ucfirst($rawStatus), ''],
        };

        $bookingRoute = match ($serviceType) {
            self::CATEGORY_PET_HOTEL => 'vendor.admin.booking-pet-hotel',
            self::CATEGORY_GROOMING => 'vendor.admin.booking-grooming',
            default => 'vendor.admin.booking-clinic',
        };

        return [
            'booking_id' => $bookingId,
            'transaction_code' => sprintf('#PP-%s-%04d', $prefix, $bookingId),
            'service_type' => $serviceType,
            'category_label' => $this->mapCategoryDisplayLabel($serviceType),
            'booking_route' => $bookingRoute,
            'date_formatted' => $dateFormatted,
            'schedule_detail' => $scheduleDetail,
            'service_name' => (string) $row->service_item_name,
            'customer_name' => (string) ($row->customer_name ?? 'Pelanggan'),
            'amount' => $amount,
            'amount_formatted' => $this->formatRupiah($amount),
            'payment_key' => $paymentKey,
            'payment_label' => $paymentLabel,
            'booking_status_key' => $bookingStatusKey,
            'booking_status_label' => $bookingStatusLabel,
            'raw_status' => $rawStatus,
            'raw_status_note' => $rawStatusNote,
        ];
    }

    /**
     * Build a NULL literal cast to a concrete column type so UNION ALL branches
     * stay type-compatible on strict engines such as PostgreSQL.
     */
    private function typedNull(string $type): string
    {
        $driver = DB::connection()->getDriverName();

        $sqlType = match ([$driver, $type]) {
            ['pgsql', 'timestamp'] => 'TIMESTAMP',
            ['pgsql', 'integer'] => 'INTEGER',
            ['mysql', 'timestamp'], ['mariadb', 'timestamp'] => 'DATETIME',
            ['mysql', 'integer'], ['mariadb', 'integer'] => 'SIGNED',
            default => null,
        };

        return $sqlType === null ? 'NULL' : "CAST(NULL AS {$sqlType})";
    }

    private function mapCategoryDisplayLabel(string $serviceType): string
    {
        return match ($serviceType) {
            self::CATEGORY_PET_HOTEL => 'Pet Hotel',
            self::CATEGORY_CLINIC => 'Klinik Hewan',
            self::CATEGORY_GROOMING => 'Grooming',
            default => ucfirst($serviceType),
        };
    }

    private function formatDecimal(float $number): string
    {
        $formatted = number_format($number, 1, ',', '.');

        return str_ends_with($formatted, ',0')
            ? substr($formatted, 0, -2)
            : $formatted;
    }
}
