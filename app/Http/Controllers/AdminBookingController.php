<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AttendanceHelpers;
use App\Models\AttendanceLog;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminBookingController extends Controller
{
    use AttendanceHelpers;

    public function bookings(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $tab = $request->get('tab', 'active') === 'completed' ? 'completed' : 'active';
        $activeFilter = in_array($request->get('filter'), ['today', 'unassigned', 'overdue', 'review', 'in_progress'], true)
            ? $request->get('filter')
            : '';

        $activeBookingsQuery = Booking::with(['user', 'staff', 'service', 'reviewedBy', 'preferredStaff'])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress']);

        $completedBookingsQuery = Booking::with(['user', 'staff', 'service', 'rating', 'reviewedBy', 'preferredStaff'])
            ->whereIn('status', ['completed', 'cancelled']);

        $filteredActiveBookingsQuery = (clone $activeBookingsQuery)
            ->when($activeFilter === 'today', fn ($query) => $query->whereDate('scheduled_date', $today))
            ->when($activeFilter === 'unassigned', fn ($query) => $query->whereNull('staff_id'))
            ->when($activeFilter === 'overdue', fn ($query) => $query->where('status', 'pending')->where('created_at', '<=', now()->subDay()))
            ->when($activeFilter === 'review', fn ($query) => $query->where('manual_review_status', 'pending'))
            ->when($activeFilter === 'in_progress', fn ($query) => $query->where('status', 'in_progress'));

        $activeBookings = $filteredActiveBookingsQuery
            ->orderByRaw("CASE WHEN manual_review_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw(
                'CASE WHEN scheduled_date = ? THEN 0 WHEN scheduled_date > ? THEN 1 ELSE 2 END',
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

        $staffList = User::where('role', 'staff')->get()->map(function ($s) use ($presentStaffIds) {
            $s->is_present = in_array($s->id, $presentStaffIds);

            return $s;
        });

        $busyStaffIdsBySlot = Booking::query()
            ->whereIn('status', Booking::scheduleConflictStatuses())
            ->whereNotNull('staff_id')
            ->get(['id', 'staff_id', 'scheduled_date', 'scheduled_time'])
            ->groupBy(fn (Booking $booking) => Booking::scheduleSlotKey($booking->scheduled_date, $booking->scheduled_time))
            ->map(fn ($slotBookings) => $slotBookings->pluck('staff_id')
                ->map(fn ($staffId) => (int) $staffId)
                ->unique()
                ->values()
                ->all());

        $activeBookings->getCollection()->transform(function (Booking $booking) use ($busyStaffIdsBySlot, $presentStaffIds) {
            $slotKey = Booking::scheduleSlotKey($booking->scheduled_date, $booking->scheduled_time);
            $busyStaffIds = array_values(array_filter(
                $busyStaffIdsBySlot->get($slotKey, []),
                fn (int $staffId) => $staffId !== (int) $booking->staff_id
            ));

            $booking->busy_staff_ids = $busyStaffIds;
            $booking->available_present_staff_count = count(array_diff($presentStaffIds, $busyStaffIds));
            $booking->pending_escalation = $this->pendingEscalationFor($booking);

            return $booking;
        });

        $stats = [
            'total' => Booking::count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
        ];

        $queueCounts = [
            'active' => (clone $activeBookingsQuery)->count(),
            'completed' => (clone $completedBookingsQuery)->count(),
            'today' => (clone $activeBookingsQuery)->whereDate('scheduled_date', $today)->count(),
            'upcoming' => (clone $activeBookingsQuery)->whereDate('scheduled_date', '>', $today)->count(),
            'in_progress' => (clone $activeBookingsQuery)->where('status', 'in_progress')->count(),
            'review_pending' => (clone $activeBookingsQuery)->where('manual_review_status', 'pending')->count(),
            'unassigned' => (clone $activeBookingsQuery)->whereNull('staff_id')->count(),
        ];
        $pendingEscalationSummary = $this->pendingEscalationSummary();

        return view('admin.bookings', compact(
            'activeBookings', 'completedBookings', 'staffList', 'stats',
            'tab', 'queueCounts', 'pendingEscalationSummary', 'activeFilter',
        ));
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $oldStaffId = $booking->staff_id;
        $oldStatus = $booking->status;
        $oldPaymentStatus = $booking->payment_status;

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
                'status' => 'This booking cannot be moved from '.str_replace('_', ' ', $oldStatus).' to '.str_replace('_', ' ', $newStatus).'.',
            ]);
        }

        if (Booking::requiresAssignedStaffForStatus($newStatus) && ! $newStaffId) {
            return back()->withErrors([
                'staff_id' => 'Please assign a staff member before updating to this status.',
            ]);
        }

        $statusChanged = $newStatus !== $oldStatus;
        $staffAssignmentChanged = (int) ($newStaffId ?? 0) !== (int) ($oldStaffId ?? 0);

        if ($booking->requiresManualReview() && ($statusChanged || $staffAssignmentChanged)) {
            return back()->withErrors([
                'status' => 'This booking is flagged for manual review. Approve or block it before changing status or staff assignment.',
            ]);
        }

        if ($booking->isReviewBlocked() && ($statusChanged || $staffAssignmentChanged)) {
            return back()->withErrors([
                'status' => 'This booking was blocked during manual review and can no longer move through the operational workflow.',
            ]);
        }

        if (
            $newStaffId
            && Booking::staffHasScheduleConflict($newStaffId, $booking->scheduled_date, $booking->scheduled_time, $booking->id)
        ) {
            return back()->withErrors([
                'staff_id' => 'This staff member is already assigned to another active booking at the same date and time.',
            ]);
        }

        if ($booking->preferred_staff_id && $newStaffId) {
            $booking->preferred_staff_status = (int) $newStaffId === (int) $booking->preferred_staff_id
                ? 'assigned'
                : 'alternate_assigned';
        }

        $paymentStatusChanged = false;

        if (
            $newStatus === 'completed'
            && $booking->payment_method === 'on_site_cash'
            && $booking->payment_status !== 'paid'
        ) {
            $booking->payment_status = 'paid';
            $booking->payment_reference = $booking->payment_reference ?: Booking::generatePaymentReference($booking->payment_method);
            $booking->paid_at = $booking->paid_at ?: now();
            $paymentStatusChanged = true;
        }

        $booking->status = $newStatus;
        $booking->staff_id = $newStaffId;
        $booking->save();

        $actor = auth()->user();

        if ($statusChanged) {
            $booking->logActivity($actor, 'status_updated', 'Status changed from '.str_replace('_', ' ', $oldStatus).' to '.str_replace('_', ' ', $newStatus).'.', [
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
            ]);
        }

        if ($staffAssignmentChanged) {
            $booking->logActivity($actor, 'staff_assigned', 'Cleaner assignment updated.', [
                'from_staff_id' => $oldStaffId,
                'to_staff_id' => $newStaffId,
            ]);
        }

        if ($paymentStatusChanged) {
            $booking->logActivity($actor, 'payment_updated', 'Payment auto-recorded as paid on completion.', [
                'from_payment_status' => $oldPaymentStatus,
                'to_payment_status' => $booking->payment_status,
            ]);
        }

        $booking->load(['user', 'staff', 'service', 'preferredStaff']);

        try {
            if ($newStatus === 'confirmed') {
                \Mail::to($booking->user->email)->send(new \App\Mail\BookingConfirmed($booking));
            }

            if ($newStatus === 'in_progress') {
                \Mail::to($booking->user->email)->send(new \App\Mail\BookingInProgress($booking));
            }

            if ($newStatus === 'completed') {
                \Mail::to($booking->user->email)->send(new \App\Mail\BookingCompleted($booking));
            }

            if ($newStaffId && $oldStaffId != $newStaffId) {
                \Mail::to($booking->user->email)->send(new \App\Mail\BookingStaffAssigned($booking));

                $this->createNotification([
                    'user_id' => $newStaffId,
                    'title' => 'New Booking Assigned',
                    'message' => 'You have been assigned to booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' scheduled for '.Carbon::parse($booking->scheduled_date)->format('F d, Y').' in '.ucfirst($booking->barangay).'.',
                    'type' => 'info',
                    'link' => route('staff.bookings'),
                ]);

                $this->createClientAssignmentNotification($booking);
            }

            if ($newStatus === 'confirmed' && $booking->staff_id && $oldStatus !== 'confirmed' && $oldStaffId == $newStaffId) {
                $this->createClientStatusNotification($booking, 'confirmed');
            }

            if ($newStatus === 'confirmed' && $booking->staff_id) {
                $this->createNotification([
                    'user_id' => $booking->staff_id,
                    'title' => 'Booking Confirmed',
                    'message' => 'Booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' has been confirmed. Please prepare for '.Carbon::parse($booking->scheduled_date)->format('F d, Y').'.',
                    'type' => 'success',
                    'link' => route('staff.bookings'),
                ]);
            }

            if ($newStatus === 'in_progress' && $oldStatus !== 'in_progress') {
                $this->createClientStatusNotification($booking, 'in_progress');
            }

            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $this->createClientStatusNotification($booking, 'completed');
            }

            if ($paymentStatusChanged && $oldPaymentStatus !== $booking->payment_status) {
                $this->createClientPaymentNotification($booking);
            }
        } catch (\Exception $e) {
            \Log::error('Email sending failed: '.$e->getMessage());
        }

        Cache::forget('admin:pending_bookings_count');

        $message = 'Booking details have been updated.';

        if ($newStaffId && $oldStaffId != $newStaffId && $oldStatus !== $newStatus) {
            $message = 'Booking status and cleaner assignment have been updated.';
        } elseif ($newStaffId && $oldStaffId != $newStaffId) {
            $message = 'Cleaner assignment has been updated.';
        } elseif ($oldStatus !== $newStatus) {
            $message = 'Booking status has been updated.';
        }

        return back()->with('success', $message);
    }

    public function updateBookingPayment(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(Booking::paymentStatuses())],
        ]);

        $oldPaymentStatus = $booking->payment_status;
        $booking->payment_status = $validated['payment_status'];

        if ($booking->payment_status === 'paid') {
            $booking->payment_reference = $booking->payment_reference ?: Booking::generatePaymentReference($booking->payment_method);
            $booking->paid_at = $booking->paid_at ?: now();
        } else {
            $booking->paid_at = null;

            if ($booking->payment_method === 'on_site_cash') {
                $booking->payment_reference = null;
            }
        }

        $booking->save();
        $booking->load('user');

        if ($oldPaymentStatus !== $booking->payment_status) {
            $booking->logActivity(auth()->user(), 'payment_updated', 'Payment status changed to '.$booking->payment_status.'.', [
                'from_payment_status' => $oldPaymentStatus,
                'to_payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
            ]);
            $this->createClientPaymentNotification($booking);
        }

        return back()->with('success', 'Payment status updated successfully.');
    }

    public function updateBookingReview(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'review_status' => ['required', Rule::in(['approved', 'blocked'])],
        ]);

        if (empty($booking->risk_reasons) && $booking->manual_review_status === 'not_required') {
            return back()->with('error', 'This booking does not require manual review.');
        }

        $reviewStatus = $validated['review_status'];

        $booking->manual_review_status = $reviewStatus;
        $booking->reviewed_by = auth()->id();
        $booking->reviewed_at = now();

        if ($reviewStatus === 'blocked') {
            $booking->staff_id = null;

            if (! in_array($booking->status, ['completed', 'cancelled'], true)) {
                $booking->status = 'cancelled';
            }
        }

        $booking->save();

        $booking->logActivity(auth()->user(), 'review_updated', 'Manual review decision: '.$reviewStatus.'.', [
            'review_status' => $reviewStatus,
        ]);

        $message = $reviewStatus === 'approved'
            ? 'Booking cleared for normal scheduling and confirmation.'
            : 'Booking blocked during manual review and removed from the active queue.';

        return back()->with('success', $message);
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

    private function pendingEscalationFor(Booking $booking): ?array
    {
        if ($booking->status !== 'pending' || ! $booking->created_at) {
            return null;
        }

        $ageInHours = max(1, (int) ceil($booking->created_at->diffInHours(now())));
        $ageInDays = (int) floor($ageInHours / 24);

        if ($ageInHours > 24 && $ageInHours < 168) {
            return [
                'label' => 'Warning',
                'class' => 'bg-amber-100 text-amber-700',
                'age_label' => $ageInDays > 0 ? $ageInDays.'d old' : $ageInHours.'h old',
            ];
        }

        if ($ageInHours >= 168) {
            return [
                'label' => 'Critical',
                'class' => 'bg-red-100 text-red-700',
                'age_label' => $ageInDays.'d old',
            ];
        }

        return null;
    }

    private function createClientAssignmentNotification(Booking $booking): void
    {
        if (! $booking->staff) {
            return;
        }

        $bookingCode = 'CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT);
        $scheduledDate = Carbon::parse($booking->scheduled_date)->format('F d, Y');
        $scheduledTime = Carbon::parse($booking->scheduled_time)->format('h:i A');
        $title = 'Cleaner assigned';
        $message = $booking->staff->full_name.' has been assigned to booking '.$bookingCode.' on '.$scheduledDate.' at '.$scheduledTime.'. You can review the cleaner details from your booking page.';
        $type = 'info';

        if ($booking->preferredStaff && (int) $booking->staff_id === (int) $booking->preferred_staff_id) {
            $title = 'Preferred cleaner assigned';
            $message = 'Your preferred cleaner '.$booking->preferredStaff->full_name.' has been assigned to booking '.$bookingCode.' on '.$scheduledDate.' at '.$scheduledTime.'.';
            $type = 'success';
        } elseif ($booking->preferredStaff) {
            $title = 'Alternative cleaner assigned';
            $message = 'Your preferred cleaner '.$booking->preferredStaff->full_name.' was not available, so '.$booking->staff->full_name.' has been assigned to booking '.$bookingCode.' on '.$scheduledDate.' at '.$scheduledTime.'.';
        }

        if ($booking->status === 'confirmed') {
            $message .= ' Your booking is now confirmed.';
        }

        $this->createNotification([
            'user_id' => $booking->user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => route('bookings.show', $booking->id),
        ]);
    }

    private function createClientStatusNotification(Booking $booking, string $status): void
    {
        $bookingCode = 'CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT);
        $scheduledDate = Carbon::parse($booking->scheduled_date)->format('F d, Y');
        $scheduledTime = Carbon::parse($booking->scheduled_time)->format('h:i A');

        [$title, $message, $type] = match ($status) {
            'confirmed' => [
                'Booking confirmed',
                'Booking '.$bookingCode.' is confirmed for '.$scheduledDate.' at '.$scheduledTime.'. We will send another update once the cleaner is on the way or begins the service.',
                'success',
            ],
            'in_progress' => [
                'Service in progress',
                ($booking->staff ? $booking->staff->full_name : 'Your cleaner').' has started handling booking '.$bookingCode.'. Live updates and proof of service will appear in the booking details page.',
                'info',
            ],
            'completed' => [
                'Service completed',
                'Booking '.$bookingCode.' has been marked completed. You can now review the proof of service and leave feedback when you are ready.',
                'success',
            ],
            default => [null, null, null],
        };

        if (! $title || ! $message || ! $type) {
            return;
        }

        $this->createNotification([
            'user_id' => $booking->user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => route('bookings.show', $booking->id),
        ]);
    }

    private function createClientPaymentNotification(Booking $booking): void
    {
        $bookingCode = 'CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT);
        $paymentLabel = Booking::paymentMethodLabel($booking->payment_method);

        [$title, $message, $type] = $booking->payment_status === 'paid'
            ? [
                'Payment confirmed',
                'Payment for booking '.$bookingCode.' has been recorded as paid via '.$paymentLabel.($booking->payment_reference ? ' with reference '.$booking->payment_reference.'.' : '.'),
                'success',
            ]
            : [
                'Payment pending',
                'Payment for booking '.$bookingCode.' is currently marked as pending. Please check your booking details for the latest payment update.',
                'info',
            ];

        $this->createNotification([
            'user_id' => $booking->user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => route('bookings.show', $booking->id),
        ]);
    }
}
