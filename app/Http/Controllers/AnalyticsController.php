<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private const DATE_RANGES = [7, 30, 60, 90];

    /**
     * Display the analytics dashboard.
     */
    public function index()
    {
        $dateRange = $this->resolveDateRange();
        $analyticsTimezone = $this->analyticsTimezone();
        $analyticsNow = Carbon::now($analyticsTimezone);
        $startDate = $analyticsNow->copy()->subDays($dateRange - 1)->startOfDay()->utc();

        $bookings = Booking::query()->where('bookings.created_at', '>=', $startDate);

        return view('analytics.dashboard', [
            'bookingMetrics' => $this->getBookingMetricsFromDatabase($bookings),
            'revenueMetrics' => $this->getRevenueMetricsFromDatabase($bookings),
            'staffPerformance' => $this->getStaffPerformanceFromDatabase($bookings),
            'customerSatisfaction' => $this->getCustomerSatisfactionFromDatabase($bookings, $startDate),
            'servicePopularity' => $this->getServicePopularityFromDatabase($bookings),
            'dailyTrends' => $this->getDailyTrendsFromDatabase($bookings, $startDate),
            'dateRange' => $dateRange,
        ]);
    }

    private function bookingTotalSql(string $table = 'bookings'): string
    {
        return "COALESCE(NULLIF({$table}.price, 0), COALESCE({$table}.base_price, 0) + COALESCE({$table}.property_fee, 0) + COALESCE({$table}.rooms_fee, 0) + COALESCE({$table}.bathrooms_fee, 0) + COALESCE({$table}.floor_area_fee, 0) + COALESCE({$table}.add_ons_fee, 0))";
    }

    private function getBookingMetricsFromDatabase($bookings): array
    {
        $row = (clone $bookings)->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed, SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")->first();
        $total = (int) ($row->total ?? 0);
        $completed = (int) ($row->completed ?? 0);

        return [
            'total' => $total,
            'pending' => (int) ($row->pending ?? 0),
            'confirmed' => (int) ($row->confirmed ?? 0),
            'in_progress' => (int) ($row->in_progress ?? 0),
            'completed' => $completed,
            'cancelled' => (int) ($row->cancelled ?? 0),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
        ];
    }

    private function getRevenueMetricsFromDatabase($bookings): array
    {
        $totalSql = $this->bookingTotalSql();
        $latestPaymentStatus = "COALESCE((SELECT p.status FROM payments p WHERE p.booking_id = bookings.id ORDER BY p.id DESC LIMIT 1), 'pending')";
        $row = (clone $bookings)->where('bookings.status', 'completed')->selectRaw("COUNT(*) as completed, SUM($totalSql) as total_revenue, SUM(CASE WHEN $latestPaymentStatus = 'paid' THEN 1 ELSE 0 END) as paid_bookings, SUM(CASE WHEN $latestPaymentStatus != 'paid' THEN 1 ELSE 0 END) as pending_payments, SUM(CASE WHEN $latestPaymentStatus != 'paid' THEN $totalSql ELSE 0 END) as outstanding_revenue")->first();
        $completed = (int) ($row->completed ?? 0);
        $totalRevenue = (float) ($row->total_revenue ?? 0);
        $paid = (int) ($row->paid_bookings ?? 0);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'average_booking_value' => $completed > 0 ? round($totalRevenue / $completed, 2) : 0.0,
            'paid_bookings' => $paid,
            'pending_payments' => (int) ($row->pending_payments ?? 0),
            'outstanding_revenue' => round((float) ($row->outstanding_revenue ?? 0), 2),
            'payment_collection_rate' => $completed > 0 ? round(($paid / $completed) * 100, 1) : 0.0,
        ];
    }

    private function getStaffPerformanceFromDatabase($bookings): Collection
    {
        $bookingRows = (clone $bookings)
            ->with('staffAssignments:id,booking_id,staff_id')
            ->get();
        $staffIds = $bookingRows
            ->flatMap(fn (Booking $booking) => $booking->assignedStaffIds())
            ->unique()
            ->values();
        $staff = User::whereIn('id', $staffIds)
            ->get(['id', 'first_name', 'last_name', 'barangay'])
            ->keyBy('id');

        return $staff->map(function (User $member) use ($bookingRows) {
            $assignedBookings = $bookingRows
                ->filter(fn (Booking $booking): bool => $booking->isAssignedToStaff((int) $member->id));
            $assigned = $assignedBookings->count();
            $completed = $assignedBookings->where('status', 'completed')->count();
            $ratings = $assignedBookings
                ->filter(fn (Booking $booking): bool => (int) $booking->staff_id === (int) $member->id)
                ->pluck('rating')
                ->filter();

            return [
                'name' => $member->full_name,
                'barangay' => $member->barangay_name,
                'assigned' => $assigned,
                'completed' => $completed,
                'completion_rate' => $assigned > 0 ? round(($completed / $assigned) * 100, 1) : 0.0,
                'average_rating' => $ratings->isNotEmpty() ? round((float) $ratings->avg('stars'), 1) : null,
                'reviews' => $ratings->count(),
            ];
        })->sortByDesc(fn (array $member) => ($member['completed'] * 1000) + (int) round(($member['average_rating'] ?? 0) * 100))->values();
    }

    private function getCustomerSatisfactionFromDatabase($bookings, Carbon $startDate): array
    {
        $ratings = DB::table('ratings')->join('bookings', 'bookings.id', '=', 'ratings.booking_id')->where('bookings.created_at', '>=', $startDate)->select('ratings.stars')->get();
        $total = $ratings->count();

        return [
            'average_rating' => $total > 0 ? round((float) $ratings->avg('stars'), 1) : null,
            'total_ratings' => $total,
            'satisfaction_percentage' => $total > 0 ? round(($ratings->where('stars', '>=', 4)->count() / $total) * 100, 1) : 0.0,
            'distribution' => collect(range(5, 1))->map(fn (int $stars) => ['stars' => $stars, 'count' => $ratings->where('stars', $stars)->count(), 'percentage' => $total > 0 ? round(($ratings->where('stars', $stars)->count() / $total) * 100, 1) : 0.0]),
        ];
    }

    private function getServicePopularityFromDatabase($bookings): Collection
    {
        $totalSql = $this->bookingTotalSql();

        return (clone $bookings)->leftJoin('services', 'services.id', '=', 'bookings.service_id')->select(['services.slug as service_slug', 'services.name as service_name'])->selectRaw("COUNT(bookings.id) as bookings, SUM(CASE WHEN bookings.status = 'completed' THEN 1 ELSE 0 END) as completed, AVG($totalSql) as average_price, SUM(CASE WHEN bookings.status = 'completed' THEN $totalSql ELSE 0 END) as revenue")->groupBy('services.slug', 'services.name')->orderByDesc('bookings')->get()->map(fn ($row) => ['name' => $row->service_name ?? Service::displayNameForSlug($row->service_slug), 'bookings' => (int) $row->bookings, 'completed' => (int) $row->completed, 'completion_rate' => $row->bookings > 0 ? round(((int) $row->completed / (int) $row->bookings) * 100, 1) : 0.0, 'average_price' => round((float) ($row->average_price ?? 0), 2), 'revenue' => round((float) ($row->revenue ?? 0), 2)])->values();
    }

    private function getDailyTrendsFromDatabase($bookings, Carbon $startDate): Collection
    {
        $analyticsTimezone = $this->analyticsTimezone();
        $rows = (clone $bookings)->get()
            ->groupBy(fn (Booking $booking) => $booking->created_at?->copy()->timezone($analyticsTimezone)->toDateString())
            ->map(function (Collection $dayBookings) {
                $completedBookings = $dayBookings->where('status', 'completed');

                return (object) [
                    'bookings' => $dayBookings->count(),
                    'completed' => $completedBookings->count(),
                    'revenue' => $completedBookings->sum(fn (Booking $booking) => $this->bookingTotal($booking)),
                ];
            });
        $trends = collect();
        for ($cursor = $startDate->copy()->timezone($analyticsTimezone)->startOfDay(), $today = Carbon::now($analyticsTimezone)->startOfDay(); $cursor->lte($today); $cursor->addDay()) {
            $row = $rows->get($cursor->toDateString());
            $trends->push(['date' => $cursor->toDateString(), 'label' => $cursor->format('M d'), 'bookings' => (int) ($row->bookings ?? 0), 'completed' => (int) ($row->completed ?? 0), 'revenue' => round((float) ($row->revenue ?? 0), 2)]);
        }

        return $trends;
    }

    /**
     * Export analytics data as CSV.
     */
    public function export()
    {
        $dateRange = $this->resolveDateRange();
        $analyticsNow = Carbon::now($this->analyticsTimezone());
        $startDate = $analyticsNow->copy()->subDays($dateRange - 1)->startOfDay()->utc();

        $bookings = Booking::query()
            ->with(['user:id,email,first_name,last_name', 'service:id,slug,name'])
            ->where('created_at', '>=', $startDate)
            ->orderByDesc('created_at')
            ->get();

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Booking ID', 'Client Email', 'Service', 'Status', 'Price', 'Created Date']);

        foreach ($bookings as $booking) {
            fputcsv($handle, [
                $booking->id,
                $booking->user?->email,
                $booking->service_label,
                $booking->status,
                number_format($this->bookingTotal($booking), 2, '.', ''),
                optional($booking->created_at?->copy()->timezone($this->analyticsTimezone()))->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="analytics_'.now()->timestamp.'.csv"');
    }

    private function resolveDateRange(): int
    {
        $dateRange = (int) request('date_range', 30);

        return in_array($dateRange, self::DATE_RANGES, true) ? $dateRange : 30;
    }

    private function analyticsTimezone(): string
    {
        return config('cleanflow.attendance_timezone', 'Asia/Manila');
    }

    private function getBookingMetrics(Collection $bookings): array
    {
        $totalBookings = $bookings->count();

        return [
            'total' => $totalBookings,
            'pending' => $bookings->where('status', 'pending')->count(),
            'confirmed' => $bookings->where('status', 'confirmed')->count(),
            'in_progress' => $bookings->where('status', 'in_progress')->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
            'completion_rate' => $totalBookings > 0
                ? round(($bookings->where('status', 'completed')->count() / $totalBookings) * 100, 1)
                : 0.0,
        ];
    }

    private function getRevenueMetrics(Collection $bookings): array
    {
        $completedBookings = $bookings->where('status', 'completed')->values();
        $totalRevenue = $completedBookings->sum(fn (Booking $booking) => $this->bookingTotal($booking));
        $paidBookings = $completedBookings->where('payment_status', 'paid')->values();
        $pendingPayments = $completedBookings
            ->filter(fn (Booking $booking) => $booking->payment_status !== 'paid')
            ->values();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'average_booking_value' => $completedBookings->count() > 0
                ? round($totalRevenue / $completedBookings->count(), 2)
                : 0.0,
            'paid_bookings' => $paidBookings->count(),
            'pending_payments' => $pendingPayments->count(),
            'outstanding_revenue' => round($pendingPayments->sum(fn (Booking $booking) => $this->bookingTotal($booking)), 2),
            'payment_collection_rate' => $completedBookings->count() > 0
                ? round(($paidBookings->count() / $completedBookings->count()) * 100, 1)
                : 0.0,
        ];
    }

    private function getStaffPerformance(Collection $bookings, Collection $staffMembers): Collection
    {
        return $staffMembers
            ->map(function (User $staffMember) use ($bookings) {
                $assignedBookings = $bookings
                    ->filter(fn (Booking $booking): bool => $booking->isAssignedToStaff((int) $staffMember->id))
                    ->values();

                if ($assignedBookings->isEmpty()) {
                    return null;
                }

                $ratings = $assignedBookings
                    ->filter(fn (Booking $booking): bool => (int) $booking->staff_id === (int) $staffMember->id)
                    ->pluck('rating')
                    ->filter();

                $completedCount = $assignedBookings->where('status', 'completed')->count();
                $averageRating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;

                return [
                    'name' => $staffMember->full_name,
                    'barangay' => $staffMember->barangay_name,
                    'assigned' => $assignedBookings->count(),
                    'completed' => $completedCount,
                    'completion_rate' => round(($completedCount / $assignedBookings->count()) * 100, 1),
                    'average_rating' => $averageRating,
                    'reviews' => $ratings->count(),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $staffMember) => ($staffMember['completed'] * 1000) + (int) round(($staffMember['average_rating'] ?? 0) * 100))
            ->values();
    }

    private function getCustomerSatisfaction(Collection $bookings): array
    {
        $ratings = $bookings
            ->pluck('rating')
            ->filter()
            ->values();

        $totalRatings = $ratings->count();
        $distribution = collect(range(5, 1))
            ->map(function (int $stars) use ($ratings, $totalRatings) {
                $count = $ratings->where('stars', $stars)->count();

                return [
                    'stars' => $stars,
                    'count' => $count,
                    'percentage' => $totalRatings > 0 ? round(($count / $totalRatings) * 100, 1) : 0.0,
                ];
            });

        return [
            'average_rating' => $totalRatings > 0 ? round($ratings->avg('stars'), 1) : null,
            'total_ratings' => $totalRatings,
            'satisfaction_percentage' => $totalRatings > 0
                ? round(($ratings->filter(fn ($rating) => $rating->stars >= 4)->count() / $totalRatings) * 100, 1)
                : 0.0,
            'distribution' => $distribution,
        ];
    }

    private function getServicePopularity(Collection $bookings): Collection
    {
        return $bookings
            ->groupBy(fn (Booking $booking) => $booking->service_id)
            ->map(function (Collection $serviceBookings) {
                $completedBookings = $serviceBookings->where('status', 'completed');
                $serviceName = $serviceBookings->first()?->service?->name ?? Service::displayNameForSlug($serviceBookings->first()?->service_type);

                return [
                    'name' => $serviceName,
                    'bookings' => $serviceBookings->count(),
                    'completed' => $completedBookings->count(),
                    'completion_rate' => $serviceBookings->count() > 0
                        ? round(($completedBookings->count() / $serviceBookings->count()) * 100, 1)
                        : 0.0,
                    'average_price' => round($serviceBookings->avg(fn (Booking $booking) => $this->bookingTotal($booking)) ?? 0, 2),
                    'revenue' => round($completedBookings->sum(fn (Booking $booking) => $this->bookingTotal($booking)), 2),
                ];
            })
            ->sortByDesc('bookings')
            ->values();
    }

    private function getDailyTrends(Carbon $startDate, Collection $bookings): Collection
    {
        $analyticsTimezone = $this->analyticsTimezone();
        $bookingsByDate = $bookings->groupBy(
            fn (Booking $booking) => $booking->created_at?->copy()->timezone($analyticsTimezone)->toDateString()
        );

        $trends = collect();
        $cursor = $startDate->copy()->timezone($analyticsTimezone)->startOfDay();
        $today = Carbon::now($analyticsTimezone)->startOfDay();

        while ($cursor->lte($today)) {
            $dayBookings = $bookingsByDate->get($cursor->toDateString(), collect());
            $completedBookings = $dayBookings->where('status', 'completed');

            $trends->push([
                'date' => $cursor->toDateString(),
                'label' => $cursor->format('M d'),
                'bookings' => $dayBookings->count(),
                'completed' => $completedBookings->count(),
                'revenue' => round($completedBookings->sum(fn (Booking $booking) => $this->bookingTotal($booking)), 2),
            ]);

            $cursor->addDay();
        }

        return $trends;
    }

    private function bookingTotal(Booking $booking): float
    {
        if (is_numeric($booking->price) && (float) $booking->price > 0) {
            return round((float) $booking->price, 2);
        }

        $computedTotal = collect([
            'base_price',
            'property_fee',
            'rooms_fee',
            'bathrooms_fee',
            'floor_area_fee',
            'add_ons_fee',
        ])->sum(fn (string $field) => (float) ($booking->{$field} ?? 0));

        return round($computedTotal, 2);
    }
}
