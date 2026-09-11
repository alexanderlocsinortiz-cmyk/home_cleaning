<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExpireUnpaidOnlineBookingsCommandTest extends TestCase
{
    public function test_it_cancels_old_unpaid_online_bookings_and_notifies_the_client_once(): void
    {
        $now = Carbon::parse('2026-09-10 02:00:00', 'UTC');
        Carbon::setTestNow($now);

        try {
            $client = User::factory()->create(['role' => 'client']);
            $bookings = collect(['pending', 'confirmed'])->map(function (string $status, int $index) use ($client, $now): Booking {
                $booking = Booking::factory()->create([
                    'user_id' => $client->id,
                    'status' => $status,
                    'payment_method' => 'gcash',
                    'scheduled_date' => '2026-09-11',
                    'scheduled_time' => $index === 0 ? '09:00' : '10:00',
                    'created_at' => $now->copy()->subMinutes(31),
                ]);
                $booking->payment->forceFill([
                    'method' => 'gcash',
                    'status' => 'pending',
                    'provider' => 'paymongo',
                    'created_at' => $now->copy()->subMinutes(31),
                ])->save();

                return $booking;
            });

            $this->assertSame(0, Artisan::call('bookings:expire-unpaid-online'));

            foreach ($bookings as $booking) {
                $this->assertSame('cancelled', $booking->fresh()->status);
                $this->assertDatabaseHas('booking_activity_logs', [
                    'booking_id' => $booking->id,
                    'action' => 'status_updated',
                ]);
                $this->assertDatabaseHas('notifications', [
                    'booking_id' => $booking->id,
                    'user_id' => $client->id,
                    'type' => 'booking_status',
                    'dedupe_key' => 'booking-payment-expired:'.$booking->id,
                ]);
            }

            $this->assertSame(2, Notification::where('type', 'booking_status')->count());

            Artisan::call('bookings:expire-unpaid-online');

            $this->assertSame(2, Notification::where('type', 'booking_status')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_leaves_a_recent_unpaid_online_booking_active(): void
    {
        $now = Carbon::parse('2026-09-10 02:00:00', 'UTC');
        Carbon::setTestNow($now);

        try {
            $booking = Booking::factory()->create([
                'status' => 'confirmed',
                'payment_method' => 'maya',
                'scheduled_date' => '2026-09-11',
            ]);
            $booking->payment->forceFill([
                'method' => 'maya',
                'status' => 'pending',
                'provider' => 'paymongo',
                'created_at' => $now->copy()->subMinutes(29),
            ])->save();

            Artisan::call('bookings:expire-unpaid-online');

            $this->assertSame('confirmed', $booking->fresh()->status);
            $this->assertSame(0, Notification::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_never_expires_a_paid_online_booking(): void
    {
        $now = Carbon::parse('2026-09-10 02:00:00', 'UTC');
        Carbon::setTestNow($now);

        try {
            $booking = Booking::factory()->create([
                'status' => 'confirmed',
                'payment_method' => 'gcash',
                'scheduled_date' => '2026-09-11',
            ]);
            $booking->payment->forceFill([
                'method' => 'gcash',
                'status' => 'paid',
                'provider' => 'paymongo',
                'created_at' => $now->copy()->subHour(),
                'paid_at' => $now->copy()->subMinutes(31),
            ])->save();

            Artisan::call('bookings:expire-unpaid-online');

            $this->assertSame('confirmed', $booking->fresh()->status);
            $this->assertSame(0, Notification::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_does_not_expire_an_assigned_unpaid_online_booking(): void
    {
        $now = Carbon::parse('2026-09-10 02:00:00', 'UTC');
        Carbon::setTestNow($now);

        try {
            $staff = User::factory()->create(['role' => 'staff']);
            $booking = Booking::factory()->create([
                'staff_id' => $staff->id,
                'status' => 'confirmed',
                'payment_method' => 'gcash',
                'scheduled_date' => '2026-09-11',
            ]);
            $booking->payment->forceFill([
                'method' => 'gcash',
                'status' => 'pending',
                'provider' => 'paymongo',
                'created_at' => $now->copy()->subMinutes(31),
            ])->save();

            Artisan::call('bookings:expire-unpaid-online');

            $this->assertSame('confirmed', $booking->fresh()->status);
            $this->assertSame(0, Notification::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_does_not_expire_a_cash_booking(): void
    {
        $now = Carbon::parse('2026-09-10 02:00:00', 'UTC');
        Carbon::setTestNow($now);

        try {
            $booking = Booking::factory()->create([
                'status' => 'pending',
                'payment_method' => 'on_site_cash',
                'scheduled_date' => '2026-09-11',
            ]);
            $booking->payment->forceFill([
                'method' => 'on_site_cash',
                'status' => 'pending',
                'provider' => 'manual',
                'created_at' => $now->copy()->subMinutes(31),
            ])->save();

            Artisan::call('bookings:expire-unpaid-online');

            $this->assertSame('pending', $booking->fresh()->status);
            $this->assertSame(0, Notification::count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
