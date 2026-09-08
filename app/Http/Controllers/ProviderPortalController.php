<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\CleanerApplicationDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProviderPortalController extends Controller
{
    public function dashboard()
    {
        $application = auth()->user()->cleanerApplication;

        $assignedBookingsQuery = $application
            ? Booking::where('cleaner_application_id', $application->id)
            : Booking::whereRaw('1 = 0');

        $assignedBookings = (clone $assignedBookingsQuery)
            ->with(['rating', 'payment'])
            ->get();

        $bookingStats = [
            'assigned' => (clone $assignedBookingsQuery)->count(),
            'active' => (clone $assignedBookingsQuery)->whereIn('status', ['pending', 'confirmed', 'in_progress'])->count(),
            'completed' => (clone $assignedBookingsQuery)->where('status', 'completed')->count(),
        ];

        $ratings = $assignedBookings->pluck('rating')->filter();
        $ratingStats = [
            'average' => $ratings->isNotEmpty() ? round((float) $ratings->avg('stars'), 1) : null,
            'total' => $ratings->count(),
        ];

        $respondedAssignments = $assignedBookings
            ->whereIn('provider_assignment_status', ['accepted', 'declined']);

        $acceptanceRate = $respondedAssignments->isNotEmpty()
            ? round(($respondedAssignments->where('provider_assignment_status', 'accepted')->count() / $respondedAssignments->count()) * 100)
            : null;

        $responseMinutes = $assignedBookings
            ->filter(fn (Booking $booking) => $booking->provider_assignment_responded_at !== null)
            ->map(fn (Booking $booking) => max(0, $booking->created_at->diffInMinutes($booking->provider_assignment_responded_at)));

        $performanceStats = [
            'completed_jobs' => $bookingStats['completed'],
            'acceptance_rate' => $acceptanceRate,
            'average_rating' => $ratingStats['average'],
            'total_ratings' => $ratingStats['total'],
            'average_response_minutes' => $responseMinutes->isNotEmpty() ? round((float) $responseMinutes->avg()) : null,
        ];

        $payoutRows = $application
            ? Booking::where('cleaner_application_id', $application->id)->whereHas('payout')->with(['payment', 'payout'])->get()
            : collect();

        $payoutStats = $this->payoutStats($payoutRows);

        $currentBooking = (clone $assignedBookingsQuery)
            ->with(['user', 'service', 'payment'])
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->first();

        $recentBookings = (clone $assignedBookingsQuery)
            ->with(['user', 'service', 'payment'])
            ->latest()
            ->take(5)
            ->get();

        $payoutChecklist = [
            'details' => $application?->hasPayoutDetails() ?? false,
            'valid_id' => ($application?->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_VALID_ID_FRONT) ?? false)
                && ($application?->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_VALID_ID_BACK) ?? false),
            'proof' => $application?->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_PAYOUT_ACCOUNT_PROOF) ?? false,
        ];

        return view('provider.dashboard', compact(
            'application',
            'bookingStats',
            'ratingStats',
            'performanceStats',
            'payoutStats',
            'currentBooking',
            'recentBookings',
            'payoutChecklist'
        ));
    }

    public function updateAvailability(Request $request)
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application, 403);

        $validated = $request->validate([
            'availability_status' => ['required', Rule::in(['available', 'paused'])],
            'availability_notes' => ['nullable', 'string', 'max:1000'],
            'max_daily_bookings' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $application->update([
            'availability_status' => $validated['availability_status'],
            'availability_notes' => $validated['availability_notes'] ?? null,
            'max_daily_bookings' => $validated['max_daily_bookings'] ?? null,
        ]);

        return back()->with('success', 'Availability updated.');
    }

    public function updateLocation(Request $request)
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application, 403);

        $locationCenters = config('cleanflow.bukidnon_location_centers', []);
        $validated = $request->validate([
            'location_area' => ['required', 'string', Rule::in(array_keys($locationCenters))],
            'location_latitude' => ['required', 'numeric', 'between:7.3,8.7'],
            'location_longitude' => ['required', 'numeric', 'between:124.4,125.6'],
        ]);

        $application->update($validated);

        return back()->with('success', 'Provider location updated. Only CleanFlow admins can see the exact pin.');
    }

    public function updatePayoutSetup(Request $request)
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application, 403);

        $application->refresh();

        $validated = $request->validate([
            'payout_method' => ['required', Rule::in(CleanerApplication::payoutMethods())],
            'payout_account_name' => ['required', 'string', 'max:150'],
            'payout_account_number' => ['required', 'string', 'max:100'],
            'valid_id_front_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'valid_id_back_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'business_permit_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'payout_account_proof_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $documentUploads = [
            CleanerApplicationDocument::TYPE_VALID_ID_FRONT => 'valid_id_front_document',
            CleanerApplicationDocument::TYPE_VALID_ID_BACK => 'valid_id_back_document',
            CleanerApplicationDocument::TYPE_BUSINESS_PERMIT => 'business_permit_document',
            CleanerApplicationDocument::TYPE_PAYOUT_ACCOUNT_PROOF => 'payout_account_proof_document',
        ];

        foreach ($documentUploads as $documentType => $inputName) {
            if ($request->hasFile($inputName)) {
                $this->storePayoutDocument($application, $documentType, $request->file($inputName));
            }
        }

        $missingDocuments = collect($application->requiredPayoutDocumentTypes())
            ->reject(fn (string $documentType) => $application->fresh()->hasUploadedPayoutDocument($documentType))
            ->map(fn (string $documentType) => CleanerApplicationDocument::TYPE_LABELS[$documentType])
            ->values()
            ->all();

        if ($missingDocuments !== []) {
            return back()->withErrors([
                'payout_documents' => 'Upload required payout document(s): '.implode(', ', $missingDocuments).'.',
            ]);
        }

        $application->update([
            'payout_method' => $validated['payout_method'],
            'payout_account_name' => $validated['payout_account_name'],
            'payout_account_number' => $validated['payout_account_number'],
            'valid_id_submitted' => $application->fresh()->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_VALID_ID_FRONT)
                && $application->fresh()->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_VALID_ID_BACK),
            'business_permit_submitted' => $application->isTeam() && $application->fresh()->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_BUSINESS_PERMIT),
            'payout_account_proof_submitted' => $application->fresh()->hasUploadedPayoutDocument(CleanerApplicationDocument::TYPE_PAYOUT_ACCOUNT_PROOF),
            'payout_verification_status' => CleanerApplication::PAYOUT_VERIFICATION_PENDING,
            'payout_verified_at' => null,
            'payout_verified_by' => null,
        ]);

        return back()->with('success', 'Payout setup submitted for admin verification.');
    }

    public function bookings(Request $request)
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application, 403);

        $status = $request->query('status', 'all');
        $allowedStatuses = ['all', 'pending_response', 'active', 'completed', 'cancelled'];

        abort_unless(in_array($status, $allowedStatuses, true), 404);

        $baseQuery = Booking::where('cleaner_application_id', $application->id);

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending_response' => (clone $baseQuery)
                ->where(function ($query) {
                    $query->whereNull('provider_assignment_status')
                        ->orWhere('provider_assignment_status', 'pending');
                })
                ->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['pending', 'confirmed', 'in_progress'])->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
        ];

        $bookings = (clone $baseQuery)
            ->with(['user', 'service', 'payment'])
            ->when($status === 'pending_response', function ($query) {
                $query->where(function ($innerQuery) {
                    $innerQuery->whereNull('provider_assignment_status')
                        ->orWhere('provider_assignment_status', 'pending');
                });
            })
            ->when($status === 'active', fn ($query) => $query->whereIn('status', ['pending', 'confirmed', 'in_progress']))
            ->when($status === 'completed', fn ($query) => $query->where('status', 'completed'))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->orderByRaw("CASE WHEN status IN ('pending', 'confirmed', 'in_progress') THEN 0 ELSE 1 END")
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate(10)
            ->withQueryString();

        return view('provider.bookings', compact('application', 'bookings', 'counts', 'status'));
    }

    public function payouts()
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application, 403);

        $payoutStats = $this->payoutStats(
            Booking::where('cleaner_application_id', $application->id)
                ->whereHas('payout')
                ->with(['payment', 'payout'])
                ->get()
        );

        $payouts = Booking::with(['user', 'service', 'payment', 'payout'])
            ->where('cleaner_application_id', $application->id)
            ->whereHas('payout')
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('provider.payouts', compact('application', 'payoutStats', 'payouts'));
    }

    public function showBooking(Booking $booking)
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application || (int) $booking->cleaner_application_id !== (int) $application->id, 403);

        $booking->load(['user', 'service', 'staff', 'payment', 'payout', 'serviceProofs.uploader']);

        return view('provider.booking-show', compact('application', 'booking'));
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application || (int) $booking->cleaner_application_id !== (int) $application->id, 403);

        if ($booking->effectiveProviderAssignmentStatus() !== 'accepted') {
            return back()->withErrors([
                'status' => 'Accept this assignment before updating service progress.',
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'completed'])],
        ]);

        $statusIsAllowed = match ($validated['status']) {
            'in_progress' => $booking->status === 'confirmed',
            'completed' => $booking->status === 'in_progress',
            default => false,
        };

        if (! $statusIsAllowed) {
            return back()->withErrors([
                'status' => $validated['status'] === 'completed'
                    ? 'Only bookings that are already in progress can be marked as completed.'
                    : 'Only confirmed bookings can be started.',
            ]);
        }

        if ($validated['status'] === 'in_progress') {
            $request->validate([
                'before_photos' => ['required', 'array', 'min:1', 'max:4'],
                'before_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
        }

        if ($validated['status'] === 'completed') {
            $request->validate([
                'after_photos' => ['required', 'array', 'min:1', 'max:4'],
                'after_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'completion_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:'.config('cleanflow.proof_uploads.max_video_kb', 102400)],
            ]);
        }

        if ($validated['status'] === 'completed' && ! $booking->hasBeforeServiceProof()) {
            return back()->withErrors([
                'status' => 'Upload at least one before-service photo before completing this booking.',
            ]);
        }

        $actor = auth()->user();

        DB::transaction(function () use ($booking, $request, $actor, $validated) {
            if ($validated['status'] === 'in_progress') {
                $beforePhotoCount = $this->storeProofBatch(
                    $booking,
                    $request->file('before_photos', []),
                    'before',
                    'image',
                    $actor
                );

                $booking->status = 'in_progress';
                $booking->markServiceStarted();
                $booking->save();

                $booking->logActivity($actor, 'provider_proof_uploaded', 'Provider uploaded '.$beforePhotoCount.' before-service photo'.($beforePhotoCount === 1 ? '' : 's').'.', [
                    'stage' => 'before',
                    'media_type' => 'image',
                    'count' => $beforePhotoCount,
                ]);

                $booking->logActivity($actor, 'provider_status_updated', 'Provider marked the booking as in progress.', [
                    'from_status' => 'confirmed',
                    'to_status' => 'in_progress',
                ]);

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
                $actor
            );

            $videoUploaded = false;
            if ($request->hasFile('completion_video')) {
                $this->storeProofBatch(
                    $booking,
                    [$request->file('completion_video')],
                    'after',
                    'video',
                    $actor,
                    'completion_video'
                );
                $videoUploaded = true;
            }

            $booking->status = 'completed';
            $booking->markServiceCompleted();

            if ($booking->payment?->method === 'on_site_cash') {
                $booking->cash_collected_amount = $booking->cash_collected_amount ?: $booking->provider_gross_amount;
                $booking->provider_commission_due = $booking->provider_commission_due ?: $booking->platform_commission_amount;
                $booking->provider_commission_status = $booking->provider_commission_status ?: 'unpaid';
            }

            $booking->save();

            $booking->logActivity($actor, 'provider_proof_uploaded', 'Provider uploaded '.$afterPhotoCount.' after-service photo'.($afterPhotoCount === 1 ? '' : 's').'.', [
                'stage' => 'after',
                'media_type' => 'image',
                'count' => $afterPhotoCount,
            ]);

            if ($videoUploaded) {
                $booking->logActivity($actor, 'provider_proof_uploaded', 'Provider uploaded a completion video.', [
                    'stage' => 'after',
                    'media_type' => 'video',
                    'count' => 1,
                ]);
            }

            $booking->logActivity($actor, 'provider_status_updated', 'Provider marked the booking as completed.', [
                'from_status' => 'in_progress',
                'to_status' => 'completed',
                'payment_status' => $booking->payment?->status ?? 'pending',
                'on_time_status' => $booking->on_time_status,
                'started_late_minutes' => $booking->started_late_minutes,
                'completed_late_minutes' => $booking->completed_late_minutes,
            ]);

            $this->createClientProofNotification($booking, 'service_completed', [
                'after_photo_count' => $afterPhotoCount,
                'video_uploaded' => $videoUploaded,
            ]);
        });

        return back()->with('success', $validated['status'] === 'completed'
            ? 'Service marked as completed and proof of service has been uploaded.'
            : 'Service marked as in progress and before-service proof has been uploaded.');
    }

    public function respondToBooking(Request $request, Booking $booking)
    {
        $application = auth()->user()->cleanerApplication;

        abort_if(! $application || (int) $booking->cleaner_application_id !== (int) $application->id, 403);

        if (! $booking->canProviderRespondToAssignment()) {
            return back()->withErrors([
                'response' => 'This assignment already has a response or can no longer be changed.',
            ]);
        }

        $validated = $request->validate([
            'response' => ['required', Rule::in(['accepted', 'declined'])],
            'provider_assignment_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking->provider_assignment_status = $validated['response'];
        $booking->provider_assignment_responded_at = now();
        $booking->provider_assignment_notes = $validated['provider_assignment_notes'] ?? null;
        $booking->save();

        $booking->logActivity(
            auth()->user(),
            'marketplace_provider_response',
            'Marketplace provider '.($validated['response'] === 'accepted' ? 'accepted' : 'declined').' the assignment.',
            [
                'provider_assignment_status' => $validated['response'],
                'cleaner_application_id' => $application->id,
            ]
        );

        return redirect()
            ->route('provider.bookings.show', $booking)
            ->with('success', 'Assignment response saved.');
    }

    private function payoutStats($payoutRows): array
    {
        $rows = collect($payoutRows);

        return [
            'gross' => round((float) $rows->sum('provider_gross_amount'), 2),
            'commission' => round((float) $rows->sum('platform_commission_amount'), 2),
            'payout' => round((float) $rows->sum('provider_payout_amount'), 2),
            'cash_collected' => round((float) $rows->where('payment_method', 'on_site_cash')->sum('cash_collected_amount'), 2),
            'commission_due' => round((float) $rows->where('provider_commission_status', 'unpaid')->sum('provider_commission_due'), 2),
            'commission_paid' => round((float) $rows->where('provider_commission_status', 'paid')->sum('provider_commission_due'), 2),
            'pending' => round((float) $rows->where('provider_payout_status', 'pending')->sum('provider_payout_amount'), 2),
            'ready' => round((float) $rows->where('provider_payout_status', 'ready')->sum('provider_payout_amount'), 2),
            'paid' => round((float) $rows->where('provider_payout_status', 'paid')->sum('provider_payout_amount'), 2),
            'held' => round((float) $rows->where('provider_payout_status', 'held')->sum('provider_payout_amount'), 2),
        ];
    }

    private function storePayoutDocument(CleanerApplication $application, string $documentType, $file): CleanerApplicationDocument
    {
        $path = $file->store('provider-documents/'.$application->id, config('filesystems.private_uploads_disk'));

        $oldDocuments = $application->documents()
            ->where('document_type', $documentType)
            ->get();

        foreach ($oldDocuments as $oldDocument) {
            Storage::disk(config('filesystems.private_uploads_disk'))->delete($oldDocument->file_path);
            $oldDocument->delete();
        }

        return $application->documents()->create([
            'document_type' => $documentType,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => auth()->id(),
        ]);
    }

    private function storeProofBatch(
        Booking $booking,
        array $files,
        string $stage,
        string $mediaType,
        User $uploadedBy,
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
                    'uploaded_by' => $uploadedBy->id,
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
