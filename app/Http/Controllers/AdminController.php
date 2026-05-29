<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AttendanceHelpers;
use App\Models\AttendanceLog;
use App\Models\Booking;
use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use AttendanceHelpers;

    public function dashboard(Request $request)
    {
        $dashboardNow = Carbon::now($this->attendanceTimezone());
        $dashboardStats = $this->dashboardStats();
        $totalEarnings = $dashboardStats['total_earnings'];
        $recentBookings = Booking::with(['user', 'service'])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->latest()
            ->take(4)
            ->get();
        $topStaff = $this->dashboardTopStaff($dashboardNow);
        $pendingEscalationSummary = $this->pendingEscalationSummary();
        $tomorrowJobs = Booking::query()
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->whereDate('scheduled_date', $dashboardNow->copy()->addDay()->toDateString())
            ->count();
        $unassignedBookings = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('staff_id')
            ->count();
        [$todayStartUtc, $todayEndUtc] = $this->attendanceUtcRange($dashboardNow);
        $presentStaffCount = AttendanceLog::query()
            ->where('punch_type', 'in')
            ->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])
            ->distinct('user_id')
            ->count('user_id');

        // 30-day analytics window
        $analyticsStart = Carbon::now()->subDays(29)->startOfDay();
        $analyticsBookings = Booking::query()
            ->with(['rating:id,booking_id,stars', 'service:id,slug,name'])
            ->where('created_at', '>=', $analyticsStart)
            ->orderBy('created_at')
            ->get();

        // Customer satisfaction (30-day)
        $analyticsRatings = $analyticsBookings->pluck('rating')->filter()->values();
        $analyticsAvgRating = $analyticsRatings->count() > 0 ? round($analyticsRatings->avg('stars'), 1) : null;
        $analyticsRatingCount = $analyticsRatings->count();

        // All-time cancelled count
        $cancelledCount = Booking::where('status', 'cancelled')->count();

        // Service popularity is all-time to match the total bookings shown in the card.
        $servicePopularity = Booking::query()
            ->with('service:id,slug,name')
            ->get()
            ->groupBy('service_type')
            ->map(fn ($group, $type) => [
                'name' => $group->first()?->service?->name ?? ucfirst(str_replace('_', ' ', $type ?? 'Other')),
                'bookings' => $group->count(),
            ])
            ->sortByDesc('bookings')
            ->take(5)
            ->values();

        // Staff performance with ratings (30-day, top 5)
        $staffPerformance = User::where('role', 'staff')
            ->get(['id', 'first_name', 'last_name'])
            ->map(function (User $member) use ($analyticsBookings) {
                $assigned = $analyticsBookings->where('staff_id', $member->id);
                if ($assigned->isEmpty()) {
                    return null;
                }
                $completed = $assigned->where('status', 'completed')->count();
                $ratings = $assigned->pluck('rating')->filter();

                return [
                    'name' => $member->display_name,
                    'completed' => $completed,
                    'rating' => $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null,
                ];
            })
            ->filter()
            ->sortByDesc('completed')
            ->take(5)
            ->values();

        // Booking trend data uses scheduled service dates, so restored historical bookings still appear.
        $trendBookings = Booking::query()
            ->select(['id', 'scheduled_date', 'status'])
            ->whereNotNull('scheduled_date')
            ->orderBy('scheduled_date')
            ->get();
        $bookingsByDate = $trendBookings->groupBy(fn (Booking $b) => Carbon::parse($b->scheduled_date)->toDateString());
        $chartLabels = $chartDateLabels = $chartBookingsData = [];
        $firstTrendDate = $trendBookings->isNotEmpty()
            ? Carbon::parse($trendBookings->first()->scheduled_date)->startOfDay()
            : Carbon::now()->startOfDay();
        $lastTrendDate = $trendBookings->isNotEmpty()
            ? Carbon::parse($trendBookings->last()->scheduled_date)->startOfDay()
            : Carbon::now()->startOfDay();
        $chartCursor = $firstTrendDate->copy();
        while ($chartCursor->lte($lastTrendDate)) {
            $dateStr = $chartCursor->toDateString();
            $day = $bookingsByDate->get($dateStr, collect());
            $chartDateLabels[] = $dateStr;
            $chartLabels[] = $chartCursor->format('M d');
            $chartBookingsData[] = $day->count();
            $chartCursor->addDay();
        }

        $availableRevenueMonths = Booking::query()
            ->where('status', 'completed')
            ->whereNotNull('scheduled_date')
            ->orderByDesc('scheduled_date')
            ->pluck('scheduled_date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->unique()
            ->values();
        $currentMonthKey = $dashboardNow->format('Y-m');
        $requestedRevenueMonth = $request->query('revenue_month');
        $selectedRevenueMonth = $requestedRevenueMonth
            ?: ($availableRevenueMonths->contains($currentMonthKey) ? $currentMonthKey : ($availableRevenueMonths->first() ?? $currentMonthKey));
        if (! preg_match('/^\d{4}-\d{2}$/', (string) $selectedRevenueMonth)) {
            $selectedRevenueMonth = $currentMonthKey;
        }

        $revenueMonthStart = Carbon::createFromFormat('Y-m-d', $selectedRevenueMonth.'-01')->startOfMonth();
        $revenueMonthEnd = $revenueMonthStart->copy()->endOfMonth();
        $revenueBookings = Booking::query()
            ->where('status', 'completed')
            ->whereBetween('scheduled_date', [$revenueMonthStart->toDateString(), $revenueMonthEnd->toDateString()])
            ->get(['id', 'scheduled_date', 'price']);
        $revenueByDate = $revenueBookings->groupBy(fn (Booking $b) => Carbon::parse($b->scheduled_date)->toDateString());
        $revenueLabels = $revenueDateLabels = $chartRevenueData = [];
        $revenueCursor = $revenueMonthStart->copy();
        while ($revenueCursor->lte($revenueMonthEnd)) {
            $dateStr = $revenueCursor->toDateString();
            $day = $revenueByDate->get($dateStr, collect());
            $revenueDateLabels[] = $dateStr;
            $revenueLabels[] = $revenueCursor->format('M d');
            $chartRevenueData[] = round($day->sum(fn (Booking $b) => (float) ($b->price ?? 0)), 2);
            $revenueCursor->addDay();
        }
        $selectedRevenueTotal = round(array_sum($chartRevenueData), 2);
        $selectedRevenueCompletedCount = $revenueBookings->count();
        $selectedRevenueMonthLabel = $revenueMonthStart->format('F Y');

        // Recent activity (last 8 bookings sorted by updated_at)
        $recentActivity = Booking::with(['user:id,first_name,last_name', 'service:id,name,slug'])
            ->latest('updated_at')
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'dashboardNow', 'dashboardStats', 'recentBookings', 'topStaff',
            'totalEarnings', 'pendingEscalationSummary', 'tomorrowJobs',
            'unassignedBookings', 'presentStaffCount',
            'analyticsAvgRating', 'analyticsRatingCount', 'cancelledCount',
            'servicePopularity', 'staffPerformance',
            'chartLabels', 'chartDateLabels', 'chartBookingsData',
            'availableRevenueMonths', 'selectedRevenueMonth', 'selectedRevenueMonthLabel',
            'selectedRevenueTotal', 'selectedRevenueCompletedCount',
            'revenueLabels', 'revenueDateLabels', 'chartRevenueData',
            'recentActivity'
        ));
    }

    public function serviceAreas()
    {
        $barangays = config('cleanflow.service_areas', []);

        return view('admin.service-areas', compact('barangays'));
    }

    private function dashboardStats(): array
    {
        $bookingCounts = DB::table('bookings')->selectRaw("
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_bookings,
            SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as total_earnings
        ")->first();

        $userCounts = DB::table('users')->selectRaw("
            SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) as customers,
            SUM(CASE WHEN role = 'client' AND email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified_customers,
            SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff
        ")->first();

        return [
            'total_bookings' => (int) ($bookingCounts->total_bookings ?? 0),
            'pending_bookings' => (int) ($bookingCounts->pending_bookings ?? 0),
            'confirmed_bookings' => (int) ($bookingCounts->confirmed_bookings ?? 0),
            'completed_bookings' => (int) ($bookingCounts->completed_bookings ?? 0),
            'in_progress_bookings' => (int) ($bookingCounts->in_progress_bookings ?? 0),
            'total_earnings' => (float) ($bookingCounts->total_earnings ?? 0),
            'customers' => (int) ($userCounts->customers ?? 0),
            'verified_customers' => (int) ($userCounts->verified_customers ?? 0),
            'staff' => (int) ($userCounts->staff ?? 0),
            'active_devices' => Device::where('is_active', true)->count(),
        ];
    }

    private function dashboardTopStaff(Carbon $dashboardNow)
    {
        $currentMonthStart = $dashboardNow->copy()->startOfMonth();
        $currentMonthEnd = $dashboardNow->copy()->endOfMonth();
        $previousMonthStart = $dashboardNow->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $dashboardNow->copy()->subMonth()->endOfMonth();

        return User::where('role', 'staff')
            ->with(['assignedBookings' => function ($query) use ($previousMonthStart, $currentMonthEnd) {
                $query->where('status', 'completed')
                    ->whereDate('scheduled_date', '>=', $previousMonthStart->toDateString())
                    ->whereDate('scheduled_date', '<=', $currentMonthEnd->toDateString());
            }])
            ->get()
            ->map(function (User $staff) use ($currentMonthStart, $currentMonthEnd, $previousMonthStart, $previousMonthEnd) {
                $currentMonthCompleted = $staff->assignedBookings->filter(
                    fn (Booking $booking) => filled($booking->scheduled_date)
                        && Carbon::parse($booking->scheduled_date)->betweenIncluded($currentMonthStart, $currentMonthEnd)
                )->count();
                $previousMonthCompleted = $staff->assignedBookings->filter(
                    fn (Booking $booking) => filled($booking->scheduled_date)
                        && Carbon::parse($booking->scheduled_date)->betweenIncluded($previousMonthStart, $previousMonthEnd)
                )->count();

                $staff->current_month_completed = $currentMonthCompleted;
                $staff->trend_change = $currentMonthCompleted - $previousMonthCompleted;

                return $staff;
            })
            ->filter(fn (User $staff) => $staff->current_month_completed > 0)
            ->sort(function (User $left, User $right) {
                if ($left->current_month_completed !== $right->current_month_completed) {
                    return $right->current_month_completed <=> $left->current_month_completed;
                }

                if ($left->trend_change !== $right->trend_change) {
                    return $right->trend_change <=> $left->trend_change;
                }

                return strcmp(
                    strtolower(trim($left->last_name.' '.$left->first_name)),
                    strtolower(trim($right->last_name.' '.$right->first_name))
                );
            })
            ->take(3)
            ->values();
    }

    private function pendingEscalationSummary(): array
    {
        return [
            'warning' => Booking::query()
                ->where('status', 'pending')
                ->where('created_at', '<=', now()->subDay())
                ->where('created_at', '>', now()->subDays(7))
                ->count(),
            'critical' => Booking::query()
                ->where('status', 'pending')
                ->where('created_at', '<=', now()->subDays(7))
                ->count(),
        ];
    }
}
