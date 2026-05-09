<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function reports(Request $request)
    {
        [$filters, $dateRange] = $this->resolveReportFilters($request);

        $bookingScope = fn (Builder $query) => $this->applyReportDateRange($query, $dateRange);

        $totalBookings = $bookingScope(Booking::query())->count();
        $completedBookings = $bookingScope(Booking::where('status', 'completed'))->count();
        $pendingBookings = $bookingScope(Booking::where('status', 'pending'))->count();
        $cancelledBookings = $bookingScope(Booking::where('status', 'cancelled'))->count();
        $confirmedBookings = $bookingScope(Booking::where('status', 'confirmed'))->count();
        $inProgressBookings = $bookingScope(Booking::where('status', 'in_progress'))->count();
        $totalRevenue = $bookingScope(Booking::where('status', 'completed'))->sum('price');
        $averageCompletedRevenue = $completedBookings > 0
            ? round((float) $totalRevenue / $completedBookings, 2)
            : 0.0;
        $cancellationRate = $totalBookings > 0
            ? round(($cancelledBookings / $totalBookings) * 100, 1)
            : 0.0;
        $pendingOlderThanDay = $bookingScope(Booking::where('status', 'pending')->where('created_at', '<=', now()->subDay()))->count();
        $unassignedActiveBookings = $bookingScope(Booking::whereIn('status', ['pending', 'confirmed', 'in_progress'])->whereNull('staff_id'))->count();

        $revenueByType = $bookingScope(Booking::query())
            ->join('services', 'services.slug', '=', 'bookings.service_type')
            ->where('bookings.status', 'completed')
            ->where('services.is_active', true)
            ->selectRaw('services.slug as service_type, services.name as service_name, COUNT(bookings.id) as total, SUM(bookings.price) as revenue')
            ->groupBy('services.slug', 'services.name')
            ->orderByDesc('revenue')
            ->get();

        $bookingsByType = $bookingScope(Booking::query())
            ->join('services', 'services.slug', '=', 'bookings.service_type')
            ->where('services.is_active', true)
            ->selectRaw('services.slug as service_type, services.name as service_name, COUNT(bookings.id) as total')
            ->groupBy('services.slug', 'services.name')
            ->orderByDesc('total')
            ->get();

        $statusSummary = [
            'completed' => $completedBookings,
            'confirmed' => $confirmedBookings,
            'pending' => $pendingBookings,
            'cancelled' => $cancelledBookings,
            'in_progress' => $inProgressBookings,
        ];

        $invalidServiceBookings = Booking::query()
            ->whereNotIn('service_type', Service::query()->pluck('slug'))
            ->count();

        $advancedAnalytics = $this->buildAdvancedAnalytics();
        $staffPerformance = $advancedAnalytics['staffPerformance'];

        $recentBookings = $bookingScope(Booking::with(['user', 'staff', 'service']))
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        $monthlyBookings = $advancedAnalytics['monthlyBookingTrend'];

        $reportInsights = [
            'average_completed_revenue' => $averageCompletedRevenue,
            'cancellation_rate' => $cancellationRate,
            'pending_older_than_day' => $pendingOlderThanDay,
            'unassigned_active_bookings' => $unassignedActiveBookings,
            'date_label' => $dateRange['label'],
        ];

        return view('admin.reports', array_merge(compact(
            'totalBookings', 'completedBookings', 'pendingBookings',
            'cancelledBookings', 'confirmedBookings', 'inProgressBookings',
            'totalRevenue', 'revenueByType', 'bookingsByType',
            'statusSummary', 'invalidServiceBookings',
            'staffPerformance', 'recentBookings', 'monthlyBookings',
            'filters', 'reportInsights'
        ), $advancedAnalytics));
    }

    private function resolveReportFilters(Request $request): array
    {
        $period = in_array($request->get('period'), ['all', 'today', 'this_week', 'this_month', 'last_month', 'custom'], true)
            ? $request->get('period')
            : 'all';

        $filters = [
            'period' => $period,
            'date_from' => (string) $request->get('date_from', ''),
            'date_to' => (string) $request->get('date_to', ''),
        ];

        $start = null;
        $end = null;
        $label = 'All time';

        if ($period === 'today') {
            $start = now()->startOfDay();
            $end = now()->endOfDay();
            $label = 'Today';
        } elseif ($period === 'this_week') {
            $start = now()->startOfWeek();
            $end = now()->endOfWeek();
            $label = 'This week';
        } elseif ($period === 'this_month') {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
            $label = 'This month';
        } elseif ($period === 'last_month') {
            $start = now()->subMonthNoOverflow()->startOfMonth();
            $end = now()->subMonthNoOverflow()->endOfMonth();
            $label = 'Last month';
        } elseif ($period === 'custom') {
            $start = $this->parseReportDate($filters['date_from'])?->startOfDay();
            $end = $this->parseReportDate($filters['date_to'])?->endOfDay();

            if ($start && $end && $start->gt($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            $label = trim(($start?->format('M d, Y') ?? 'Any start').' - '.($end?->format('M d, Y') ?? 'Any end'));
        }

        return [$filters, compact('start', 'end', 'label')];
    }

    private function parseReportDate(string $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyReportDateRange(Builder $query, array $dateRange): Builder
    {
        return $query
            ->when($dateRange['start'] ?? null, fn (Builder $query, Carbon $start) => $query->where('bookings.created_at', '>=', $start))
            ->when($dateRange['end'] ?? null, fn (Builder $query, Carbon $end) => $query->where('bookings.created_at', '<=', $end));
    }

    private function monthlyBookingsMonthExpression(): string
    {
        return match (Booking::query()->getConnection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(MONTH FROM created_at) AS INTEGER)',
            default => 'MONTH(created_at)',
        };
    }

    private function buildAdvancedAnalytics(): array
    {
        $now = Carbon::now();
        $monthBuckets = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => $now->copy()->startOfMonth()->subMonths($monthsAgo))
            ->values();

        $rangeStart = $monthBuckets->first()->copy()->startOfMonth();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $currentMonthStart->copy()->subMonth()->endOfMonth();

        $bookingsInRange = Booking::query()
            ->where('created_at', '>=', $rangeStart)
            ->get(['id', 'status', 'price', 'created_at', 'scheduled_date', 'scheduled_time']);

        $monthlyBookingTrend = $monthBuckets->map(function (Carbon $month) use ($bookingsInRange) {
            $monthBookings = $bookingsInRange->filter(fn (Booking $booking) => $booking->created_at?->format('Y-m') === $month->format('Y-m'));
            $completedMonthBookings = $monthBookings->where('status', 'completed');

            return (object) [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'short_label' => $month->format('M'),
                'total' => $monthBookings->count(),
                'completed' => $completedMonthBookings->count(),
                'revenue' => round((float) $completedMonthBookings->sum('price'), 2),
                'completion_rate' => $monthBookings->count() > 0
                    ? round(($completedMonthBookings->count() / $monthBookings->count()) * 100, 1)
                    : 0.0,
            ];
        })->values();

        $previousMonthTotal = null;
        $monthlyBookingTrend = $monthlyBookingTrend->map(function (object $month) use (&$previousMonthTotal) {
            $month->growth = $previousMonthTotal === null
                ? null
                : ($previousMonthTotal > 0
                    ? round((($month->total - $previousMonthTotal) / $previousMonthTotal) * 100, 1)
                    : ($month->total > 0 ? 100.0 : 0.0));
            $previousMonthTotal = $month->total;

            return $month;
        })->values();

        $bookingsForDemand = Booking::query()
            ->whereDate('scheduled_date', '>=', $rangeStart->toDateString())
            ->get(['id', 'status', 'scheduled_date', 'scheduled_time']);

        $timeSlotTrends = $bookingsForDemand
            ->filter(fn (Booking $booking) => filled($booking->scheduled_time))
            ->groupBy(fn (Booking $booking) => Carbon::parse($booking->scheduled_time)->format('g:i A'))
            ->map(function ($slotBookings, string $label) {
                $bookings = collect($slotBookings);

                return (object) [
                    'label' => $label,
                    'total' => $bookings->count(),
                    'completed' => $bookings->where('status', 'completed')->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(5);

        $weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $weekdayTrends = collect($weekdayOrder)->map(function (string $weekday) use ($bookingsForDemand) {
            $weekdayBookings = $bookingsForDemand->filter(
                fn (Booking $booking) => Carbon::parse($booking->scheduled_date)->format('l') === $weekday
            );

            return (object) [
                'label' => $weekday,
                'short_label' => substr($weekday, 0, 3),
                'total' => $weekdayBookings->count(),
            ];
        })->values();

        $ratingsInRange = Rating::query()
            ->where('created_at', '>=', $rangeStart)
            ->get(['id', 'stars', 'created_at']);

        $satisfactionTrend = $monthBuckets->map(function (Carbon $month) use ($ratingsInRange) {
            $monthRatings = $ratingsInRange->filter(fn (Rating $rating) => $rating->created_at?->format('Y-m') === $month->format('Y-m'));
            $reviewCount = $monthRatings->count();
            $positiveReviews = $monthRatings->filter(fn (Rating $rating) => (int) $rating->stars >= 4)->count();

            return (object) [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'short_label' => $month->format('M'),
                'average' => $reviewCount > 0 ? round((float) $monthRatings->avg('stars'), 1) : null,
                'reviews' => $reviewCount,
                'positive_share' => $reviewCount > 0 ? round(($positiveReviews / $reviewCount) * 100, 1) : 0.0,
            ];
        })->values();

        $overallRatingStats = DB::table('ratings')->selectRaw('COUNT(*) as total, AVG(stars) as avg_stars')->first();
        $overallReviewCount = (int) ($overallRatingStats->total ?? 0);
        $overallAvgStars = $overallReviewCount > 0 ? round((float) ($overallRatingStats->avg_stars ?? 0), 1) : null;
        $currentMonthAverageRating = $satisfactionTrend->last()->average;
        $previousMonthAverageRating = $satisfactionTrend->slice(-2, 1)->first()->average ?? null;

        $staffPerformance = User::where('role', 'staff')
            ->with(['assignedBookings.rating'])
            ->get()
            ->map(function (User $staff) use ($currentMonthStart, $currentMonthEnd, $previousMonthStart, $previousMonthEnd) {
                $assigned = $staff->assignedBookings;
                $completed = $assigned->where('status', 'completed');
                $ratings = $completed->pluck('rating')->filter();
                $currentMonthCompleted = $completed->filter(
                    fn (Booking $booking) => filled($booking->scheduled_date)
                        && Carbon::parse($booking->scheduled_date)->betweenIncluded($currentMonthStart, $currentMonthEnd)
                );
                $previousMonthCompleted = $completed->filter(
                    fn (Booking $booking) => filled($booking->scheduled_date)
                        && Carbon::parse($booking->scheduled_date)->betweenIncluded($previousMonthStart, $previousMonthEnd)
                );
                $currentMonthRatings = $ratings->filter(
                    fn (Rating $rating) => $rating->created_at
                        && $rating->created_at->gte($currentMonthStart)
                        && $rating->created_at->lte($currentMonthEnd)
                );

                $staff->total_assigned = $assigned->count();
                $staff->total_completed = $completed->count();
                $staff->completion_rate = $assigned->count() > 0
                    ? round(($completed->count() / $assigned->count()) * 100, 1)
                    : 0.0;
                $staff->avg_rating = $ratings->count() > 0
                    ? round((float) $ratings->avg('stars'), 1)
                    : null;
                $staff->total_ratings = $ratings->count();
                $staff->current_month_completed = $currentMonthCompleted->count();
                $staff->previous_month_completed = $previousMonthCompleted->count();
                $staff->trend_change = $staff->current_month_completed - $staff->previous_month_completed;
                $staff->current_month_revenue = round((float) $currentMonthCompleted->sum('price'), 2);
                $staff->current_month_avg_rating = $currentMonthRatings->count() > 0
                    ? round((float) $currentMonthRatings->avg('stars'), 1)
                    : null;

                return $staff;
            })
            ->values();

        $staffPerformance = $staffPerformance
            ->sort(function ($left, $right) {
                $leftRank = [
                    $left->current_month_completed > 0 ? 1 : 0,
                    $left->current_month_completed,
                    $left->avg_rating ?? 0,
                    $left->completion_rate,
                    $left->total_completed,
                    strtolower(trim($left->last_name.' '.$left->first_name)),
                ];

                $rightRank = [
                    $right->current_month_completed > 0 ? 1 : 0,
                    $right->current_month_completed,
                    $right->avg_rating ?? 0,
                    $right->completion_rate,
                    $right->total_completed,
                    strtolower(trim($right->last_name.' '.$right->first_name)),
                ];

                return $rightRank <=> $leftRank;
            })
            ->values();

        $topStaffLeaders = $staffPerformance
            ->filter(fn (User $staff) => $staff->current_month_completed > 0)
            ->take(5)
            ->values();

        $totalBookings = DB::table('bookings')->count();
        $completedBookings = DB::table('bookings')->where('status', 'completed')->count();
        $currentMonthBookings = $monthlyBookingTrend->last();
        $previousMonthBookings = $monthlyBookingTrend->slice(-2, 1)->first();
        $busiestTimeSlot = $timeSlotTrends->first();
        $busiestWeekday = $weekdayTrends->sortByDesc('total')->first();

        $analyticsOverview = [
            'completion_rate' => $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0.0,
            'booking_growth' => $currentMonthBookings?->growth,
            'current_month_bookings' => $currentMonthBookings?->total ?? 0,
            'previous_month_bookings' => $previousMonthBookings?->total ?? 0,
            'average_satisfaction' => $overallAvgStars,
            'total_reviews' => $overallReviewCount,
            'satisfaction_delta' => ($currentMonthAverageRating !== null && $previousMonthAverageRating !== null)
                ? round($currentMonthAverageRating - $previousMonthAverageRating, 1)
                : null,
            'peak_time_label' => $busiestTimeSlot?->label,
            'peak_time_total' => $busiestTimeSlot?->total ?? 0,
            'peak_day_label' => $busiestWeekday?->label,
            'peak_day_total' => $busiestWeekday?->total ?? 0,
        ];

        return [
            'analyticsOverview' => $analyticsOverview,
            'monthlyBookingTrend' => $monthlyBookingTrend,
            'timeSlotTrends' => $timeSlotTrends,
            'weekdayTrends' => $weekdayTrends,
            'staffPerformance' => $staffPerformance,
            'topStaffLeaders' => $topStaffLeaders,
            'satisfactionTrend' => $satisfactionTrend,
        ];
    }
}
