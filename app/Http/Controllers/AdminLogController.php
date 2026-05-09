<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\BookingActivityLog;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'source' => in_array($request->get('source'), ['all', 'bookings', 'attendance'], true)
                ? $request->get('source')
                : 'all',
            'search' => trim((string) $request->get('search', '')),
            'action' => (string) $request->get('action', ''),
            'actor_role' => (string) $request->get('actor_role', ''),
        ];

        $logsQuery = BookingActivityLog::query()
            ->with(['booking.user', 'actor'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $bookingIdSearch = preg_replace('/\D/', '', $search);

                $query->where(function ($query) use ($search, $bookingIdSearch) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('actor_name', 'like', "%{$search}%")
                        ->orWhereHas('booking', function ($bookingQuery) use ($search, $bookingIdSearch) {
                            $bookingQuery->where('barangay', 'like', "%{$search}%")
                                ->when($bookingIdSearch !== '', fn ($query) => $query->orWhere('id', (int) $bookingIdSearch))
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['actor_role'] !== '', fn ($query) => $query->where('actor_role', $filters['actor_role']));

        $bookingLogs = (clone $logsQuery)
            ->latest()
            ->paginate($filters['source'] === 'bookings' ? 15 : 8, ['*'], 'booking_page')
            ->withQueryString();

        $attendanceLogsQuery = AttendanceLog::query()
            ->with(['user', 'device'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('punch_type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('device', function ($deviceQuery) use ($search) {
                            $deviceQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('serial_number', 'like', "%{$search}%");
                        });
                });
            });

        $attendanceLogs = (clone $attendanceLogsQuery)
            ->latest('logged_at')
            ->latest()
            ->paginate($filters['source'] === 'attendance' ? 15 : 8, ['*'], 'attendance_page')
            ->withQueryString();

        $actions = BookingActivityLog::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $actorRoles = BookingActivityLog::query()
            ->select('actor_role')
            ->whereNotNull('actor_role')
            ->distinct()
            ->orderBy('actor_role')
            ->pluck('actor_role');

        $stats = [
            'booking_total' => BookingActivityLog::count(),
            'attendance_total' => AttendanceLog::count(),
            'booking_today' => BookingActivityLog::whereDate('created_at', today())->count(),
            'attendance_today' => AttendanceLog::whereDate('logged_at', today())->count(),
            'booking_filtered' => (clone $logsQuery)->count(),
            'attendance_filtered' => (clone $attendanceLogsQuery)->count(),
        ];

        return view('admin.logs', compact('bookingLogs', 'attendanceLogs', 'actions', 'actorRoles', 'filters', 'stats'));
    }
}
