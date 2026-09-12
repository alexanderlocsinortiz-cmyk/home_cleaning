<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DeviceEnrollmentRequest;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StaffPortalController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        // ✅ Eager load to avoid N+1 queries
        $assignedBookings = Booking::with(['user', 'rating', 'service', 'payment', 'staffAssignments.staff'])
            ->select([
                'id',
                'user_id',
                'staff_id',
                'service_id',
                'status',
                'scheduled_date',
                'scheduled_time',
                'price',
            ])
            ->assignedToStaff($user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // ✅ Use aggregates instead of multiple queries
        $stats = Booking::assignedToStaff($user->id)
            ->selectRaw("
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings
            ")
            ->first();

        $totalBookings = $stats->total_bookings ?? 0;
        $completedBookings = $stats->completed_bookings ?? 0;
        $inProgress = $stats->in_progress ?? 0;
        $confirmedBookings = $stats->confirmed_bookings ?? 0;
        // Booking price belongs to the legacy primary cleaner until a split policy is defined.
        $totalEarnings = Booking::query()
            ->where('staff_id', $user->id)
            ->where('status', 'completed')
            ->sum('price');

        $ratingStats = Rating::query()
            ->where('staff_id', $user->id)
            ->selectRaw('AVG(stars) as average_stars, COUNT(*) as total_ratings')
            ->first();

        $avgRating = $ratingStats?->average_stars
            ? round((float) $ratingStats->average_stars, 1)
            : null;
        $totalRatings = (int) ($ratingStats?->total_ratings ?? 0);

        return view('staff.welcome', compact(
            'user', 'assignedBookings', 'totalBookings',
            'completedBookings', 'inProgress', 'confirmedBookings',
            'avgRating', 'totalRatings', 'totalEarnings'
        ));
    }

    public function serviceAreas()
    {
        $barangays = config('cleanflow.service_areas', []);
        $coverageAreas = config('cleanflow.bukidnon_service_areas', []);
        $providerCoveragePoints = $this->providerCoverageMapPoints();
        $stats = $this->serviceAreaStats();

        return view('staff.service-areas', compact('barangays', 'coverageAreas', 'providerCoveragePoints', 'stats'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,completed'],
        ]);

        $booking = Booking::where('id', $id)
            ->assignedToStaff(Auth::id())
            ->firstOrFail();

        if (! $booking->canBeUpdatedByStaffTo($validated['status'])) {
            return back()->withErrors([
                'status' => $validated['status'] === 'completed'
                    ? 'Only bookings that are already in progress can be marked as completed.'
                    : 'Only confirmed bookings can be started.',
            ]);
        }

        $status = $validated['status'];

        if ($status === 'in_progress') {
            $request->validate([
                'before_photos' => ['required', 'array', 'min:1', 'max:4'],
                'before_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
        }

        if ($status === 'completed') {
            $request->validate([
                'after_photos' => ['required', 'array', 'min:1', 'max:4'],
                'after_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'completion_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:'.config('cleanflow.proof_uploads.max_video_kb', 102400)],
            ]);
        }

        if ($status === 'completed' && ! $booking->hasBeforeServiceProof()) {
            return back()->withErrors([
                'status' => 'Upload at least one before-service photo before completing this booking.',
            ]);
        }

        $actor = Auth::user();

        try {
            DB::transaction(function () use ($booking, $request, $actor, $status) {
                if ($status === 'in_progress') {
                    $beforePhotoCount = $this->storeProofBatch(
                        $booking,
                        $request->file('before_photos', []),
                        'before',
                        'image',
                        $actor->id
                    );

                    $booking->status = 'in_progress';
                    $booking->markServiceStarted();
                    $booking->save();

                    $booking->logActivity(
                        $actor,
                        'proof_uploaded',
                        'Uploaded '.$beforePhotoCount.' before-service photo'.($beforePhotoCount === 1 ? '' : 's').'.',
                        [
                            'stage' => 'before',
                            'media_type' => 'image',
                            'count' => $beforePhotoCount,
                        ]
                    );

                    $booking->logActivity(
                        $actor,
                        'status_updated',
                        'Marked the booking as in progress.',
                        [
                            'from_status' => 'confirmed',
                            'to_status' => 'in_progress',
                        ]
                    );

                    $this->createClientProofNotification($booking, 'service_started', [
                        'before_photo_count' => $beforePhotoCount,
                    ]);

                    return;
                }

                $afterPhotoCount = $this->storeProofBatch(
                    $booking,
                    $request->file('after_photos', []),
                    'after',
                    'image',
                    $actor->id
                );

                $videoUploaded = false;
                if ($request->hasFile('completion_video')) {
                    $this->storeProofBatch(
                        $booking,
                        [$request->file('completion_video')],
                        'after',
                        'video',
                        $actor->id,
                        'completion_video'
                    );
                    $videoUploaded = true;
                }

                $booking->status = 'completed';
                $booking->markServiceCompleted();

                $booking->save();

                $booking->logActivity(
                    $actor,
                    'proof_uploaded',
                    'Uploaded '.$afterPhotoCount.' after-service photo'.($afterPhotoCount === 1 ? '' : 's').'.',
                    [
                        'stage' => 'after',
                        'media_type' => 'image',
                        'count' => $afterPhotoCount,
                    ]
                );

                if ($videoUploaded) {
                    $booking->logActivity(
                        $actor,
                        'proof_uploaded',
                        'Uploaded a completion video.',
                        [
                            'stage' => 'after',
                            'media_type' => 'video',
                            'count' => 1,
                        ]
                    );
                }

                $booking->logActivity(
                    $actor,
                    'status_updated',
                    'Marked the booking as completed.',
                    [
                        'from_status' => 'in_progress',
                        'to_status' => 'completed',
                        'payment_status' => $booking->payment?->status ?? 'pending',
                        'on_time_status' => $booking->on_time_status,
                        'started_late_minutes' => $booking->started_late_minutes,
                        'completed_late_minutes' => $booking->completed_late_minutes,
                    ]
                );

                $this->createClientProofNotification($booking, 'service_completed', [
                    'after_photo_count' => $afterPhotoCount,
                    'video_uploaded' => $videoUploaded,
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Staff booking status update failed.', [
                'booking_id' => $booking->id,
                'staff_id' => $actor->id,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors(['status' => 'We could not update this service right now. Please try again in a few seconds.'])
                ->withInput();
        }

        return back()->with('success', $validated['status'] === 'completed'
            ? 'Service marked as completed and proof of service has been uploaded.'
            : 'Service marked as in progress and before-service proof has been uploaded.');
    }

    public function profile()
    {
        $user = Auth::user();

        return view('staff.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function bookings(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $query = Booking::with(['user', 'rating', 'service', 'payment', 'serviceProofs', 'staffAssignments.staff'])
            ->withCount([
                'serviceProofs as before_photo_count' => fn ($proofs) => $proofs
                    ->where('stage', 'before')
                    ->where('media_type', 'image'),
                'serviceProofs as after_photo_count' => fn ($proofs) => $proofs
                    ->where('stage', 'after')
                    ->where('media_type', 'image'),
                'serviceProofs as completion_video_count' => fn ($proofs) => $proofs
                    ->where('stage', 'after')
                    ->where('media_type', 'video'),
            ])
            ->assignedToStaff($user->id)
            ->orderBy('scheduled_date', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10);

        $counts = [
            'all' => Booking::assignedToStaff($user->id)->count(),
            'confirmed' => Booking::assignedToStaff($user->id)->where('status', 'confirmed')->count(),
            'in_progress' => Booking::assignedToStaff($user->id)->where('status', 'in_progress')->count(),
            'completed' => Booking::assignedToStaff($user->id)->where('status', 'completed')->count(),
            'cancelled' => Booking::assignedToStaff($user->id)->where('status', 'cancelled')->count(),
        ];

        return view('staff.bookings', compact('bookings', 'status', 'counts', 'user'));
    }

    public function performance()
    {
        $user = Auth::user();

        // All completed bookings with ratings
        $completedBookings = Booking::with(['rating', 'service', 'user', 'payment'])
            ->assignedToStaff($user->id)
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Rating stats
        $ratings = $completedBookings
            ->filter(fn (Booking $booking): bool => (int) $booking->staff_id === (int) $user->id)
            ->pluck('rating')
            ->filter();
        $avgRating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;
        $totalRatings = $ratings->count();

        // Star breakdown
        $starBreakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $starBreakdown[$i] = $ratings->where('stars', $i)->count();
        }

        // Overall stats
        $totalBookings = Booking::assignedToStaff($user->id)->count();
        $completedCount = $completedBookings->count();
        $completionRate = $totalBookings > 0 ? round(($completedCount / $totalBookings) * 100, 1) : 0;
        // Booking price belongs to the legacy primary cleaner until a split policy is defined.
        $totalEarnings = $completedBookings
            ->where('staff_id', $user->id)
            ->sum('price');

        // Ranking among all staff
        $allAssignedBookings = Booking::with([
            'rating',
            'staffAssignments:id,booking_id,staff_id',
        ])->get();
        $allStaff = User::where('role', 'staff')
            ->get()
            ->map(function ($staff) use ($allAssignedBookings) {
                $assigned = $allAssignedBookings
                    ->filter(fn (Booking $booking): bool => $booking->isAssignedToStaff((int) $staff->id));
                $completed = $assigned->where('status', 'completed');
                $ratings = $assigned
                    ->filter(fn (Booking $booking): bool => (int) $booking->staff_id === (int) $staff->id)
                    ->pluck('rating')
                    ->filter();
                $staff->avg_rating = $ratings->count() > 0 ? $ratings->avg('stars') : 0;
                $staff->completed_count = $completed->count();

                return $staff;
            })
            ->sortByDesc('avg_rating')
            ->values();

        $myRank = $allStaff->search(fn ($s) => $s->id === $user->id) + 1;
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
        $scheduleTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $scheduleNow = Carbon::now($scheduleTimezone);

        $bookings = Booking::with(['user', 'service', 'payment', 'staffAssignments.staff'])
            ->assignedToStaff($user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereDate('scheduled_date', '>=', $scheduleNow->copy()->startOfMonth()->toDateString())
            ->whereDate('scheduled_date', '<=', $scheduleNow->copy()->endOfMonth()->addMonth()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // Group bookings by date
        $bookingsByDate = $bookings->groupBy(function ($booking) use ($scheduleTimezone) {
            return Carbon::parse($booking->scheduled_date->toDateString(), $scheduleTimezone)->format('Y-m-d');
        });

        $currentMonth = $scheduleNow->format('Y-m');

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

        return back()->with('success', 'All booking updates have been marked as read.');
    }

    public function fingerprintConsent(DeviceEnrollmentRequest $enrollmentRequest)
    {
        abort_unless($enrollmentRequest->user_id === Auth::id(), 403);

        $enrollmentRequest->load(['device', 'requestedBy']);

        return view('staff.fingerprint-consent', [
            'enrollmentRequest' => $enrollmentRequest,
            'user' => Auth::user(),
        ]);
    }

    public function acceptFingerprintConsent(Request $request, DeviceEnrollmentRequest $enrollmentRequest)
    {
        abort_unless($enrollmentRequest->user_id === Auth::id(), 403);

        $request->validate([
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'You must accept the biometric attendance terms before enrollment can continue.',
        ]);

        if ($enrollmentRequest->status !== 'awaiting_consent') {
            return redirect()
                ->route('staff.fingerprint-consent.show', $enrollmentRequest)
                ->with('success', 'Fingerprint enrollment terms were already reviewed.');
        }

        $enrollmentRequest->update([
            'status' => 'consent_accepted',
            'consent_accepted_at' => now(),
        ]);

        Notification::where('user_id', Auth::id())
            ->where('link', route('staff.fingerprint-consent.show', $enrollmentRequest))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()
            ->route('staff.fingerprint-consent.show', $enrollmentRequest)
            ->with('success', 'Consent approved. Admin can now continue your fingerprint enrollment on the device.');
    }

    public function declineFingerprintConsent(DeviceEnrollmentRequest $enrollmentRequest)
    {
        abort_unless($enrollmentRequest->user_id === Auth::id(), 403);

        if ($enrollmentRequest->status !== 'awaiting_consent') {
            return redirect()
                ->route('staff.fingerprint-consent.show', $enrollmentRequest)
                ->with('success', 'Fingerprint enrollment consent was already reviewed.');
        }

        $enrollmentRequest->update([
            'status' => 'declined',
            'error_message' => 'Staff declined biometric consent.',
        ]);

        Notification::where('user_id', Auth::id())
            ->where('link', route('staff.fingerprint-consent.show', $enrollmentRequest))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()
            ->route('staff.fingerprint-consent.show', $enrollmentRequest)
            ->with('success', 'Fingerprint enrollment consent declined.');
    }

    private function storeProofBatch(
        Booking $booking,
        array $files,
        string $stage,
        string $mediaType,
        int $uploadedBy,
        ?string $errorField = null
    ): int {
        $disk = (string) config('filesystems.proof_uploads_disk');
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                try {
                    $path = $file->store('booking-proofs/'.$stage, $disk);
                } catch (Throwable $exception) {
                    $this->logProofStorageFailure($booking, $stage, $mediaType, $disk, $file, $exception);

                    throw ValidationException::withMessages([
                        $errorField ?: ($stage === 'before' ? 'before_photos' : 'after_photos') => 'We could not securely store the proof file. Please try again or contact an administrator.',
                    ]);
                }

                if (! is_string($path) || trim($path) === '') {
                    $exception = new \RuntimeException('Proof file storage returned an empty path.');
                    $this->logProofStorageFailure($booking, $stage, $mediaType, $disk, $file, $exception);

                    throw ValidationException::withMessages([
                        $errorField ?: ($stage === 'before' ? 'before_photos' : 'after_photos') => 'We could not securely store the proof file. Please try again or contact an administrator.',
                    ]);
                }

                $storedPaths[] = $path;

                $booking->serviceProofs()->create([
                    'uploaded_by' => $uploadedBy,
                    'stage' => $stage,
                    'media_type' => $mediaType,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                try {
                    Storage::disk($disk)->delete($storedPaths);
                } catch (Throwable $cleanupException) {
                    Log::warning('Booking proof cleanup failed after status update error.', [
                        'booking_id' => $booking->id,
                        'disk' => $disk,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            throw $exception;
        }

        return count($files);
    }

    private function logProofStorageFailure(Booking $booking, string $stage, string $mediaType, string $disk, $file, Throwable $exception): void
    {
        Log::error('Booking proof upload failed.', [
            'booking_id' => $booking->id,
            'stage' => $stage,
            'media_type' => $mediaType,
            'disk' => $disk,
            'original_name' => $file->getClientOriginalName(),
            'error' => $exception->getMessage(),
        ]);
    }

    private function createClientProofNotification(Booking $booking, string $event, array $payload = []): void
    {
        $bookingCode = 'CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT);

        if ($event === 'service_started') {
            $this->createNotification([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'title' => 'Service started with proof',
                'message' => 'Your cleaner has started booking '.$bookingCode.' and uploaded '.($payload['before_photo_count'] ?? 0).' before-service photo'.(($payload['before_photo_count'] ?? 0) === 1 ? '' : 's').'. You can review them from the booking details page.',
                'type' => 'info',
                'link' => '/bookings/'.$booking->id,
            ]);

            return;
        }

        $message = 'Your cleaner completed booking '.$bookingCode.' and uploaded '.($payload['after_photo_count'] ?? 0).' after-service photo'.(($payload['after_photo_count'] ?? 0) === 1 ? '' : 's');

        if (! empty($payload['video_uploaded'])) {
            $message .= ' plus a completion video';
        }

        $this->createNotification([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'title' => 'Service completed with proof',
            'message' => $message.'. You can now review the proof of service and leave feedback whenever you are ready.',
            'type' => 'success',
            'link' => '/bookings/'.$booking->id,
        ]);
    }
}
