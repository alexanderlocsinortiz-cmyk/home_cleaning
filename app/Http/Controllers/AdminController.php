<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\Booking;
use App\Models\Device;
use App\Models\DeviceEnrollmentRequest;
use App\Models\Service;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $filters = [
            'barangay' => (string) $request->get('barangay', ''),
            'verification' => (string) $request->get('verification', ''),
            'booking_activity' => (string) $request->get('booking_activity', ''),
            'registration_month' => (string) $request->get('registration_month', ''),
        ];

        $baseCustomerQuery = User::query()->where('role', 'client');

        $stats = [
            'total' => (clone $baseCustomerQuery)->count(),
            'verified' => (clone $baseCustomerQuery)->whereNotNull('email_verified_at')->count(),
            'with_bookings' => (clone $baseCustomerQuery)->whereHas('bookings')->count(),
            'new_this_month' => (clone $baseCustomerQuery)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfDay()])
                ->count(),
        ];

        $customersQuery = User::query()
            ->where('role', 'client')
            ->withCount('bookings')
            ->addSelect([
                'latest_booking_id' => Booking::query()
                    ->select('id')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'latest_booking_date' => Booking::query()
                    ->select('scheduled_date')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'latest_booking_status' => Booking::query()
                    ->select('status')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('scheduled_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('barangay', 'like', "%{$search}%");
                });
            })
            ->when($filters['barangay'] !== '', fn ($query) => $query->where('barangay', $filters['barangay']))
            ->when($filters['verification'] === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($filters['verification'] === 'pending', fn ($query) => $query->whereNull('email_verified_at'))
            ->when($filters['booking_activity'] === 'with_bookings', fn ($query) => $query->has('bookings'))
            ->when($filters['booking_activity'] === 'without_bookings', fn ($query) => $query->doesntHave('bookings'))
            ->when(
                preg_match('/^\d{4}-\d{2}$/', $filters['registration_month']) === 1,
                fn ($query) => $query->whereDate(
                    'created_at',
                    '>=',
                    Carbon::createFromFormat('Y-m', $filters['registration_month'])->startOfMonth()->toDateString()
                )
            )
            ->when(
                preg_match('/^\d{4}-\d{2}$/', $filters['registration_month']) === 1,
                fn ($query) => $query->whereDate(
                    'created_at',
                    '<=',
                    Carbon::createFromFormat('Y-m', $filters['registration_month'])->endOfMonth()->toDateString()
                )
            );

        $filteredCount = (clone $customersQuery)->count();
        $customers = $customersQuery
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $barangays = config('cleanflow.barangays', []);
        $genderOptions = $this->customerGenderOptions();
        $registrationMonthOptions = (clone $baseCustomerQuery)
            ->orderByDesc('created_at')
            ->get(['created_at'])
            ->pluck('created_at')
            ->filter()
            ->map(function ($createdAt) {
                $date = $createdAt instanceof Carbon ? $createdAt : Carbon::parse($createdAt);

                return $date->copy()->startOfMonth();
            })
            ->unique(fn (Carbon $date) => $date->format('Y-m'))
            ->sortByDesc(fn (Carbon $date) => $date->timestamp)
            ->mapWithKeys(fn (Carbon $date) => [$date->format('Y-m') => $date->format('F Y')])
            ->all();

        return view('admin.customers', compact(
            'customers',
            'search',
            'filters',
            'stats',
            'filteredCount',
            'barangays',
            'genderOptions',
            'registrationMonthOptions',
        ));
    }

    public function dashboard()
    {
        $totalEarnings = Booking::where('status', 'completed')->sum('price');
        $recentBookings = Booking::with(['user', 'service'])->latest()->take(6)->get();
        $topStaff = User::where('role', 'staff')
            ->with(['assignedBookings.rating'])
            ->get()
            ->map(function ($s) {
                $ratings = $s->assignedBookings
                    ->where('status', 'completed')
                    ->pluck('rating')
                    ->filter();

                $s->avg_rating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;
                $s->total_ratings = $ratings->count();

                return $s;
            })
            ->sort(function ($left, $right) {
                $leftRank = [
                    $left->total_ratings > 0 ? 1 : 0,
                    $left->avg_rating ?? 0,
                    $left->total_ratings,
                    strtolower(trim($left->last_name . ' ' . $left->first_name)),
                ];

                $rightRank = [
                    $right->total_ratings > 0 ? 1 : 0,
                    $right->avg_rating ?? 0,
                    $right->total_ratings,
                    strtolower(trim($right->last_name . ' ' . $right->first_name)),
                ];

                return $rightRank <=> $leftRank;
            })
            ->values()
            ->take(3);

        return view('admin.dashboard', compact('recentBookings', 'topStaff', 'totalEarnings'));
    }

    public function editCustomerVerification(User $customer)
    {
        $customer = $this->ensureClientCustomer($customer);
        $latestBooking = $customer->bookings()
            ->with('service')
            ->orderByDesc('scheduled_date')
            ->orderByDesc('created_at')
            ->first();

        $customer->loadCount('bookings');

        return view('admin.customers-verification', [
            'customer' => $customer,
            'latestBooking' => $latestBooking,
        ]);
    }

    public function updateCustomerVerification(Request $request, User $customer)
    {
        $customer = $this->ensureClientCustomer($customer);
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(['verified', 'pending'])],
        ]);

        $wasVerified = $customer->email_verified_at !== null;
        $shouldVerify = $validated['verification_status'] === 'verified';

        if ($shouldVerify) {
            $customer->email_verified_at = $customer->email_verified_at ?: now();
        } else {
            $customer->email_verified_at = null;
        }

        $customer->save();

        $isVerified = $customer->email_verified_at !== null;
        $message = match (true) {
            $isVerified && ! $wasVerified => 'Customer marked as verified.',
            ! $isVerified && $wasVerified => 'Customer marked as pending verification.',
            $isVerified => 'Customer remains verified.',
            default => 'Customer remains pending verification.',
        };

        return redirect()
            ->route('admin.customers.verification.edit', $customer)
            ->with('success', $message);
    }

    public function destroy(User $customer)
    {
        $customer = $this->ensureClientCustomer($customer);

        if ($customer->bookings()->exists()) {
            return back()->with('error', 'Customer accounts with booking history are protected from deletion to preserve operational records.');
        }

        $customer->delete();

        return back()->with('success', 'Customer deleted successfully.');
    }

    public function bookings(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $tab = $request->get('tab', 'active') === 'completed' ? 'completed' : 'active';

        $activeBookingsQuery = Booking::with(['user', 'staff', 'service'])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress']);

        $completedBookingsQuery = Booking::with(['user', 'staff', 'service', 'rating'])
            ->whereIn('status', ['completed', 'cancelled']);

        $activeBookings = (clone $activeBookingsQuery)
            ->orderByRaw(
                "CASE WHEN scheduled_date = ? THEN 0 WHEN scheduled_date > ? THEN 1 ELSE 2 END",
                [$today, $today]
            )
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'active_page')
            ->withQueryString();

        $completedBookings = (clone $completedBookingsQuery)
            ->orderByDesc('updated_at')
            ->orderByDesc('scheduled_date')
            ->paginate(10, ['*'], 'completed_page')
            ->withQueryString();

        [$todayStartUtc, $todayEndUtc] = $this->attendanceUtcRange();
        $presentStaffIds = AttendanceLog::where('punch_type', 'in')
            ->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $staffList = User::where('role', 'staff')->get()->map(function($s) use ($presentStaffIds) {
            $s->is_present = in_array($s->id, $presentStaffIds);
            return $s;
        });

        $stats = [
            'total'     => Booking::count(),
            'pending'   => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
        ];

        $queueCounts = [
            'active' => (clone $activeBookingsQuery)->count(),
            'completed' => (clone $completedBookingsQuery)->count(),
            'today' => (clone $activeBookingsQuery)->whereDate('scheduled_date', $today)->count(),
            'upcoming' => (clone $activeBookingsQuery)->whereDate('scheduled_date', '>', $today)->count(),
            'in_progress' => (clone $activeBookingsQuery)->where('status', 'in_progress')->count(),
        ];

        return view('admin.bookings', compact(
            'activeBookings',
            'completedBookings',
            'staffList',
            'stats',
            'tab',
            'queueCounts',
        ));
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $oldStaffId = $booking->staff_id;
        $oldStatus = $booking->status;
        
        $validated = $request->validate([
            'status' => ['required', Rule::in(Booking::statuses())],
            'staff_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
        ]);

        $newStatus = $validated['status'];
        $newStaffId = array_key_exists('staff_id', $validated) ? $validated['staff_id'] : $booking->staff_id;

        if (in_array($oldStatus, ['completed', 'cancelled'], true) && $newStaffId != $oldStaffId) {
            return back()->withErrors([
                'staff_id' => 'Staff assignment cannot be changed after a booking is completed or cancelled.',
            ]);
        }

        if (! $booking->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'This booking cannot be moved from ' . str_replace('_', ' ', $oldStatus) . ' to ' . str_replace('_', ' ', $newStatus) . '.',
            ]);
        }

        if (Booking::requiresAssignedStaffForStatus($newStatus) && ! $newStaffId) {
            return back()->withErrors([
                'staff_id' => 'Please assign a staff member before updating to this status.',
            ]);
        }

        $booking->status = $newStatus;
        $booking->staff_id = $newStaffId;
        $booking->save();

        // Load relationships for email
        $booking->load(['user', 'staff', 'service']);

        // Send email notifications
        try {
            if ($newStatus === 'confirmed') {
                \Mail::to($booking->user->email)
                    ->send(new \App\Mail\BookingConfirmed($booking));
            }

            if ($newStatus === 'in_progress') {
                \Mail::to($booking->user->email)
                    ->send(new \App\Mail\BookingInProgress($booking));
            }

            if ($newStatus === 'completed') {
                \Mail::to($booking->user->email)
                    ->send(new \App\Mail\BookingCompleted($booking));
            }

            if ($newStaffId && $oldStaffId != $newStaffId) {
                \Mail::to($booking->user->email)
                    ->send(new \App\Mail\BookingStaffAssigned($booking));

                Notification::create([
                    'user_id' => $newStaffId,
                    'title'   => 'New Booking Assigned',
                    'message' => 'You have been assigned to booking CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT) . ' on ' . \Carbon\Carbon::parse($booking->scheduled_date)->format('F d, Y') . ' at ' . ucfirst($booking->barangay) . '.',
                    'type'    => 'info',
                    'link'    => '/staff/bookings',
                ]);
            }

            if ($newStatus === 'confirmed' && $booking->staff_id) {
                Notification::create([
                    'user_id' => $booking->staff_id,
                    'title'   => 'Booking Confirmed',
                    'message' => 'Booking CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT) . ' has been confirmed. Please be ready on ' . \Carbon\Carbon::parse($booking->scheduled_date)->format('F d, Y') . '.',
                    'type'    => 'success',
                    'link'    => '/staff/bookings',
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Email sending failed: ' . $e->getMessage());
        }

        $message = 'Booking details updated successfully.';

        if ($newStaffId && $oldStaffId != $newStaffId && $oldStatus !== $newStatus) {
            $message = 'Booking status and staff assignment updated successfully.';
        } elseif ($newStaffId && $oldStaffId != $newStaffId) {
            $message = 'Staff assignment updated successfully.';
        } elseif ($oldStatus !== $newStatus) {
            $message = 'Booking status updated successfully.';
        }

        return back()->with('success', $message);
    }

    public function attendance()
    {
        [$todayStartUtc, $todayEndUtc, $attendanceDate] = $this->attendanceUtcRange();
        $staff = \App\Models\User::where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $devices = Device::orderBy('name')->get();
        $recentEnrollmentRequests = DeviceEnrollmentRequest::with(['device', 'user', 'requestedBy'])
            ->latest()
            ->take(10)
            ->get();

        $attendance = $staff->map(function ($s) use ($todayStartUtc, $todayEndUtc) {
            $timeIn = AttendanceLog::where('user_id', $s->id)
                ->where('punch_type', 'in')
                ->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])
                ->latest('logged_at')
                ->first();

            $timeOut = AttendanceLog::where('user_id', $s->id)
                ->where('punch_type', 'out')
                ->whereBetween('logged_at', [$todayStartUtc, $todayEndUtc])
                ->latest('logged_at')
                ->first();

            return [
                'id'         => $s->id,
                'name'       => $s->first_name . ' ' . $s->last_name,
                'email'      => $s->email,
                'barangay'   => $s->barangay,
                'status'     => $this->attendanceStatusFor($timeIn),
                'time_in'    => $this->formatAttendanceTime($timeIn?->logged_at),
                'time_out'   => $this->formatAttendanceTime($timeOut?->logged_at),
                'is_present' => $timeIn !== null,
            ];
        });

        $presentCount = $attendance->where('is_present', true)->count();
        $absentCount = $attendance->where('is_present', false)->count();
        $lateCount = $attendance->where('status', 'late')->count();

        return view('admin.attendance', compact(
            'attendance',
            'presentCount',
            'absentCount',
            'lateCount',
            'devices',
            'staff',
            'recentEnrollmentRequests',
            'attendanceDate',
        ));
    }

    public function storeAttendanceDevice(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:devices,serial_number'],
            'location' => ['nullable', 'string', 'max:150'],
        ]);

        $device = Device::create([
            'name' => $validated['name'],
            'serial_number' => $validated['serial_number'],
            'location' => $validated['location'] ?? null,
            'api_token' => $this->generateUniqueDeviceToken(),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.attendance')
            ->with('success', 'Biometric device created successfully.')
            ->with('generated_device_token', $device->api_token)
            ->with('generated_device_name', $device->name)
            ->with('generated_device_serial', $device->serial_number);
    }

    public function storeAttendanceEnrollmentRequest(Request $request)
    {
        $validated = $request->validate([
            'device_id' => [
                'required',
                Rule::exists('devices', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
            'template_id' => ['required', 'integer', 'min:1', 'max:162'],
        ]);

        $staff = User::where('role', 'staff')->findOrFail($validated['user_id']);

        if ($staff->fingerprint_template_id !== null) {
            return back()->withErrors([
                'user_id' => $staff->full_name . ' already has fingerprint slot #' . $staff->fingerprint_template_id . '.',
            ])->withInput();
        }

        $deviceBusy = DeviceEnrollmentRequest::where('device_id', $validated['device_id'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($deviceBusy) {
            return back()->withErrors([
                'device_id' => 'This device already has an enrollment in progress or waiting in queue.',
            ])->withInput();
        }

        $templateTaken = User::where('fingerprint_template_id', $validated['template_id'])->exists();

        if ($templateTaken) {
            return back()->withErrors([
                'template_id' => 'That fingerprint slot is already assigned to a staff member.',
            ])->withInput();
        }

        $templateQueued = DeviceEnrollmentRequest::where('template_id', $validated['template_id'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($templateQueued) {
            return back()->withErrors([
                'template_id' => 'That fingerprint slot is already waiting in the enrollment queue.',
            ])->withInput();
        }

        DeviceEnrollmentRequest::create([
            'device_id' => $validated['device_id'],
            'user_id' => $staff->id,
            'requested_by' => auth()->id(),
            'template_id' => $validated['template_id'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('admin.attendance')
            ->with('success', 'Fingerprint enrollment request created. Ask the staff member to scan the same finger twice on the device.');
    }

    public function rotateAttendanceDeviceToken(Device $device)
    {
        $device->update([
            'api_token' => $this->generateUniqueDeviceToken(),
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.attendance')
            ->with('success', 'Device token rotated successfully.')
            ->with('generated_device_token', $device->api_token)
            ->with('generated_device_name', $device->name)
            ->with('generated_device_serial', $device->serial_number);
    }

    public function attendanceHistory(Request $request)
    {
        $attendanceOffset = $this->attendanceTimezoneOffset();
        $localDateExpression = "DATE(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}'))";
        $lateRankExpression = "MAX(CASE WHEN punch_type = 'in' AND TIME(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}')) > '08:00:00' THEN 2 WHEN punch_type = 'in' THEN 1 ELSE 0 END)";
        $lateCondition = "punch_type = 'in' AND TIME(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}')) > '08:00:00'";
        $presentCondition = "(punch_type != 'in' OR TIME(CONVERT_TZ(logged_at, '+00:00', '{$attendanceOffset}')) <= '08:00:00')";

        $query = AttendanceLog::with(['user', 'device'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'staff');
            });

        $dateFrom = null;
        $dateTo = null;

        if ($request->period) {
            $period = $request->period;
            $now = Carbon::now($this->attendanceTimezone());

            switch ($period) {
                case 'today':
                    $dateFrom = $now->copy()->toDateString();
                    $dateTo = $now->copy()->toDateString();
                    break;
                case 'yesterday':
                    $dateFrom = $now->copy()->subDay()->toDateString();
                    $dateTo = $now->copy()->subDay()->toDateString();
                    break;
                case 'this_week':
                    $dateFrom = $now->copy()->startOfWeek()->toDateString();
                    $dateTo = $now->copy()->endOfWeek()->toDateString();
                    break;
                case 'last_week':
                    $dateFrom = $now->copy()->subWeek()->startOfWeek()->toDateString();
                    $dateTo = $now->copy()->subWeek()->endOfWeek()->toDateString();
                    break;
                case 'this_month':
                    $dateFrom = $now->copy()->startOfMonth()->toDateString();
                    $dateTo = $now->copy()->endOfMonth()->toDateString();
                    break;
                case 'last_month':
                    $dateFrom = $now->copy()->subMonth()->startOfMonth()->toDateString();
                    $dateTo = $now->copy()->subMonth()->endOfMonth()->toDateString();
                    break;
            }
        }

        if ($request->staff_id) {
            $query->where('user_id', $request->staff_id);
        }

        $dateFrom = $request->date_from ?: $dateFrom;
        $dateTo = $request->date_to ?: $dateTo;

        if ($dateFrom) {
            $query->where('logged_at', '>=', $this->localDateToUtc($dateFrom));
        }

        if ($dateTo) {
            $query->where('logged_at', '<=', $this->localDateToUtc($dateTo, true));
        }

        if ($request->status === 'late') {
            $query->whereRaw($lateCondition);
        } elseif ($request->status === 'present') {
            $query->whereRaw($presentCondition);
        }

        if ($request->punch_type) {
            $query->where('punch_type', $request->punch_type);
        }

        $logs = $query->orderByDesc('logged_at')->paginate(15, ['*'], 'logs_page')->withQueryString();
        $logs->getCollection()->transform(function (AttendanceLog $log) {
            $loggedAtLocal = $log->logged_at->copy()->timezone($this->attendanceTimezone());
            $log->display_logged_at_date = $loggedAtLocal->format('M d, Y');
            $log->display_logged_at_time = $loggedAtLocal->format('h:i:s A');
            $log->display_status = $this->attendanceStatusFromTimestamp($loggedAtLocal, $log->punch_type);

            return $log;
        });

        $summaryQuery = AttendanceLog::with('user')
            ->whereHas('user', function ($q) {
                $q->where('role', 'staff');
            })
            ->selectRaw("user_id, {$localDateExpression} as date,
                MIN(CASE WHEN punch_type = 'in' THEN logged_at END) as time_in,
                MAX(CASE WHEN punch_type = 'out' THEN logged_at END) as time_out,
                {$lateRankExpression} as status_rank")
            ->groupBy('user_id', DB::raw($localDateExpression));

        if ($request->staff_id) {
            $summaryQuery->where('user_id', $request->staff_id);
        }

        if ($dateFrom) {
            $summaryQuery->where('logged_at', '>=', $this->localDateToUtc($dateFrom));
        }

        if ($dateTo) {
            $summaryQuery->where('logged_at', '<=', $this->localDateToUtc($dateTo, true));
        }

        if ($request->status === 'late') {
            $summaryQuery->having('status_rank', '=', 2);
        } elseif ($request->status === 'present') {
            $summaryQuery->having('status_rank', '=', 1);
        }

        $summaries = $summaryQuery->orderByDesc('date')->paginate(15, ['*'], 'summaries_page')->withQueryString();
        $summaries->getCollection()->transform(function ($summary) {
            $timeInLocal = $summary->time_in
                ? Carbon::parse($summary->time_in, 'UTC')->timezone($this->attendanceTimezone())
                : null;
            $timeOutLocal = $summary->time_out
                ? Carbon::parse($summary->time_out, 'UTC')->timezone($this->attendanceTimezone())
                : null;

            $summary->display_date = Carbon::parse($summary->date, $this->attendanceTimezone());
            $summary->display_time_in = $timeInLocal?->format('h:i A');
            $summary->display_time_out = $timeOutLocal?->format('h:i A');
            $summary->display_status = $timeInLocal
                ? $this->attendanceStatusFromTimestamp($timeInLocal, 'in')
                : 'unknown';
            $summary->hours_worked = null;

            if ($timeInLocal && $timeOutLocal) {
                $diff = $timeInLocal->diff($timeOutLocal);
                $summary->hours_worked = $diff->h . 'h ' . $diff->i . 'm';
            }

            return $summary;
        });

        $staffList = User::where('role', 'staff')->get();

        $totalLogs = AttendanceLog::whereHas('user', function ($q) {
            $q->where('role', 'staff');
        })->count();

        $totalLate = AttendanceLog::whereHas('user', function ($q) use ($lateCondition) {
            $q->where('role', 'staff');
        })->whereRaw($lateCondition)->count();

        return view('admin.attendance-history', compact(
            'logs', 'summaries', 'staffList', 'totalLogs', 'totalLate'
        ));
    }

    private function generateUniqueDeviceToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Device::where('api_token', $token)->exists());

        return $token;
    }

    private function attendanceTimezone(): string
    {
        return config('cleanflow.attendance_timezone', 'Asia/Manila');
    }

    private function attendanceTimezoneOffset(): string
    {
        return Carbon::now($this->attendanceTimezone())->format('P');
    }

    private function attendanceUtcRange(?Carbon $localReference = null): array
    {
        $localReference = $localReference
            ? $localReference->copy()->timezone($this->attendanceTimezone())
            : Carbon::now($this->attendanceTimezone());

        return [
            $localReference->copy()->startOfDay()->utc(),
            $localReference->copy()->endOfDay()->utc(),
            $localReference,
        ];
    }

    private function localDateToUtc(string $date, bool $endOfDay = false): Carbon
    {
        $localDate = Carbon::parse($date, $this->attendanceTimezone());

        return ($endOfDay ? $localDate->endOfDay() : $localDate->startOfDay())->utc();
    }

    private function formatAttendanceTime(?Carbon $timestamp): ?string
    {
        return $timestamp
            ? $timestamp->copy()->timezone($this->attendanceTimezone())->format('h:i A')
            : null;
    }

    private function attendanceStatusFor(?AttendanceLog $timeIn): string
    {
        return $timeIn
            ? $this->attendanceStatusFromTimestamp($timeIn->logged_at->copy()->timezone($this->attendanceTimezone()))
            : 'absent';
    }

    private function attendanceStatusFromTimestamp(?Carbon $loggedAtLocal, string $punchType = 'in'): string
    {
        if (! $loggedAtLocal) {
            return 'absent';
        }

        if ($punchType !== 'in') {
            return 'present';
        }

        $cutoff = $loggedAtLocal->copy()->startOfDay()->setTime(8, 0);

        return $loggedAtLocal->greaterThan($cutoff) ? 'late' : 'present';
    }

    public function reports()
    {
        $totalBookings = Booking::count();
        $completedBookings = Booking::where('status', 'completed')->count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $inProgressBookings = Booking::where('status', 'in_progress')->count();
        $totalRevenue = Booking::where('status', 'completed')->sum('price');

        $revenueByType = Booking::query()
            ->join('services', 'services.slug', '=', 'bookings.service_type')
            ->where('bookings.status', 'completed')
            ->where('services.is_active', true)
            ->selectRaw('services.slug as service_type, services.name as service_name, COUNT(bookings.id) as total, SUM(bookings.price) as revenue')
            ->groupBy('services.slug', 'services.name')
            ->orderByDesc('revenue')
            ->get();

        $bookingsByType = Booking::query()
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

        $staffPerformance = User::where('role', 'staff')
            ->with(['assignedBookings.rating'])
            ->get()
            ->map(function($staff) {
                $assigned = $staff->assignedBookings;
                $completed = $assigned->where('status', 'completed');
                $ratings = $assigned->pluck('rating')->filter();
                $staff->total_assigned = $assigned->count();
                $staff->total_completed = $completed->count();
                $staff->completion_rate = $assigned->count() > 0
                    ? round(($completed->count() / $assigned->count()) * 100, 1)
                    : 0;
                $staff->avg_rating = $ratings->count() > 0
                    ? round($ratings->avg('stars'), 1)
                    : null;
                $staff->total_ratings = $ratings->count();
                return $staff;
            });

        $recentBookings = Booking::with(['user', 'staff', 'service'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $monthExpression = Booking::query()->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $monthlyBookings = Booking::selectRaw("$monthExpression as month, COUNT(*) as total, SUM(CASE WHEN status='completed' THEN price ELSE 0 END) as revenue")
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('admin.reports', compact(
            'totalBookings', 'completedBookings', 'pendingBookings',
            'cancelledBookings', 'confirmedBookings', 'inProgressBookings',
            'totalRevenue', 'revenueByType', 'bookingsByType',
            'statusSummary', 'invalidServiceBookings',
            'staffPerformance', 'recentBookings', 'monthlyBookings'
        ));
    }

    public function serviceAreas()
    {
        $barangays = config('cleanflow.service_areas', []);
        return view('admin.service-areas', compact('barangays'));
    }

    private function ensureClientCustomer(User $customer): User
    {
        abort_if($customer->role !== 'client', 404);

        return $customer;
    }

    private function customerGenderOptions(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
            'prefer_not_to_say' => 'Prefer not to say',
        ];
    }
}
