<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobileBookingDetailsController extends Controller
{
    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->assertParticipant($request, $booking);
        $booking->load(['service', 'payment', 'rating', 'serviceProofs', 'messages.sender', 'staff', 'staffAssignments:id,booking_id,staff_id']);

        return response()->json(['booking' => $this->detailsPayload($booking, $request->user())]);
    }

    public function message(Request $request, Booking $booking): JsonResponse
    {
        $user = $this->assertParticipant($request, $booking);
        if (! $booking->staff_id) {
            return response()->json(['message' => 'Messaging is available after a cleaner has been assigned.'], 422);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);
        $message = $booking->messages()->create([
            'sender_id' => $user->id,
            'message' => trim(strip_tags($validated['message'])),
        ])->load('sender');

        $recipientId = (int) $user->id === (int) $booking->user_id ? $booking->staff_id : $booking->user_id;
        Notification::create([
            'user_id' => $recipientId,
            'booking_id' => $booking->id,
            'title' => 'New booking message',
            'message' => $user->display_name.' sent a message on '.$this->bookingCode($booking).'.',
            'type' => 'info',
            'link' => '/bookings/'.$booking->id,
        ]);

        return response()->json([
            'message' => 'Message sent.',
            'booking_message' => $this->messagePayload($message),
        ], 201);
    }

    public function uploadCashPaymentProof(Request $request, Booking $booking): JsonResponse
    {
        $user = $this->assertParticipant($request, $booking);
        abort_unless($user->role === 'client' && (int) $booking->user_id === (int) $user->id, 403);
        $booking->load('payment');
        $payment = $booking->payment;

        if ($payment?->method !== 'on_site_cash') {
            return response()->json(['message' => 'Cash payment proof is only available for cash bookings.'], 422);
        }
        if ($payment->status === 'paid') {
            return response()->json(['message' => 'This booking is already marked as paid.'], 422);
        }
        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'Cash payment proof can be uploaded after the service is completed.'], 422);
        }
        if ($payment->cash_proof_status === 'pending') {
            return response()->json(['message' => 'Your cash receipt is already waiting for admin review.'], 422);
        }

        $request->validate([
            'cash_payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $disk = config('filesystems.private_uploads_disk');
        $oldPath = $payment->cash_proof_path;
        $file = $request->file('cash_payment_proof');
        $path = $file->store('cash-payment-proofs', $disk);
        $payment->forceFill([
            'cash_proof_path' => $path,
            'cash_proof_original_name' => $file->getClientOriginalName(),
            'cash_proof_mime_type' => $file->getMimeType(),
            'cash_proof_size' => $file->getSize(),
            'cash_proof_status' => 'pending',
            'cash_proof_submitted_at' => now(),
            'cash_proof_reviewed_at' => null,
            'cash_proof_reviewed_by' => null,
            'cash_proof_rejection_reason' => null,
            'status' => 'pending',
        ])->save();
        if ($oldPath && $oldPath !== $path) Storage::disk($disk)->delete($oldPath);

        $booking->logActivity($user, 'cash_payment_proof_submitted', 'Client uploaded cash payment proof for admin review.', [
            'filename' => $payment->cash_proof_original_name,
            'size' => $payment->cash_proof_size,
        ]);

        foreach (\App\Models\User::where('role', 'admin')->get() as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'booking_id' => $booking->id,
                'title' => 'Cash payment proof submitted',
                'message' => 'The client uploaded cash payment proof for booking '.$this->bookingCode($booking).'.',
                'type' => 'warning',
                'link' => '/bookings/'.$booking->id,
            ]);
        }

        return response()->json(['message' => 'Cash payment proof uploaded for admin review.']);
    }

    private function assertParticipant(Request $request, Booking $booking)
    {
        $user = $request->user();
        $allowed = $user && (($user->role === 'client' && (int) $booking->user_id === (int) $user->id)
            || ($user->role === 'staff' && $booking->isAssignedToStaff((int) $user->id)));
        abort_unless($allowed, 403);

        if ($user->role === 'client') {
            abort_unless($user->hasVerifiedEmail(), 403, 'Please verify your email before using booking details.');
        }

        return $user;
    }

    private function detailsPayload(Booking $booking, $viewer): array
    {
        return [
            'id' => $booking->id,
            'code' => $this->bookingCode($booking),
            'status' => $booking->status,
            'can_cancel' => $viewer->role === 'client' && $booking->clientCanCancel(),
            'payment' => [
                'method' => $booking->payment?->method,
                'status' => $booking->payment?->status,
                'status_label' => Booking::paymentStatusLabel($booking->payment?->status),
                'online_payment_expiry_minutes' => Booking::isDigitalPaymentMethod($booking->payment?->method)
                    && $booking->payment?->status === 'pending'
                    ? (int) config('cleanflow.payments.unpaid_online_expiry_minutes', 30)
                    : null,
                'refund_status' => $booking->payment?->refund_status ?? 'none',
                'refund_status_label' => $booking->payment?->refundStatusLabel() ?? 'No refund requested',
                'amount' => (float) ($booking->payment?->amount ?? $booking->price),
                'reference' => $booking->payment?->reference,
                'receipt_number' => $booking->payment?->receipt_number,
                'collected_amount' => $booking->payment?->collected_amount,
                'collected_at' => $booking->payment?->collected_at?->toISOString(),
                'cash_proof_status' => $booking->payment?->cash_proof_status,
                'cash_proof_name' => $booking->payment?->cash_proof_original_name,
                'cash_proof_mime_type' => $booking->payment?->cash_proof_mime_type,
                'cash_proof_available' => $viewer->role === 'client' && (bool) $booking->payment?->cash_proof_path,
                'cash_proof_url' => $viewer->role === 'client' && $booking->payment?->cash_proof_path
                    ? route('api.mobile.booking.cash-payment-proof', $booking)
                    : null,
            ],
            'proofs' => $booking->serviceProofs->map(fn ($proof) => [
                'id' => $proof->id,
                'stage' => $proof->stage,
                'media_type' => $proof->media_type,
                'original_name' => $proof->original_name,
                'captured_at' => $proof->captured_at?->toISOString(),
                'media_url' => route('api.mobile.booking.proof', [$booking, $proof]),
            ])->values(),
            'rating' => $booking->rating ? [
                'stars' => (int) $booking->rating->stars,
                'comment' => $booking->rating->comment,
                'photo_available' => (bool) $booking->rating->photo,
                'photo_name' => $booking->rating->photo ? basename($booking->rating->photo) : null,
                'photo_url' => $booking->rating->photo
                    ? route('api.mobile.booking.rating-photo', $booking)
                    : null,
                'created_at' => $booking->rating->created_at?->toISOString(),
            ] : null,
            'dispute' => [
                'status' => $booking->dispute_status,
                'reason' => $booking->dispute_reason,
                'description' => $booking->dispute_description,
                'resolution' => $booking->dispute_resolution,
                'admin_notes' => $booking->dispute_admin_notes,
                'can_open' => $viewer->role === 'client' && $booking->canClientOpenDispute($viewer),
            ],
            'messages' => $booking->messages->map(fn ($message) => $this->messagePayload($message))->values(),
        ];
    }

    private function messagePayload($message): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'sender_name' => $message->sender?->display_name,
            'sender_id' => $message->sender_id,
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    private function bookingCode(Booking $booking): string
    {
        return 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
    }
}
