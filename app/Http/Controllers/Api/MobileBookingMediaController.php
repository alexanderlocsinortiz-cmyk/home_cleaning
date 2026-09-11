<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingServiceProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobileBookingMediaController extends Controller
{
    public function proof(Request $request, Booking $booking, BookingServiceProof $proof)
    {
        $this->assertParticipant($request, $booking);
        abort_unless((int) $proof->booking_id === (int) $booking->id, 404);

        return $this->privateMediaResponse(
            $proof->file_path,
            $proof->original_name ?: basename($proof->file_path),
            config('filesystems.proof_uploads_disk'),
            true,
        );
    }

    public function ratingPhoto(Request $request, Booking $booking)
    {
        $this->assertParticipant($request, $booking);
        abort_unless($booking->rating?->photo, 404);

        return $this->privateMediaResponse(
            $booking->rating->photo,
            basename($booking->rating->photo),
            config('filesystems.proof_uploads_disk'),
            true,
        );
    }

    public function cashPaymentProof(Request $request, Booking $booking)
    {
        $user = $this->assertParticipant($request, $booking);
        abort_unless($user->role === 'client' && (int) $booking->user_id === (int) $user->id, 403);
        $booking->load('payment');

        abort_unless($booking->payment?->method === 'on_site_cash' && $booking->payment?->cash_proof_path, 404);

        return $this->privateMediaResponse(
            $booking->payment->cash_proof_path,
            $booking->payment->cash_proof_original_name ?: 'cash-payment-proof',
            config('filesystems.private_uploads_disk'),
        );
    }

    private function assertParticipant(Request $request, Booking $booking)
    {
        $user = $request->user();
        $allowed = $user && (($user->role === 'client' && (int) $booking->user_id === (int) $user->id)
            || ($user->role === 'staff' && $booking->isAssignedToStaff((int) $user->id)));
        abort_unless($allowed, 403);

        if ($user->role === 'client') {
            abort_unless($user->hasVerifiedEmail(), 403, 'Please verify your email before viewing booking media.');
        }

        return $user;
    }

    private function privateMediaResponse(string $path, string $name, string $disk, bool $allowLegacyPublic = false)
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path) && $allowLegacyPublic) {
            $legacyStorage = Storage::disk(config('filesystems.public_uploads_disk'));
            abort_unless($legacyStorage->exists($path), 404);
            $storage = $legacyStorage;
        } else {
            abort_unless($storage->exists($path), 404);
        }

        return $storage->response($path, $name, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
