<?php

namespace Tests\Feature;

use App\Jobs\SendBookingReminderEmail;
use App\Mail\QuickNotification;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingReminderCommandTest extends TestCase
{
    public function test_confirmed_booking_reminders_are_created_once_for_client_and_staff(): void
    {
        $now = Carbon::parse('2026-09-09 08:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);
        Bus::fake();

        try {
            $client = User::factory()->create(['role' => 'client']);
            $staff = User::factory()->create(['role' => 'staff']);
            $booking = Booking::factory()->create([
                'user_id' => $client->id,
                'staff_id' => $staff->id,
                'status' => 'confirmed',
                'scheduled_date' => '2026-09-10',
                'scheduled_time' => '08:00:00',
            ]);

            $this->assertSame(0, Artisan::call('bookings:send-reminders'));
            $this->assertSame(2, Notification::where('booking_id', $booking->id)->count());
            $this->assertDatabaseHas('notifications', [
                'booking_id' => $booking->id,
                'user_id' => $client->id,
                'type' => 'booking_reminder',
                'dedupe_key' => "booking-reminder:{$booking->id}:{$client->id}:24h",
            ]);
            $this->assertDatabaseHas('notifications', [
                'booking_id' => $booking->id,
                'user_id' => $staff->id,
                'type' => 'booking_reminder',
                'dedupe_key' => "booking-reminder:{$booking->id}:{$staff->id}:24h",
            ]);
            Bus::assertDispatched(SendBookingReminderEmail::class, 2);

            $this->assertSame(0, Artisan::call('bookings:send-reminders'));
            $this->assertSame(2, Notification::where('booking_id', $booking->id)->count());
            Bus::assertDispatched(SendBookingReminderEmail::class, 2);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_one_hour_reminder_is_created_for_a_client_without_an_assigned_worker(): void
    {
        $now = Carbon::parse('2026-09-09 07:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);
        Bus::fake();

        try {
            $client = User::factory()->create(['role' => 'client']);
            $booking = Booking::factory()->create([
                'user_id' => $client->id,
                'status' => 'confirmed',
                'scheduled_date' => '2026-09-09',
                'scheduled_time' => '08:00:00',
            ]);

            Artisan::call('bookings:send-reminders');

            $this->assertDatabaseHas('notifications', [
                'booking_id' => $booking->id,
                'user_id' => $client->id,
                'title' => 'Booking starts soon',
                'dedupe_key' => "booking-reminder:{$booking->id}:{$client->id}:1h",
            ]);
            $this->assertSame(1, Notification::where('booking_id', $booking->id)->count());
            Bus::assertDispatched(SendBookingReminderEmail::class, 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reminder_email_job_sends_once_and_marks_notification_sent(): void
    {
        Mail::fake();
        $recipient = User::factory()->create(['role' => 'client']);
        $notification = Notification::create([
            'user_id' => $recipient->id,
            'subject' => 'Booking reminder - Home Cleaning Service',
            'title' => 'Booking tomorrow',
            'message' => 'Your booking is scheduled tomorrow.',
            'type' => 'booking_reminder',
            'dedupe_key' => 'booking-reminder:test:email',
        ]);

        (new SendBookingReminderEmail($notification->id))->handle();

        Mail::assertSent(QuickNotification::class, function (QuickNotification $mail) use ($notification): bool {
            return $mail->notification->is($notification);
        });
        $this->assertNotNull($notification->fresh()->sent_at);

        (new SendBookingReminderEmail($notification->id))->handle();
        Mail::assertSentCount(1);
    }
}
