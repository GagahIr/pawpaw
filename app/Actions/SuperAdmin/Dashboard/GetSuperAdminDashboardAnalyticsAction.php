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

    private const TREND_MONTHS = 12;

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
     * Seluruh data dashboard super admin dalam satu panggilan.
     *
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
    public function getOverview(?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::instance($now ?? now());
        $monthStart = $now->startOfMonth();
        $monthEnd = $now->endOfMonth();

        $vendorStatus = $this->getVendorStatus($now);
        $bookingDistribution = $this->getBookingDistribution($monthStart, $monthEnd);
        $paymentHealth = $this->getPaymentHealth($monthStart, $monthEnd);
        $revenue = $this->getRevenueTrend($now);

        return [
            'metrics' => [
                'revenue' => $revenue['this_month'],
                'active_vendors' => $vendorStatus['subscription']['counts']['active'],
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
                'top_vendors' => $this->getTopVendors(),
                'recent_payments' => $this->getRecentPayments(),
                'pending_verifications' => $this->getPendingVerifications(),
                'recent_bookings' => $this->getRecentBookings(),
            ],
            'growth' => [
                'new_vendors' => Vendor::query()->whereBetween('join_at', [$monthStart, $monthEnd])->count(),
                'payment_success_rate' => $paymentHealth['success_rate'],
                'avg_booking_per_vendor' => $vendorStatus['subscription']['counts']['active'] > 0
                    ? round($bookingDistribution['total'] / $vendorStatus['subscription']['counts']['active'], 1)
                    : 0.0,
            ],
        ];
    }

    /**
     * Pendapatan 12 bulan terakhir (pembayaran berstatus success) beserta perbandingan bulan ini dan bulan lalu.
     *
     * @return array{
     *     categories: list<string>,
     *     revenues: list<int>,
     *     this_month: int,
     *     last_month: int,
     *     change_percent: float|null,
     *     has_data: bool
     * }
     */
    public function getRevenueTrend(CarbonInterface $now): array
    {
        $now = CarbonImmutable::instance($now);
        $windowStart = $now->startOfMonth()->subMonthsNoOverflow(self::TREND_MONTHS - 1);

        $totalsByMonth = VendorPayment::query()
            ->where('status', 'success')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$windowStart, $now->endOfMonth()])
            ->get(['amount', DB::raw('COALESCE(paid_at, created_at) as revenue_at')])
            ->groupBy(fn (VendorPayment $payment): string => CarbonImmutable::parse($payment->getAttribute('revenue_at'))->format('Y-m'))
            ->map(fn (Collection $payments): int => (int) $payments->sum('amount'));

        $categories = [];
        $revenues = [];

        for ($offset = 0; $offset < self::TREND_MONTHS; $offset++) {
            $month = $windowStart->addMonthsNoOverflow($offset);
            $categories[] = self::MONTH_NAMES[$month->month - 1].' '.$month->format('y');
            $revenues[] = (int) ($totalsByMonth[$month->format('Y-m')] ?? 0);
        }

        $thisMonth = $revenues[self::TREND_MONTHS - 1];
        $lastMonth = $revenues[self::TREND_MONTHS - 2];

        return [
            'categories' => $categories,
            'revenues' => $revenues,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'change_percent' => $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : null,
            'has_data' => array_sum($revenues) > 0,
        ];
    }

    /**
     * Status langganan dan verifikasi mitra.
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
     * Booking bulan ini per jenis dan per status dari tiga tabel booking.
     *
     * @return array{
     *     total: int,
     *     by_type: array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>},
     *     by_status: array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>}
     * }
     */
    public function getBookingDistribution(CarbonInterface $monthStart, CarbonInterface $monthEnd): array
    {
        $typeCounts = [];
        $statusCounts = array_fill_keys(array_keys(self::BOOKING_STATUSES), 0);

        foreach (self::BOOKING_TYPES as $type => $config) {
            $perStatus = DB::table($config['table'])
                ->whereNull('deleted_at')
                ->whereBetween($config['date_column'], [$monthStart, $monthEnd])
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
     * Jumlah pembayaran per status pada bulan berjalan dan tingkat keberhasilannya.
     *
     * Tingkat keberhasilan = success / (success + failed + expired), pembayaran yang masih pending belum dihitung.
     *
     * @return array{labels: list<string>, values: list<int>, colors: list<string>, total: int, has_data: bool, counts: array<string, int>, success_rate: float|null}
     */
    public function getPaymentHealth(CarbonInterface $monthStart, CarbonInterface $monthEnd): array
    {
        $counts = array_fill_keys(array_keys(self::PAYMENT_STATUSES), 0);

        VendorPayment::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
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
     * Lima mitra dengan total pendapatan (semua pembayaran success) tertinggi.
     * Tiap mitra membawa atribut revenue_total dan effective_subscription.
     *
     * @return Collection<int, Vendor>
     */
    public function getTopVendors(int $limit = 5): Collection
    {
        $now = CarbonImmutable::now();

        return Vendor::query()
            ->whereHas('payments', fn ($query) => $query->where('status', 'success'))
            ->withSum(['payments as revenue_total' => fn ($query) => $query->where('status', 'success')], 'amount')
            ->orderByDesc('revenue_total')
            ->limit($limit)
            ->get()
            ->each(fn (Vendor $vendor) => $vendor->setAttribute('effective_subscription', $this->resolveEffectiveSubscription($vendor, $now)));
    }

    /**
     * @return Collection<int, VendorPayment>
     */
    public function getRecentPayments(int $limit = 5): Collection
    {
        return VendorPayment::query()
            ->with(['vendor' => fn ($query) => $query->withTrashed()])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Antrean verifikasi, yang paling lama menunggu di urutan teratas.
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
     * Booking terbaru dari ketiga jenis layanan, diurutkan berdasarkan waktu dibuat.
     *
     * @return Collection<int, array{vendor_name: string, type: string, type_label: string, booked_at: CarbonImmutable, status: string, status_label: string}>
     */
    public function getRecentBookings(int $limit = 5): Collection
    {
        return collect(self::BOOKING_TYPES)
            ->flatMap(function (array $config, string $type) use ($limit): Collection {
                return DB::table($config['table'].' as booking')
                    ->join('vendor_service_items as service_item', 'service_item.id', '=', 'booking.vendor_service_item_id')
                    ->join('vendor_services as service', 'service.id', '=', 'service_item.vendor_service_id')
                    ->join('vendors as vendor', 'vendor.id', '=', 'service.vendor_id')
                    ->whereNull('booking.deleted_at')
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
}
