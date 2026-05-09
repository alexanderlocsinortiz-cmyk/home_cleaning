<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnalyticsController extends Controller
{
    private const DATE_RANGES = [7, 30, 60, 90];

    /**
     * Display the analytics dashboard.
     */
    public function index()
    {
        $dateRange = $this->resolveDateRange();
        $startDate = Carbon::now()->subDays($dateRange - 1)->startOfDay();

        $bookings = Booking::query()
            ->with([
                'rating:id,booking_id,stars',
                'service:id,slug,name',
                'staff:id,first_name,last_name,barangay',
            ])
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        $staffMembers = User::query()
            ->where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'barangay']);

        return view('analytics.dashboard', [
            'bookingMetrics' => $this->getBookingMetrics($bookings),
            'revenueMetrics' => $this->getRevenueMetrics($bookings),
            'staffPerformance' => $this->getStaffPerformance($bookings, $staffMembers),
            'customerSatisfaction' => $this->getCustomerSatisfaction($bookings),
            'servicePopularity' => $this->getServicePopularity($bookings),
            'dailyTrends' => $this->getDailyTrends($startDate, $bookings),
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Export analytics data as CSV.
     */
    public function export()
    {
        $dateRange = $this->resolveDateRange();
        $startDate = Carbon::now()->subDays($dateRange - 1)->startOfDay();

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
                optional($booking->created_at)->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="analytics_' . now()->timestamp . '.csv"');
    }

    private function resolveDateRange(): int
    {
        $dateRange = (int) request('date_range', 30);

        return in_array($dateRange, self::DATE_RANGES, true) ? $dateRange : 30;
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
                    ->where('staff_id', $staffMember->id)
                    ->values();

                if ($assignedBookings->isEmpty()) {
                    return null;
                }

                $ratings = $assignedBookings
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
            ->groupBy('service_type')
            ->map(function (Collection $serviceBookings, ?string $serviceType) {
                $completedBookings = $serviceBookings->where('status', 'completed');
                $serviceName = $serviceBookings->first()?->service?->name ?? Service::displayNameForSlug($serviceType);

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
        $bookingsByDate = $bookings->groupBy(
            fn (Booking $booking) => optional($booking->created_at)->toDateString()
        );

        $trends = collect();
        $cursor = $startDate->copy();
        $today = Carbon::now()->startOfDay();

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
            'property_adjustment',
            'property_fee',
            'room_bathroom_fees',
            'rooms_fee',
            'bathrooms_fee',
            'floor_area_fees',
            'floor_area_fee',
            'add_on_fees',
            'add_ons_fee',
        ])->sum(fn (string $field) => (float) ($booking->{$field} ?? 0));

        return round($computedTotal, 2);
    }
}
