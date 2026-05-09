<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AttendanceHelpers;
use App\Models\AttendanceLog;
use App\Models\Booking;
use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use AttendanceHelpers;

    public function dashboard()
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

        return view('admin.dashboard', compact(
            'dashboardNow', 'dashboardStats', 'recentBookings', 'topStaff',
            'totalEarnings', 'pendingEscalationSummary', 'tomorrowJobs',
            'unassignedBookings', 'presentStaffCount'
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
            'total_bookings'       => (int) ($bookingCounts->total_bookings ?? 0),
            'pending_bookings'     => (int) ($bookingCounts->pending_bookings ?? 0),
            'confirmed_bookings'   => (int) ($bookingCounts->confirmed_bookings ?? 0),
            'completed_bookings'   => (int) ($bookingCounts->completed_bookings ?? 0),
            'in_progress_bookings' => (int) ($bookingCounts->in_progress_bookings ?? 0),
            'total_earnings'       => (float) ($bookingCounts->total_earnings ?? 0),
            'customers'            => (int) ($userCounts->customers ?? 0),
            'verified_customers'   => (int) ($userCounts->verified_customers ?? 0),
            'staff'                => (int) ($userCounts->staff ?? 0),
            'active_devices'       => Device::where('is_active', true)->count(),
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
