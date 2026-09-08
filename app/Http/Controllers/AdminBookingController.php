<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AttendanceHelpers;
use App\Jobs\SendBookingCompletedEmail;
use App\Jobs\SendBookingConfirmedEmail;
use App\Jobs\SendBookingInProgressEmail;
use App\Jobs\SendBookingStaffAssignedEmail;
use App\Jobs\SendMarketplaceProviderAssignedEmail;
use App\Jobs\SendProviderPayoutPaidEmail;
use App\Models\AttendanceLog;
use App\Models\Booking;
use App\Models\BookingStaffAssignment;
use App\Models\CleanerApplication;
use App\Models\ProviderPayoutTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminBookingController extends Controller
{
    use AttendanceHelpers;

    public function bookings(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $tab = $request->get('tab', 'active') === 'completed' ? 'completed' : 'active';
        $activeFilter = in_array($request->get('filter'), ['today', 'unassigned', 'overdue', 'review', 'in_progress', 'provider_declined'], true)
            ? $request->get('filter')
            : '';

        $activeBookingsQuery = Booking::with(['user', 'staff', 'staffAssignments.staff', 'cleanerApplication', 'service', 'payment', 'reviewedBy', 'preferredStaff'])
            ->withCount(['beforeServiceProofs', 'afterServiceProofs'])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress']);

        $completedBookingsQuery = Booking::with(['user', 'staff', 'staffAssignments.staff', 'cleanerApplication', 'service', 'payment', 'rating', 'reviewedBy', 'preferredStaff'])
            ->whereIn('status', ['completed', 'cancelled']);

        $filteredActiveBookingsQuery = (clone $activeBookingsQuery)
            ->when($activeFilter === 'today', fn ($query) => $query->whereDate('scheduled_date', $today))
            ->when($activeFilter === 'unassigned', fn ($query) => $query->whereNull('staff_id'))
            ->when($activeFilter === 'overdue', fn ($query) => $query->where('status', 'pending')->where('created_at', '<=', now()->subDay()))
            ->when($activeFilter === 'review', fn ($query) => $query->where('manual_review_status', 'pending'))
            ->when($activeFilter === 'in_progress', fn ($query) => $query->where('status', 'in_progress'))
            ->when($activeFilter === 'provider_declined', fn ($query) => $query->where('provider_assignment_status', 'declined'));

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
        $allStaffIds = $staffList->pluck('id')->all();
        $approvedCleanerApplications = CleanerApplication::where('status', CleanerApplication::STATUS_APPROVED)
            ->orderBy('business_name')
            ->get();

        $activeBookings->getCollection()->transform(function (Booking $booking) use ($presentStaffIds, $allStaffIds) {
            $busyStaffIds = Booking::busyStaffIdsForAssignment(
                $booking->scheduled_date,
                $booking->scheduled_time,
                $booking->id,
                (int) $booking->duration_minutes
            );

            $booking->busy_staff_ids = $busyStaffIds;
            $booking->available_present_staff_count = count(array_diff($presentStaffIds, $busyStaffIds));
            $booking->available_staff_count = count(array_diff($allStaffIds, $busyStaffIds));
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
            'provider_declined' => (clone $activeBookingsQuery)->where('provider_assignment_status', 'declined')->count(),
        ];
        $pendingEscalationSummary = $this->pendingEscalationSummary();

        return view('admin.bookings', compact(
            'activeBookings', 'approvedCleanerApplications', 'completedBookings', 'staffList', 'stats',
            'tab', 'queueCounts', 'pendingEscalationSummary', 'activeFilter',
        ));
    }

    public function updateBookingProvider(Request $request, $id)
    {
        $booking = Booking::with('cleanerApplication')->findOrFail($id);
        $oldCleanerApplicationId = $booking->cleaner_application_id;

        $validated = $request->validate([
            'cleaner_application_id' => [
                'nullable',
                Rule::exists('cleaner_applications', 'id')
                    ->where(fn ($query) => $query->where('status', CleanerApplication::STATUS_APPROVED)),
            ],
        ]);

        $newCleanerApplicationId = $validated['cleaner_application_id'] ?? null;

        if (in_array($booking->status, ['completed', 'cancelled'], true) && (int) ($newCleanerApplicationId ?? 0) !== (int) ($oldCleanerApplicationId ?? 0)) {
            return back()->withErrors([
                'cleaner_application_id' => 'Marketplace provider assignment cannot be changed after a booking is completed or cancelled.',
            ]);
        }

        if ($newCleanerApplicationId) {
            $provider = CleanerApplication::where('status', CleanerApplication::STATUS_APPROVED)->find($newCleanerApplicationId);

            if (! $provider || ! $provider->coversBarangay($booking->barangay)) {
                return back()->withErrors([
                    'cleaner_application_id' => 'This marketplace provider does not cover the booking barangay.',
                ]);
            }

            if (! $provider->isAvailableForAssignment()) {
                return back()->withErrors([
                    'cleaner_application_id' => 'This marketplace provider is not available for new assignments.',
                ]);
            }

            if (! $provider->hasDailyCapacityFor($booking->scheduled_date, $booking->id)) {
                return back()->withErrors([
                    'cleaner_application_id' => 'This marketplace provider has reached their daily booking limit for '.Carbon::parse($booking->scheduled_date)->format('F d, Y').'.',
                ]);
            }
        }

        if ((int) ($newCleanerApplicationId ?? 0) === (int) ($oldCleanerApplicationId ?? 0)) {
            return back()->with('success', 'Marketplace provider assignment is unchanged.');
        }

        $booking->cleaner_application_id = $newCleanerApplicationId;
        $booking->provider_assignment_status = $newCleanerApplicationId ? 'pending' : null;
        $booking->provider_assignment_responded_at = null;
        $booking->provider_assignment_notes = null;
        if ($newCleanerApplicationId) {
            $booking->forceFill($booking->calculateMarketplaceCommission());
        } else {
            $booking->clearMarketplaceCommission();
        }
        $booking->save();
        $booking->load(['user', 'service', 'cleanerApplication']);

        $booking->logActivity(auth()->user(), 'marketplace_provider_assigned', 'Marketplace provider assignment updated.', [
            'from_cleaner_application_id' => $oldCleanerApplicationId,
            'to_cleaner_application_id' => $newCleanerApplicationId,
            'provider_gross_amount' => $booking->provider_gross_amount,
            'platform_commission_rate' => $booking->platform_commission_rate,
            'platform_commission_amount' => $booking->platform_commission_amount,
            'provider_payout_amount' => $booking->provider_payout_amount,
        ]);

        if ($booking->cleanerApplication) {
            SendMarketplaceProviderAssignedEmail::dispatch($booking->id);
        }

        return back()->with('success', $booking->cleanerApplication
            ? 'Marketplace provider assignment has been updated. Provider email notification queued.'
            : 'Marketplace provider assignment has been cleared.');
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::with(['cleanerApplication.documents', 'payment', 'staffAssignments'])->findOrFail($id);
        $oldStaffId = $booking->staff_id;
        $oldStatus = $booking->status;
        $oldPaymentStatus = $booking->payment?->status ?? 'pending';

        $validated = $request->validate([
            'status' => ['required', Rule::in(Booking::statuses())],
            'payment_status' => ['nullable', Rule::in(Booking::paymentStatuses())],
            'staff_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
            'payment_collected_amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_collected_at' => ['nullable', 'date'],
            'payment_receipt_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $validated['status'];
        $newStaffId = array_key_exists('staff_id', $validated) ? $validated['staff_id'] : $booking->staff_id;
        $newPaymentStatus = array_key_exists('payment_status', $validated)
            ? $validated['payment_status']
            : ($booking->payment?->status ?? 'pending');

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

        if (Booking::requiresAssignedStaffForStatus($newStatus) && ! $newStaffId && ! $booking->hasAcceptedProviderAssignment()) {
            return back()->withErrors([
                'staff_id' => 'Please assign a staff member or use an accepted marketplace provider before updating to this status.',
            ]);
        }

        $requiredCleaners = max((int) ($booking->required_cleaners ?: 1), 1);
        if (Booking::requiresAssignedStaffForStatus($newStatus)
            && $requiredCleaners > 1
            && ! $booking->hasAcceptedProviderAssignment()
            && $booking->staffAssignments->count() !== $requiredCleaners) {
            return back()->withErrors([
                'assignments' => 'Assign all '.$requiredCleaners.' cleaners and their task groups before confirming this booking.',
            ]);
        }

        if ($newStatus === 'completed' && (! $booking->hasBeforeServiceProof() || ! $booking->hasAfterServiceProof())) {
            return back()->withErrors([
                'status' => 'Before-service and after-service photos are required before an admin can mark this booking as completed. Ask the assigned staff member to upload both proofs first.',
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
            && Booking::staffHasScheduleConflict($newStaffId, $booking->scheduled_date, $booking->scheduled_time, $booking->id, (int) $booking->duration_minutes)
        ) {
            $conflictingBooking = Booking::conflictingStaffBooking($newStaffId, $booking->scheduled_date, $booking->scheduled_time, $booking->id, (int) $booking->duration_minutes);
            $conflictLabel = $conflictingBooking
                ? ' CF-'.str_pad($conflictingBooking->id, 5, '0', STR_PAD_LEFT).' at '.Carbon::parse($conflictingBooking->scheduled_time)->format('h:i A')
                : '';

            return back()->withErrors([
                'staff_id' => 'This staff member is unavailable for this schedule'.$conflictLabel.'. Cleaners need the assigned booking time plus 1 hour rest before another assignment.',
            ]);
        }

        if ($booking->preferred_staff_id && $newStaffId) {
            $booking->preferred_staff_status = (int) $newStaffId === (int) $booking->preferred_staff_id
                ? 'assigned'
                : 'alternate_assigned';
        }

        if ($newPaymentStatus === 'paid' && $booking->payment?->method === 'on_site_cash') {
            if ($booking->payment?->cash_proof_path && $booking->payment?->cash_proof_status !== 'approved') {
                return back()->withErrors([
                    'payment_status' => 'Review and approve the uploaded cash payment proof before marking this payment as paid.',
                ]);
            }

            if ($cashPaymentError = $this->cashPaymentDetailsError($booking, $validated)) {
                return back()->withErrors(['payment_collected_amount' => $cashPaymentError])->withInput();
            }
        }

        $paymentStatusChanged = $newPaymentStatus !== $oldPaymentStatus;

        if ($paymentStatusChanged || ($newPaymentStatus === 'paid' && $booking->payment?->method === 'on_site_cash')) {
            $this->applyPaymentStatus($booking, $newPaymentStatus, $validated);
        }

        $booking->staff_id = $newStaffId;

        if ($statusChanged && $newStatus === 'confirmed') {
            $booking->setExpectedServiceWindow();
        }

        if ($statusChanged && $newStatus === 'in_progress') {
            $booking->markServiceStarted();
        }

        if ($statusChanged && $newStatus === 'completed') {
            $booking->markServiceCompleted();
        }

        $booking->status = $newStatus;
        $booking->save();

        $actor = auth()->user();

        if ($statusChanged && $newStatus === 'cancelled') {
            $this->cancelSubscriptionGroupOccurrences($booking, $actor);
        }

        if ($statusChanged) {
            $booking->logActivity($actor, 'status_updated', 'Status changed from '.str_replace('_', ' ', $oldStatus).' to '.str_replace('_', ' ', $newStatus).'.', [
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'on_time_status' => $booking->on_time_status,
                'started_late_minutes' => $booking->started_late_minutes,
                'completed_late_minutes' => $booking->completed_late_minutes,
            ]);
        }

        if ($staffAssignmentChanged) {
            $booking->logActivity($actor, 'staff_assigned', 'Cleaner assignment updated.', [
                'from_staff_id' => $oldStaffId,
                'to_staff_id' => $newStaffId,
            ]);
        }

        if ($paymentStatusChanged) {
            $booking->logActivity($actor, 'payment_updated', 'Payment status changed to '.($booking->payment?->status ?? 'pending').'.', [
                'from_payment_status' => $oldPaymentStatus,
                'to_payment_status' => $booking->payment?->status,
                'payment_method' => $booking->payment?->method,
            ]);
        }

        $booking->load(['user', 'staff', 'service', 'preferredStaff']);

        if ($newStatus === 'confirmed') {
            SendBookingConfirmedEmail::dispatch($booking->id);
        }

        if ($newStatus === 'in_progress') {
            SendBookingInProgressEmail::dispatch($booking->id);
        }

        if ($newStatus === 'completed') {
            SendBookingCompletedEmail::dispatch($booking->id);
        }

        if ($newStaffId && $oldStaffId != $newStaffId) {
            SendBookingStaffAssignedEmail::dispatch($booking->id);

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

        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $this->createClientStatusNotification($booking, 'cancelled');
        }

        if ($paymentStatusChanged && $oldPaymentStatus !== ($booking->payment?->status ?? 'pending')) {
            $this->createClientPaymentNotification($booking);
        }
        Cache::forget('admin:pending_bookings_count');

        $message = 'Booking details have been updated.';

        if ($newStaffId && $oldStaffId != $newStaffId && $oldStatus !== $newStatus) {
            $message = 'Booking status and cleaner assignment have been updated.';
        } elseif ($newStaffId && $oldStaffId != $newStaffId) {
            $message = 'Cleaner assignment has been updated.';
        } elseif ($oldStatus !== $newStatus) {
            $message = 'Booking status has been updated.';
        } elseif ($paymentStatusChanged) {
            $message = 'Payment status has been updated.';
        }

        return back()->with('success', $message);
    }

    public function updateBookingAssignments(Request $request, $id)
    {
        $booking = Booking::with('staffAssignments')->findOrFail($id);
        $requiredCleaners = max((int) ($booking->required_cleaners ?: 1), 1);

        if ($requiredCleaners <= 1) {
            return back()->withErrors([
                'assignments' => 'Task assignments are only needed when a booking requires more than one cleaner.',
            ]);
        }

        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return back()->withErrors(['assignments' => 'Assignments cannot be changed after a booking is completed or cancelled.']);
        }

        if ($booking->requiresManualReview() || $booking->isReviewBlocked()) {
            return back()->withErrors(['assignments' => 'Approve the booking review before assigning cleaners.']);
        }

        $validated = $request->validate([
            'assignments' => ['required', 'array', 'size:'.$requiredCleaners],
            'assignments.*.staff_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
            'assignments.*.task_group' => ['required', Rule::in(array_keys(BookingStaffAssignment::TASK_GROUPS))],
            'assignments.*.task_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignments = collect($validated['assignments']);
        $staffIds = $assignments->pluck('staff_id')->map(fn ($id) => (int) $id);

        if ($staffIds->unique()->count() !== $requiredCleaners) {
            return back()->withErrors(['assignments' => 'Each cleaner can only be assigned once to this booking.']);
        }

        foreach ($staffIds as $staffId) {
            if (Booking::staffHasScheduleConflict(
                $staffId,
                $booking->scheduled_date,
                $booking->scheduled_time,
                $booking->id,
                (int) $booking->duration_minutes
            )) {
                return back()->withErrors([
                    'assignments' => 'One of the selected cleaners is unavailable for this schedule. Choose cleaners without another overlapping booking or rest-buffer conflict.',
                ]);
            }
        }

        $oldStaffIds = $booking->staffAssignments->pluck('staff_id')->map(fn ($id) => (int) $id)->all();
        $actor = auth()->user();

        DB::transaction(function () use ($booking, $assignments, $staffIds, $actor, $oldStaffIds): void {
            $booking->staffAssignments()->delete();
            foreach ($assignments as $assignment) {
                $booking->staffAssignments()->create($assignment);
            }

            // Keep the legacy lead-cleaner column populated for existing workflows,
            // emails, location tracking, and older mobile clients.
            $booking->staff_id = $staffIds->first();
            $booking->save();

            $booking->logActivity($actor, 'staff_assignments_updated', 'Multi-cleaner task assignments updated.', [
                'from_staff_ids' => $oldStaffIds,
                'to_staff_ids' => $staffIds->values()->all(),
                'assignment_count' => $assignments->count(),
            ]);
        });

        $booking->load(['staffAssignments.staff', 'user']);
        foreach ($booking->staffAssignments as $assignment) {
            $this->createNotification([
                'user_id' => $assignment->staff_id,
                'title' => 'Multi-cleaner booking assigned',
                'message' => 'Booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' assigned task: '.$assignment->taskGroupLabel().'.',
                'type' => 'info',
                'link' => route('staff.bookings'),
            ]);
        }

        return back()->with('success', 'All '.$requiredCleaners.' cleaners and their task groups have been assigned.');
    }

    public function updateBookingPayment(Request $request, $id)
    {
        $booking = Booking::with('payment')->findOrFail($id);

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(Booking::paymentStatuses())],
            'payment_collected_amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_collected_at' => ['nullable', 'date'],
            'payment_receipt_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldPaymentStatus = $booking->payment?->status ?? 'pending';
        $newPaymentStatus = $validated['payment_status'];

        if ($newPaymentStatus === 'paid' && $booking->payment?->method === 'on_site_cash') {
            if ($booking->payment?->cash_proof_path && $booking->payment?->cash_proof_status !== 'approved') {
                return back()->withErrors([
                    'payment_status' => 'Review and approve the uploaded cash payment proof before marking this payment as paid.',
                ]);
            }

            if ($cashPaymentError = $this->cashPaymentDetailsError($booking, $validated)) {
                return back()->withErrors(['payment_collected_amount' => $cashPaymentError])->withInput();
            }
        }

        $this->applyPaymentStatus($booking, $newPaymentStatus, $validated);

        $booking->save();
        $booking->load('user');

        if ($oldPaymentStatus !== ($booking->payment?->status ?? 'pending')) {
            $booking->logActivity(auth()->user(), 'payment_updated', 'Payment status changed to '.($booking->payment?->status ?? 'pending').'.', [
                'from_payment_status' => $oldPaymentStatus,
                'to_payment_status' => $booking->payment?->status,
                'payment_method' => $booking->payment?->method,
            ]);
            $this->createClientPaymentNotification($booking);
        }

        return back()->with('success', 'Payment status updated successfully.');
    }

    public function reviewCashPaymentProof(Request $request, $id)
    {
        $booking = Booking::with(['payment', 'user'])->findOrFail($id);
        $payment = $booking->payment;

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'payment_collected_amount' => ['required_if:decision,approve', 'nullable', 'numeric', 'min:0.01'],
            'payment_collected_at' => ['required_if:decision,approve', 'nullable', 'date'],
            'payment_receipt_notes' => ['nullable', 'string', 'max:1000'],
            'cash_proof_rejection_reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:5', 'max:1000'],
        ]);

        if ($payment?->method !== 'on_site_cash' || ! $payment->cash_proof_path) {
            return back()->withErrors(['cash_payment_proof' => 'There is no uploaded cash payment proof to review.']);
        }

        if ($validated['decision'] === 'approve') {
            if ($payment->cash_proof_status !== 'pending') {
                return back()->withErrors(['cash_payment_proof' => 'Only pending cash payment proofs can be approved.']);
            }

            if (abs((float) $validated['payment_collected_amount'] - (float) $booking->price) > 0.009) {
                return back()->withErrors(['payment_collected_amount' => 'The cash amount must match the booking total of PHP '.number_format((float) $booking->price, 2).'.']);
            }

            $payment->forceFill([
                'status' => 'paid',
                'reference' => $payment->reference ?: Booking::generatePaymentReference('on_site_cash'),
                'receipt_number' => $payment->receipt_number ?: Booking::generateCashReceiptNumber(),
                'paid_at' => $payment->paid_at ?: now(),
                'collected_amount' => $validated['payment_collected_amount'],
                'collected_at' => $validated['payment_collected_at'],
                'collected_by' => auth()->id(),
                'receipt_notes' => $validated['payment_receipt_notes'] ?? $payment->receipt_notes,
                'cash_proof_status' => 'approved',
                'cash_proof_reviewed_at' => now(),
                'cash_proof_reviewed_by' => auth()->id(),
                'cash_proof_rejection_reason' => null,
            ])->save();

            $message = 'Cash payment proof approved and payment marked as paid.';
            $clientMessage = 'Your cash payment proof for booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' was approved. Your payment is now confirmed.';
            $activity = 'Admin approved the uploaded cash payment proof and marked payment as paid.';
        } else {
            if ($payment->cash_proof_status !== 'pending') {
                return back()->withErrors(['cash_payment_proof' => 'Only pending cash payment proofs can be rejected.']);
            }

            $payment->forceFill([
                'status' => 'pending',
                'cash_proof_status' => 'rejected',
                'cash_proof_reviewed_at' => now(),
                'cash_proof_reviewed_by' => auth()->id(),
                'cash_proof_rejection_reason' => $validated['cash_proof_rejection_reason'],
            ])->save();

            $message = 'Cash payment proof rejected. The client can upload a replacement.';
            $clientMessage = 'Your cash payment proof for booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' needs correction: '.$validated['cash_proof_rejection_reason'];
            $activity = 'Admin rejected the uploaded cash payment proof.';
        }

        $booking->setRelation('payment', $payment);
        $booking->logActivity(auth()->user(), 'cash_payment_proof_reviewed', $activity, [
            'decision' => $validated['decision'],
            'proof_status' => $payment->cash_proof_status,
        ]);
        $this->createNotification([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'title' => $validated['decision'] === 'approve' ? 'Cash payment approved' : 'Cash payment proof needs correction',
            'message' => $clientMessage,
            'type' => $validated['decision'] === 'approve' ? 'success' : 'warning',
            'link' => route('bookings.show', $booking->id),
        ]);

        if ($validated['decision'] === 'approve') {
            $this->createClientPaymentNotification($booking);
        }

        return back()->with('success', $message);
    }

    public function updateBookingPayout(Request $request, $id)
    {
        $booking = Booking::with('payment')->findOrFail($id);

        $validated = $request->validate([
            'provider_payout_status' => ['required', Rule::in(Booking::providerPayoutStatuses())],
            'provider_payout_reference' => ['required_if:provider_payout_status,paid', 'nullable', 'string', 'max:120'],
            'provider_payout_paid_at' => ['required_if:provider_payout_status,paid', 'nullable', 'date'],
            'provider_payout_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (! $booking->cleaner_application_id || $booking->provider_gross_amount === null) {
            return back()->withErrors([
                'provider_payout_status' => 'Assign a marketplace provider before managing payout status.',
            ]);
        }

        if ($booking->payment?->method === 'on_site_cash') {
            return back()->withErrors([
                'provider_payout_status' => 'Cash marketplace bookings do not use provider payouts. Track the provider commission collection instead.',
            ]);
        }

        $newStatus = $validated['provider_payout_status'];
        $oldStatus = $booking->provider_payout_status ?: 'pending';
        $previousPayoutReference = $booking->provider_payout_reference;
        $previousPayoutPaidAt = $booking->provider_payout_paid_at;
        $previousPayoutProofPath = $booking->provider_payout_proof_path;
        $previousPayoutProofOriginalFilename = $booking->provider_payout_proof_original_filename;

        if (in_array($newStatus, ['ready', 'paid'], true) && $booking->hasOpenDispute()) {
            return back()->withErrors([
                'provider_payout_status' => 'Provider payout is held while the booking dispute is open.',
            ]);
        }

        if (in_array($newStatus, ['ready', 'paid'], true) && $booking->status !== 'completed') {
            return back()->withErrors([
                'provider_payout_status' => 'Provider payout can only be marked ready or paid after the booking is completed.',
            ]);
        }

        if (in_array($newStatus, ['ready', 'paid'], true) && ! $booking->cleanerApplication?->isPayoutReadyForAdmin()) {
            return back()->withErrors([
                'provider_payout_status' => 'Provider payout is on hold until payout setup is verified. '.$booking->cleanerApplication?->payoutHoldReason(),
            ]);
        }

        if ($newStatus === 'paid' && $booking->payment?->status !== 'paid') {
            return back()->withErrors([
                'provider_payout_status' => 'Provider payout cannot be marked paid until the customer payment is paid.',
            ]);
        }

        if ($newStatus === $oldStatus && $newStatus !== 'paid') {
            return back()->with('success', 'Provider payout status is unchanged.');
        }

        $booking->provider_payout_status = $newStatus;

        if ($newStatus === 'paid') {
            $booking->provider_payout_reference = $validated['provider_payout_reference'];
            $booking->provider_payout_paid_at = $validated['provider_payout_paid_at'];
            $booking->provider_payout_processed_by = $request->user()->id;

            if ($request->hasFile('provider_payout_proof')) {
                $proof = $request->file('provider_payout_proof');
                $booking->provider_payout_proof_path = $proof->store('provider-payout-proofs/'.$booking->id, config('filesystems.private_uploads_disk'));
                $booking->provider_payout_proof_original_filename = $proof->getClientOriginalName();
            }
        } else {
            $booking->provider_payout_reference = null;
            $booking->provider_payout_paid_at = null;
            $booking->provider_payout_processed_by = null;
            $booking->provider_payout_proof_path = null;
            $booking->provider_payout_proof_original_filename = null;
        }

        $booking->save();
        $booking->load(['cleanerApplication', 'service']);

        ProviderPayoutTransaction::create([
            'booking_id' => $booking->id,
            'cleaner_application_id' => $booking->cleaner_application_id,
            'processed_by' => $request->user()->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'provider_gross_amount' => $booking->provider_gross_amount,
            'platform_commission_amount' => $booking->platform_commission_amount,
            'provider_payout_amount' => $booking->provider_payout_amount,
            'payout_reference' => $newStatus === 'paid' ? $booking->provider_payout_reference : ($oldStatus === 'paid' ? $previousPayoutReference : null),
            'payout_paid_at' => $newStatus === 'paid' ? $booking->provider_payout_paid_at : ($oldStatus === 'paid' ? $previousPayoutPaidAt : null),
            'payout_proof_path' => $newStatus === 'paid' ? $booking->provider_payout_proof_path : ($oldStatus === 'paid' ? $previousPayoutProofPath : null),
            'payout_proof_original_filename' => $newStatus === 'paid' ? $booking->provider_payout_proof_original_filename : ($oldStatus === 'paid' ? $previousPayoutProofOriginalFilename : null),
            'notes' => $oldStatus === 'paid' && $newStatus !== 'paid' ? 'Paid payout status was reversed by admin.' : null,
        ]);

        $booking->logActivity(auth()->user(), 'provider_payout_updated', 'Provider payout status changed from '.Booking::providerPayoutStatusLabel($oldStatus).' to '.Booking::providerPayoutStatusLabel($newStatus).'.', [
            'from_provider_payout_status' => $oldStatus,
            'to_provider_payout_status' => $newStatus,
            'provider_payout_amount' => $booking->provider_payout_amount,
            'provider_payout_reference' => $booking->provider_payout_reference,
            'provider_payout_paid_at' => $booking->provider_payout_paid_at?->toDateTimeString(),
            'provider_payout_has_proof' => filled($booking->provider_payout_proof_path),
        ]);

        if ($newStatus === 'paid' && $oldStatus !== 'paid' && $booking->cleanerApplication) {
            SendProviderPayoutPaidEmail::dispatch($booking->id);
        }

        return back()->with('success', 'Provider payout status updated.');
    }

    public function updateBookingProviderCommission(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'provider_commission_status' => ['required', Rule::in(Booking::providerCommissionStatuses())],
            'provider_commission_reference' => ['required_if:provider_commission_status,paid', 'nullable', 'string', 'max:120'],
            'provider_commission_paid_at' => ['required_if:provider_commission_status,paid', 'nullable', 'date'],
            'provider_commission_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($booking->payment?->method !== 'on_site_cash' || ! $booking->cleaner_application_id || $booking->provider_commission_due === null) {
            return back()->withErrors([
                'provider_commission_status' => 'Commission collection is only available for cash marketplace provider bookings.',
            ]);
        }

        $oldStatus = $booking->provider_commission_status ?: 'unpaid';
        $newStatus = $validated['provider_commission_status'];

        if ($newStatus === 'not_applicable') {
            return back()->withErrors([
                'provider_commission_status' => 'Cash marketplace provider bookings cannot be marked not applicable.',
            ]);
        }

        $booking->provider_commission_status = $newStatus;

        if ($newStatus === 'paid') {
            $booking->provider_commission_reference = $validated['provider_commission_reference'];
            $booking->provider_commission_paid_at = $validated['provider_commission_paid_at'];
            $booking->provider_commission_collected_by = $request->user()->id;

            if ($request->hasFile('provider_commission_proof')) {
                $proof = $request->file('provider_commission_proof');
                $booking->provider_commission_proof_path = $proof->store('provider-commission-proofs/'.$booking->id, config('filesystems.private_uploads_disk'));
                $booking->provider_commission_proof_original_filename = $proof->getClientOriginalName();
            }

        } elseif ($newStatus !== 'paid') {
            $booking->provider_commission_reference = null;
            $booking->provider_commission_paid_at = null;
            $booking->provider_commission_collected_by = null;
            $booking->provider_commission_proof_path = null;
            $booking->provider_commission_proof_original_filename = null;
        }

        $booking->save();

        $booking->logActivity(auth()->user(), 'provider_commission_updated', 'Provider cash commission status changed from '.Booking::providerCommissionStatusLabel($oldStatus).' to '.Booking::providerCommissionStatusLabel($newStatus).'.', [
            'from_provider_commission_status' => $oldStatus,
            'to_provider_commission_status' => $newStatus,
            'provider_commission_due' => $booking->provider_commission_due,
            'provider_commission_reference' => $booking->provider_commission_reference,
            'provider_commission_paid_at' => $booking->provider_commission_paid_at?->toDateTimeString(),
        ]);

        return back()->with('success', 'Provider commission collection updated.');
    }

    public function downloadProviderPayoutProof($id)
    {
        $booking = Booking::findOrFail($id);

        abort_if(! $booking->provider_payout_proof_path, 404);
        abort_if(! Storage::disk(config('filesystems.private_uploads_disk'))->exists($booking->provider_payout_proof_path), 404);

        return Storage::disk(config('filesystems.private_uploads_disk'))->download(
            $booking->provider_payout_proof_path,
            $booking->provider_payout_proof_original_filename ?: 'provider-payout-proof-'.$booking->id
        );
    }

    public function downloadProviderCommissionProof($id)
    {
        $booking = Booking::findOrFail($id);

        abort_if(! $booking->provider_commission_proof_path, 404);
        abort_if(! Storage::disk(config('filesystems.private_uploads_disk'))->exists($booking->provider_commission_proof_path), 404);

        return Storage::disk(config('filesystems.private_uploads_disk'))->download(
            $booking->provider_commission_proof_path,
            $booking->provider_commission_proof_original_filename ?: 'provider-commission-proof-'.$booking->id
        );
    }

    public function downloadProviderPayoutTransactionProof(ProviderPayoutTransaction $transaction)
    {
        abort_if(! $transaction->payout_proof_path, 404);
        abort_if(! Storage::disk(config('filesystems.private_uploads_disk'))->exists($transaction->payout_proof_path), 404);

        return Storage::disk(config('filesystems.private_uploads_disk'))->download(
            $transaction->payout_proof_path,
            $transaction->payout_proof_original_filename ?: 'provider-payout-transaction-proof-'.$transaction->id
        );
    }

    public function updateBookingDispute(Request $request, $id)
    {
        $booking = Booking::with('cleanerApplication.documents')->findOrFail($id);

        if (! $booking->hasOpenDispute()) {
            return back()->withErrors([
                'dispute_resolution' => 'Only open disputes can be resolved.',
            ]);
        }

        $validated = $request->validate([
            'dispute_resolution' => ['required', Rule::in(array_keys(Booking::disputeResolutions()))],
            'dispute_admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = $validated['dispute_resolution'] === 'reject_dispute' ? 'rejected' : 'resolved';
        $payoutStatus = $booking->provider_payout_status;

        if ($validated['dispute_resolution'] === 'release_payout') {
            $payoutStatus = $booking->cleanerApplication?->isPayoutReadyForAdmin() ? 'ready' : 'held';
        } elseif (in_array($validated['dispute_resolution'], ['refund_customer', 'partial_refund'], true)) {
            $payoutStatus = 'held';
        } elseif ($validated['dispute_resolution'] === 'reject_dispute') {
            $payoutStatus = $booking->cleanerApplication?->isPayoutReadyForAdmin() ? 'ready' : 'held';
        }

        $booking->forceFill([
            'dispute_status' => $status,
            'dispute_resolution' => $validated['dispute_resolution'],
            'dispute_admin_notes' => $validated['dispute_admin_notes'] ?? null,
            'dispute_reviewed_by' => $request->user()->id,
            'dispute_resolved_at' => now(),
            'provider_payout_status' => $booking->provider_gross_amount !== null ? $payoutStatus : $booking->provider_payout_status,
        ])->save();

        $booking->logActivity(
            $request->user(),
            'dispute_resolved',
            'Admin resolved booking dispute as '.Booking::disputeResolutions()[$validated['dispute_resolution']].'.',
            [
                'dispute_resolution' => $validated['dispute_resolution'],
                'provider_payout_status' => $booking->provider_payout_status,
            ]
        );

        return back()->with('success', 'Booking dispute has been updated.');
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
        $oldStatus = $booking->status;

        $booking->manual_review_status = $reviewStatus;
        $booking->reviewed_by = auth()->id();
        $booking->reviewed_at = now();

        if ($reviewStatus === 'approved' && $booking->status === 'pending') {
            $booking->setExpectedServiceWindow();
            $booking->status = 'confirmed';
        }

        if ($reviewStatus === 'blocked') {
            $booking->staff_id = null;
            $booking->staffAssignments()->delete();

            if (! in_array($booking->status, ['completed', 'cancelled'], true)) {
                $booking->status = 'cancelled';
            }
        }

        $booking->save();

        $booking->logActivity(auth()->user(), 'review_updated', 'Manual review decision: '.$reviewStatus.'.', [
            'review_status' => $reviewStatus,
        ]);

        if ($reviewStatus === 'approved' && $oldStatus !== 'confirmed') {
            $booking->load(['user', 'staff', 'service', 'preferredStaff']);
            $booking->logActivity(auth()->user(), 'status_updated', 'Manual review approval confirmed the booking.', [
                'from_status' => $oldStatus,
                'to_status' => 'confirmed',
            ]);
            SendBookingConfirmedEmail::dispatch($booking->id);
            $this->createClientStatusNotification($booking, 'confirmed');
        }

        $message = $reviewStatus === 'approved'
            ? 'Booking approved and confirmed. Assign cleaners before the service starts.'
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
            'cancelled' => [
                'Booking cancelled',
                'Booking '.$bookingCode.' has been cancelled. If this was unexpected, please contact support before creating another booking.',
                'info',
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

    private function cancelSubscriptionGroupOccurrences(Booking $booking, ?User $actor): void
    {
        if (! $booking->subscription_group_id) {
            return;
        }

        Booking::query()
            ->where('subscription_group_id', $booking->subscription_group_id)
            ->where('id', '!=', $booking->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get()
            ->each(function (Booking $occurrence) use ($actor, $booking): void {
                $oldStatus = $occurrence->status;
                $occurrence->status = 'cancelled';
                $occurrence->save();

                $occurrence->logActivity($actor, 'status_updated', 'Subscription occurrence cancelled with booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).'.', [
                    'from_status' => $oldStatus,
                    'to_status' => 'cancelled',
                    'subscription_group_id' => $booking->subscription_group_id,
                ]);
            });
    }

    private function cashPaymentDetailsError(Booking $booking, array $validated): ?string
    {
        $amount = $validated['payment_collected_amount'] ?? $booking->payment?->collected_amount;
        $collectedAt = $validated['payment_collected_at'] ?? $booking->payment?->collected_at;

        if ($amount === null || $amount === '') {
            return 'Enter the cash amount collected before marking this booking as paid.';
        }

        if (abs((float) $amount - (float) $booking->price) > 0.009) {
            return 'The cash amount must match the booking total of PHP '.number_format((float) $booking->price, 2).'.';
        }

        if ($collectedAt === null || $collectedAt === '') {
            return 'Enter when the cash was collected before marking this booking as paid.';
        }

        return null;
    }

    private function applyPaymentStatus(Booking $booking, string $paymentStatus, array $validated): void
    {
        $payment = $booking->paymentOrCreate([
            'method' => 'on_site_cash',
            'status' => 'pending',
            'amount' => $booking->price ?? 0,
            'currency' => 'PHP',
            'provider' => 'manual',
        ]);
        $method = $payment->method;
        $payment->status = $paymentStatus;

        if ($paymentStatus === 'paid') {
            $payment->reference = $payment->reference ?: Booking::generatePaymentReference($method);
            $payment->paid_at = $payment->paid_at ?: now();

            if ($method === 'on_site_cash') {
                $payment->receipt_number = $payment->receipt_number ?: Booking::generateCashReceiptNumber();
                $payment->collected_amount = $validated['payment_collected_amount'] ?? $payment->collected_amount;
                $payment->collected_at = $validated['payment_collected_at'] ?? $payment->collected_at;
                $payment->collected_by = auth()->id();
                $payment->receipt_notes = $validated['payment_receipt_notes'] ?? $payment->receipt_notes;
            }

            $payment->save();
            $booking->setRelation('payment', $payment);

            return;
        }

        $payment->paid_at = null;

        if ($method === 'on_site_cash' && $paymentStatus === 'pending') {
            $payment->reference = null;
            $payment->receipt_number = null;
            $payment->collected_amount = null;
            $payment->collected_at = null;
            $payment->collected_by = null;
            $payment->receipt_notes = null;
        }

        $payment->save();
        $booking->setRelation('payment', $payment);
    }

    private function createClientPaymentNotification(Booking $booking): void
    {
        $bookingCode = 'CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT);
        $paymentLabel = Booking::paymentMethodLabel($booking->payment?->method ?? 'on_site_cash');

        [$title, $message, $type] = ($booking->payment?->status ?? 'pending') === 'paid'
            ? [
                'Payment confirmed',
                'Payment for booking '.$bookingCode.' has been recorded as paid via '.$paymentLabel.($booking->payment?->reference ? ' with reference '.$booking->payment->reference.'.' : '.'),
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
