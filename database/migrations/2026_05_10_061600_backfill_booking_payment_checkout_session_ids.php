<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Booking::query()
            ->whereNull('payment_checkout_session_id')
            ->where('payment_reference', 'like', 'cs_%')
            ->chunkById(100, function ($bookings): void {
                foreach ($bookings as $booking) {
                    $booking->forceFill([
                        'payment_checkout_session_id' => $booking->payment_reference,
                    ])->save();
                }
            });
    }

    public function down(): void
    {
        Booking::query()
            ->whereColumn('payment_checkout_session_id', 'payment_reference')
            ->where('payment_reference', 'like', 'cs_%')
            ->update(['payment_checkout_session_id' => null]);
    }
};
