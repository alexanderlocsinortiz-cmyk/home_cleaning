<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class StaffPortalController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        $assignedBookings = Booking::with(['user', 'rating', 'service'])
            ->where('staff_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        $totalBookings     = Booking::where('staff_id', $user->id)->count();
        $completedBookings = Booking::where('staff_id', $user->id)->where('status', 'completed')->count();
        $inProgress        = Booking::where('staff_id', $user->id)->where('status', 'in_progress')->count();
        $confirmedBookings = Booking::where('staff_id', $user->id)->where('status', 'confirmed')->count();

        // Ratings
        $allBookings = Booking::with('rating')->where('staff_id', $user->id)->get();
        $ratings = $allBookings->pluck('rating')->filter();
        $avgRating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;
        $totalRatings = $ratings->count();

        // Earnings (completed bookings only)
        $totalEarnings = Booking::where('staff_id', $user->id)
            ->where('status', 'completed')
            ->sum('price');

        return view('staff.welcome', compact(
            'user', 'assignedBookings', 'totalBookings',
            'completedBookings', 'inProgress', 'confirmedBookings',
            'avgRating', 'totalRatings', 'totalEarnings'
        ));
    }

    public function serviceAreas()
    {
        $barangays = config('cleanflow.service_areas', []);

        $stats = [
            'barangays'    => count($barangays),
            'customers'    => \App\Models\User::where('role', 'client')->count(),
            'staff'        => \App\Models\User::where('role', 'staff')->count(),
            'satisfaction' => (function() {
                $avg = \App\Models\Rating::avg('stars');
                return $avg ? round(($avg / 5) * 100) : 98;
            })(),
        ];

        return view('staff.service-areas', compact('barangays', 'stats'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,completed'],
        ]);

        $booking = Booking::where('id', $id)
            ->where('staff_id', Auth::id())
            ->firstOrFail();

        if (! $booking->canBeUpdatedByStaffTo($validated['status'])) {
            return back()->withErrors([
                'status' => $validated['status'] === 'completed'
                    ? 'A booking must be in progress before it can be marked as completed.'
                    : 'Only confirmed bookings can be marked as in progress.',
            ]);
        }

        $booking->update(['status' => $validated['status']]);

        return back()->with('success', $validated['status'] === 'completed'
            ? 'Booking marked as completed.'
            : 'Booking marked as in progress.');
    }

    public function profile()
    {
        $user = Auth::user();
        $barangays = config('cleanflow.barangays');

        return view('staff.profile', compact('user', 'barangays'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'barangay'   => ['required', 'in:' . implode(',', array_keys(config('cleanflow.barangays')))],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function bookings(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $query = Booking::with(['user', 'rating', 'service'])
            ->where('staff_id', $user->id)
            ->orderBy('scheduled_date', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10);

        $counts = [
            'all'         => Booking::where('staff_id', $user->id)->count(),
            'confirmed'   => Booking::where('staff_id', $user->id)->where('status', 'confirmed')->count(),
            'in_progress' => Booking::where('staff_id', $user->id)->where('status', 'in_progress')->count(),
            'completed'   => Booking::where('staff_id', $user->id)->where('status', 'completed')->count(),
            'cancelled'   => Booking::where('staff_id', $user->id)->where('status', 'cancelled')->count(),
        ];

        return view('staff.bookings', compact('bookings', 'status', 'counts', 'user'));
    }

    public function performance()
    {
        $user = Auth::user();

        // All completed bookings with ratings
        $completedBookings = Booking::with(['rating', 'service', 'user'])
            ->where('staff_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Rating stats
        $ratings = $completedBookings->pluck('rating')->filter();
        $avgRating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;
        $totalRatings = $ratings->count();

        // Star breakdown
        $starBreakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $starBreakdown[$i] = $ratings->where('stars', $i)->count();
        }

        // Overall stats
        $totalBookings     = Booking::where('staff_id', $user->id)->count();
        $completedCount    = $completedBookings->count();
        $completionRate    = $totalBookings > 0 ? round(($completedCount / $totalBookings) * 100, 1) : 0;
        $totalEarnings     = $completedBookings->sum('price');

        // Ranking among all staff
        $allStaff = User::where('role', 'staff')
            ->with(['assignedBookings.rating'])
            ->get()
            ->map(function($staff) {
                $completed = $staff->assignedBookings->where('status', 'completed');
                $ratings = $staff->assignedBookings->pluck('rating')->filter();
                $staff->avg_rating = $ratings->count() > 0 ? $ratings->avg('stars') : 0;
                $staff->completed_count = $completed->count();
                return $staff;
            })
            ->sortByDesc('avg_rating')
            ->values();

        $myRank = $allStaff->search(fn($s) => $s->id === $user->id) + 1;
        $totalStaff = $allStaff->count();

        return view('staff.performance', compact(
            'user', 'completedBookings', 'ratings', 'avgRating',
            'totalRatings', 'starBreakdown', 'totalBookings',
            'completedCount', 'completionRate', 'totalEarnings',
            'myRank', 'totalStaff'
        ));
    }

    public function schedule()
    {
        $user = Auth::user();

        $bookings = Booking::with(['user', 'service'])
            ->where('staff_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereDate('scheduled_date', '>=', now()->startOfMonth())
            ->whereDate('scheduled_date', '<=', now()->endOfMonth()->addMonth())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // Group bookings by date
        $bookingsByDate = $bookings->groupBy(function($booking) {
            return \Carbon\Carbon::parse($booking->scheduled_date)->format('Y-m-d');
        });

        $currentMonth = now()->format('Y-m');

        return view('staff.schedule', compact('bookings', 'bookingsByDate', 'currentMonth', 'user'));
    }

    public function notifications()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
        return view('staff.notifications', compact('notifications', 'unreadCount', 'user'));
    }

    public function markAsRead($id)
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read_at' => now()]);
        return back();
    }

    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return back()->with('success', 'All notifications have been marked as read.');
    }
}
