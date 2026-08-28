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
use App\Models\CleanerApplication;
use App\Models\ProviderPayoutTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $activeBookingsQuery = Booking::with(['user', 'staff', 'cleanerApplication', 'service', 'reviewedBy', 'preferredStaff'])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress']);

        $completedBookingsQuery = Booking::with(['user', 'staff', 'cleanerApplication', 'service', 'rating', 'reviewedBy', 'preferredStaff'])
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
        $approvedCleanerApplications = CleanerApplication::where('status', CleanerApplication::STATUS_APPROVED)
            ->orderBy('business_name')
            ->get();

        $activeBookings->getCollection()->transform(function (Booking $booking) use ($presentStaffIds) {
            $busyStaffIds = Booking::busyStaffIdsForAssignment(
                $booking->scheduled_date,
                $booking->scheduled_time,
                $booking->id,
                (int) $booking->duration_minutes
            );

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
        $booking = Booking::with('cleanerApplication.documents')->findOrFail($id);
        $oldStaffId = $booking->staff_id;
        $oldStatus = $booking->status;
        $oldPaymentStatus = $booking->payment_status;

        $validated = $request->validate([
            'status' => ['required', Rule::in(Booking::statuses())],
            'payment_status' => ['nullable', Rule::in(Booking::paymentStatuses())],
            'staff_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'staff')),
            ],
        ]);

        $newStatus = $validated['status'];
        $newStaffId = array_key_exists('staff_id', $validated) ? $validated['staff_id'] : $booking->staff_id;
        $newPaymentStatus = array_key_exists('payment_status', $validated)
            ? $validated['payment_status']
            : $booking->payment_status;

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

        $paymentStatusChanged = $newPaymentStatus !== $oldPaymentStatus;

        if ($paymentStatusChanged) {
            $booking->payment_status = $newPaymentStatus;

            if ($booking->payment_status === 'paid') {
                $booking->payment_reference = $booking->payment_reference ?: Booking::generatePaymentReference($booking->payment_method);
                $booking->paid_at = $booking->paid_at ?: now();
            } else {
                $booking->paid_at = null;

                if ($booking->payment_method === 'on_site_cash') {
                    $booking->payment_reference = null;
                }
            }
        }

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
            $booking->logActivity($actor, 'payment_updated', 'Payment status changed to '.$booking->payment_status.'.', [
                'from_payment_status' => $oldPaymentStatus,
                'to_payment_status' => $booking->payment_status,
                'payment_method' => $booking->payment_method,
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

            if ($paymentStatusChanged && $oldPaymentStatus !== $booking->payment_status) {
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

    public function updateBookingPayout(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

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

        if ($booking->payment_method === 'on_site_cash') {
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

        if ($newStatus === 'paid' && $booking->payment_status !== 'paid') {
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

        if ($booking->payment_method !== 'on_site_cash' || ! $booking->cleaner_application_id || $booking->provider_commission_due === null) {
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

            if ($booking->payment_status !== 'paid') {
                $booking->payment_status = 'paid';
                $booking->payment_reference = $booking->payment_reference ?: Booking::generatePaymentReference('on_site_cash');
                $booking->paid_at = $booking->paid_at ?: now();
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
