<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileStaffBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $staff = $request->user();

        if ($staff->role !== 'staff') {
            return response()->json([
                'message' => 'Only staff accounts can view assigned mobile bookings.',
            ], 403);
        }

        $bookings = $this->assignedBookingQuery($staff->id)
            ->orderByRaw("CASE WHEN status IN ('confirmed', 'in_progress') THEN 0 ELSE 1 END")
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get()
            ->map(fn (Booking $booking) => $this->bookingPayload($booking))
            ->values();

        return response()->json([
            'bookings' => $bookings,
            'stats' => [
                'total_assigned' => $bookings->count(),
                'confirmed' => $bookings->where('status', 'confirmed')->count(),
                'in_progress' => $bookings->where('status', 'in_progress')->count(),
                'completed' => $bookings->where('status', 'completed')->count(),
                'cancelled' => $bookings->where('status', 'cancelled')->count(),
                'total_earnings' => round((float) $bookings->where('status', 'completed')->sum('price'), 2),
            ],
        ]);
    }

    public function start(Request $request, Booking $booking): JsonResponse
    {
        $staff = $this->authorizeAssignedStaff($request, $booking, 'start');

        if (! $booking->canBeUpdatedByStaffTo('in_progress')) {
            throw ValidationException::withMessages([
                'status' => ['Only confirmed bookings can be started.'],
            ]);
        }

        $request->validate([
            'before_photos' => ['required', 'array', 'min:1', 'max:4'],
            'before_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'proof_captured_at' => ['required', 'date'],
            'proof_latitude' => ['required', 'numeric', 'between:-90,90'],
            'proof_longitude' => ['required', 'numeric', 'between:-180,180'],
            'proof_source' => ['required', Rule::in(['camera'])],
        ]);

        $proofMetadata = $this->proofMetadata($request);

        DB::transaction(function () use ($booking, $request, $staff, $proofMetadata): void {
            $beforePhotoCount = $this->storeProofBatch(
                $booking,
                $request->file('before_photos', []),
                'before',
                'image',
                $staff->id,
                $proofMetadata
            );

            $fromStatus = $booking->status;
            $booking->status = 'in_progress';
            $booking->markServiceStarted();
            $booking->save();

            $booking->logActivity($staff, 'mobile_proof_uploaded', 'Uploaded '.$beforePhotoCount.' before-service photo'.($beforePhotoCount === 1 ? '' : 's').' from the mobile app.', [
                'stage' => 'before',
                'media_type' => 'image',
                'count' => $beforePhotoCount,
                'captured_at' => $proofMetadata['captured_at']?->toISOString(),
                'latitude' => $proofMetadata['latitude'],
                'longitude' => $proofMetadata['longitude'],
                'capture_source' => $proofMetadata['capture_source'],
            ]);
            $this->logMobileStatusUpdate($booking, $staff, $fromStatus, 'in_progress', true);
            $this->notifyClient($booking, 'Service started with proof', 'Your cleaner has started booking '.$this->bookingCode($booking).' and uploaded '.$beforePhotoCount.' before-service photo'.($beforePhotoCount === 1 ? '' : 's').'.');
        });

        return $this->statusResponse($staff->id, $booking->id, 'Service started and proof uploaded.');
    }

    public function complete(Request $request, Booking $booking): JsonResponse
    {
        $staff = $this->authorizeAssignedStaff($request, $booking, 'complete');

        if (! $booking->canBeUpdatedByStaffTo('completed')) {
            throw ValidationException::withMessages([
                'status' => ['Only bookings that are already in progress can be marked as completed.'],
            ]);
        }

        if (! $booking->hasBeforeServiceProof()) {
            throw ValidationException::withMessages([
                'status' => ['Upload at least one before-service photo before completing this booking.'],
            ]);
        }

        $request->validate([
            'after_photos' => ['required', 'array', 'min:1', 'max:4'],
            'after_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'completion_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:'.config('cleanflow.proof_uploads.max_video_kb', 10240)],
            'proof_captured_at' => ['required', 'date'],
            'proof_latitude' => ['required', 'numeric', 'between:-90,90'],
            'proof_longitude' => ['required', 'numeric', 'between:-180,180'],
            'proof_source' => ['required', Rule::in(['camera'])],
        ]);

        $proofMetadata = $this->proofMetadata($request);

        DB::transaction(function () use ($booking, $request, $staff, $proofMetadata): void {
            $afterPhotoCount = $this->storeProofBatch(
                $booking,
                $request->file('after_photos', []),
                'after',
                'image',
                $staff->id,
                $proofMetadata
            );

            $videoUploaded = false;
            if ($request->hasFile('completion_video')) {
                /** @var UploadedFile $video */
                $video = $request->file('completion_video');
                $videoPath = $video->store('booking-proofs/after', config('filesystems.proof_uploads_disk'));

                $booking->serviceProofs()->create([
                    'uploaded_by' => $staff->id,
                    'stage' => 'after',
                    'media_type' => 'video',
                    'file_path' => $videoPath,
                    'original_name' => $video->getClientOriginalName(),
                    'captured_at' => $proofMetadata['captured_at'],
                    'latitude' => $proofMetadata['latitude'],
                    'longitude' => $proofMetadata['longitude'],
                    'capture_source' => $proofMetadata['capture_source'],
                ]);

                $videoUploaded = true;
            }

            $fromStatus = $booking->status;
            $booking->status = 'completed';
            $booking->markServiceCompleted();

            $booking->save();

            $booking->logActivity($staff, 'mobile_proof_uploaded', 'Uploaded '.$afterPhotoCount.' after-service photo'.($afterPhotoCount === 1 ? '' : 's').' from the mobile app.', [
                'stage' => 'after',
                'media_type' => 'image',
                'count' => $afterPhotoCount,
                'video_uploaded' => $videoUploaded,
                'captured_at' => $proofMetadata['captured_at']?->toISOString(),
                'latitude' => $proofMetadata['latitude'],
                'longitude' => $proofMetadata['longitude'],
                'capture_source' => $proofMetadata['capture_source'],
            ]);
            $this->logMobileStatusUpdate($booking, $staff, $fromStatus, 'completed', true);
            $this->notifyClient($booking, 'Service completed with proof', 'Your cleaner completed booking '.$this->bookingCode($booking).' and uploaded '.$afterPhotoCount.' after-service photo'.($afterPhotoCount === 1 ? '' : 's').'.');
        });

        return $this->statusResponse($staff->id, $booking->id, 'Service completed and proof uploaded.');
    }

    private function assignedBookingQuery(int $staffId)
    {
        return Booking::with(['user', 'rating', 'service', 'payment'])
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
            ->where('staff_id', $staffId)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed', 'cancelled']);
    }

    private function bookingPayload(Booking $booking): array
    {
        $scheduledDate = $booking->scheduled_date?->toDateString();
        $scheduledTime = $booking->scheduled_time
            ? Carbon::parse($booking->scheduled_time)->format('H:i')
            : '08:00';

        return [
            'id' => $booking->id,
            'code' => $this->bookingCode($booking),
            'client_name' => $booking->user?->full_name ?? 'Client',
            'client_phone' => $booking->user?->phone ?? '',
            'service_label' => $booking->service?->name ?? $booking->service_label,
            'status' => $booking->status,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'street_address' => $booking->street_address ?? '',
            'barangay' => $booking->barangay ?? '',
            'price' => (float) $booking->price,
            'before_photo_count' => (int) ($booking->before_photo_count ?? 0),
            'after_photo_count' => (int) ($booking->after_photo_count ?? 0),
            'completion_video_count' => (int) ($booking->completion_video_count ?? 0),
            'has_client_pin' => $booking->service_latitude !== null && $booking->service_longitude !== null,
            'payment_method' => $booking->payment_method,
            'payment_status' => $booking->payment_status,
            'rating' => $booking->rating ? [
                'stars' => (int) $booking->rating->stars,
                'comment' => $booking->rating->comment,
                'photo_label' => $booking->rating->photo ? 'Client review photo' : null,
                'created_at' => $booking->rating->created_at?->toISOString(),
            ] : null,
        ];
    }

    private function bookingCode(Booking $booking): string
    {
        return 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeAssignedStaff(Request $request, Booking $booking, string $action)
    {
        $staff = $request->user();

        if ($staff->role !== 'staff') {
            abort(response()->json([
                'message' => 'Only staff accounts can '.$action.' assigned mobile bookings.',
            ], 403));
        }

        if ((int) $booking->staff_id !== (int) $staff->id) {
            abort(response()->json([
                'message' => 'This booking is not assigned to your staff account.',
            ], 403));
        }

        return $staff;
    }

    private function storeProofBatch(
        Booking $booking,
        array $files,
        string $stage,
        string $mediaType,
        int $uploadedBy,
        array $metadata = []
    ): int {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('booking-proofs/'.$stage, config('filesystems.proof_uploads_disk'));

            $booking->serviceProofs()->create([
                'uploaded_by' => $uploadedBy,
                'stage' => $stage,
                'media_type' => $mediaType,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'captured_at' => $metadata['captured_at'] ?? null,
                'latitude' => $metadata['latitude'] ?? null,
                'longitude' => $metadata['longitude'] ?? null,
                'capture_source' => $metadata['capture_source'] ?? null,
            ]);
        }

        return count($files);
    }

    private function proofMetadata(Request $request): array
    {
        return [
            'captured_at' => $request->filled('proof_captured_at')
                ? Carbon::parse($request->input('proof_captured_at'))->utc()
                : now(),
            'latitude' => (float) $request->input('proof_latitude'),
            'longitude' => (float) $request->input('proof_longitude'),
            'capture_source' => $request->input('proof_source'),
        ];
    }

    private function statusResponse(int $staffId, int $bookingId, string $message): JsonResponse
    {
        $booking = $this->assignedBookingQuery($staffId)
            ->where('id', $bookingId)
            ->firstOrFail();

        return response()->json([
            'message' => $message,
            'booking' => $this->bookingPayload($booking),
        ]);
    }

    private function logMobileStatusUpdate(Booking $booking, $staff, string $fromStatus, string $toStatus, bool $proofUploaded = false): void
    {
        $booking->logActivity($staff, 'mobile_status_updated', 'Staff updated booking status from the mobile app.', [
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'proof_uploaded' => $proofUploaded,
        ]);
    }

    private function notifyClient(Booking $booking, string $title, string $message): void
    {
        Notification::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'title' => $title,
            'subject' => $title,
            'message' => $message,
            'type' => 'info',
            'link' => '/bookings/'.$booking->id,
            'sent_at' => now(),
        ]);
    }
}
