<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_confirm_a_booking_without_assigning_staff(): void
    {
        $admin = $this->createUser('admin', 'admin-status@example.com', 'adminstatus');
        $client = $this->createUser('client', 'client-status@example.com', 'clientstatus');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'confirmed',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('staff_id');
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_admin_cannot_assign_a_client_as_staff(): void
    {
        $admin = $this->createUser('admin', 'admin-assign@example.com', 'adminassign');
        $client = $this->createUser('client', 'client-assign@example.com', 'clientassign');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'pending',
                'staff_id' => $client->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('staff_id');
        $this->assertNull($booking->fresh()->staff_id);
    }

    public function test_admin_cannot_skip_pending_booking_straight_to_completed(): void
    {
        $admin = $this->createUser('admin', 'admin-skip@example.com', 'adminskip');
        $client = $this->createUser('client', 'client-skip@example.com', 'clientskip');
        $staff = $this->createUser('staff', 'staff-skip@example.com', 'staffskip');
        $booking = $this->createBooking($client, $staff, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'completed',
                'staff_id' => $staff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('status');
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_staff_can_move_confirmed_booking_to_in_progress(): void
    {
        $client = $this->createUser('client', 'client-progress@example.com', 'clientprogress');
        $staff = $this->createUser('staff', 'staff-progress@example.com', 'staffprogress');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        Storage::fake('public');

        $response = $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'in_progress',
                'before_photos' => [
                    $this->fakeImageUpload('before-proof.png'),
                ],
            ]);

        $response->assertRedirect(route('staff.bookings'));
        $response->assertSessionHas('success', 'Service marked as in progress and before-service proof has been uploaded.');

        $updatedBooking = $booking->fresh();

        $this->assertSame('in_progress', $updatedBooking->status);
        $this->assertDatabaseHas('booking_service_proofs', [
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'before',
            'media_type' => 'image',
        ]);
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $staff->id,
            'action' => 'status_updated',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'title' => 'Service started with proof',
        ]);
    }

    public function test_staff_cannot_mark_confirmed_booking_as_completed_directly(): void
    {
        $client = $this->createUser('client', 'client-complete@example.com', 'clientcomplete');
        $staff = $this->createUser('staff', 'staff-complete@example.com', 'staffcomplete');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $response = $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'completed',
            ]);

        $response->assertRedirect(route('staff.bookings'));
        $response->assertSessionHasErrors('status');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_staff_cannot_start_a_booking_without_before_service_photos(): void
    {
        $client = $this->createUser('client', 'client-no-before@example.com', 'clientnobefore');
        $staff = $this->createUser('staff', 'staff-no-before@example.com', 'staffnobefore');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $response = $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'in_progress',
            ]);

        $response->assertRedirect(route('staff.bookings'));
        $response->assertSessionHasErrors('before_photos');
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_staff_can_complete_an_in_progress_booking_with_after_service_proof(): void
    {
        Storage::fake('public');

        $client = $this->createUser('client', 'client-after-proof@example.com', 'clientafterproof');
        $staff = $this->createUser('staff', 'staff-after-proof@example.com', 'staffafterproof');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'in_progress',
                'before_photos' => [
                    $this->fakeImageUpload('before-proof.png'),
                ],
            ]);

        $response = $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'completed',
                'after_photos' => [
                    $this->fakeImageUpload('after-proof.png'),
                ],
                'completion_video' => UploadedFile::fake()->create('completion.mp4', 1024, 'video/mp4'),
            ]);

        $response->assertRedirect(route('staff.bookings'));
        $response->assertSessionHas('success', 'Service marked as completed and proof of service has been uploaded.');

        $completedBooking = $booking->fresh();

        $this->assertSame('completed', $completedBooking->status);
        $this->assertDatabaseHas('booking_service_proofs', [
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'after',
            'media_type' => 'image',
        ]);
        $this->assertDatabaseHas('booking_service_proofs', [
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'after',
            'media_type' => 'video',
        ]);
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $staff->id,
            'action' => 'proof_uploaded',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'title' => 'Service completed with proof',
        ]);
    }

    public function test_staff_cannot_complete_a_booking_without_before_service_proof(): void
    {
        Storage::fake('public');

        $client = $this->createUser('client', 'client-before-required@example.com', 'clientbeforerequired');
        $staff = $this->createUser('staff', 'staff-before-required@example.com', 'staffbeforerequired');
        $booking = $this->createBooking($client, $staff, 'in_progress');

        $response = $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'completed',
                'after_photos' => [
                    $this->fakeImageUpload('after-only.png'),
                ],
            ]);

        $response->assertRedirect(route('staff.bookings'));
        $response->assertSessionHasErrors('status');
        $this->assertSame('in_progress', $booking->fresh()->status);
    }

    public function test_admin_cannot_confirm_a_booking_that_is_pending_manual_review(): void
    {
        $admin = $this->createUser('admin', 'admin-review-block@example.com', 'adminreviewblock');
        $client = $this->createUser('client', 'client-review-block@example.com', 'clientreviewblock');
        $staff = $this->createUser('staff', 'staff-review-block@example.com', 'staffreviewblock');
        $booking = $this->createBooking($client, null, 'pending');
        $booking->forceFill([
            'manual_review_status' => 'pending',
            'risk_reasons' => ['Another client already requested this exact address and schedule.'],
        ])->save();

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'confirmed',
                'staff_id' => $staff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('status');
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->staff_id);
    }

    public function test_admin_can_approve_manual_review_then_confirm_booking(): void
    {
        $admin = $this->createUser('admin', 'admin-review-approve@example.com', 'adminreviewapprove');
        $client = $this->createUser('client', 'client-review-approve@example.com', 'clientreviewapprove');
        $staff = $this->createUser('staff', 'staff-review-approve@example.com', 'staffreviewapprove');
        $booking = $this->createBooking($client, null, 'pending');
        $booking->forceFill([
            'manual_review_status' => 'pending',
            'risk_reasons' => ['Another client already requested this exact address and schedule.'],
        ])->save();

        $reviewResponse = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.review', $booking->id), [
                'review_status' => 'approved',
            ]);

        $reviewResponse->assertRedirect(route('admin.bookings'));
        $reviewResponse->assertSessionHas('success', 'Booking cleared for normal scheduling and confirmation.');

        $reviewedBooking = $booking->fresh();
        $this->assertSame('approved', $reviewedBooking->manual_review_status);
        $this->assertSame($admin->id, $reviewedBooking->reviewed_by);
        $this->assertNotNull($reviewedBooking->reviewed_at);

        $confirmResponse = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'confirmed',
                'staff_id' => $staff->id,
            ]);

        $confirmResponse->assertRedirect(route('admin.bookings'));
        $confirmResponse->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame($staff->id, $booking->fresh()->staff_id);
    }

    public function test_admin_can_block_suspicious_booking_and_cancel_it_from_operations(): void
    {
        $admin = $this->createUser('admin', 'admin-review-cancel@example.com', 'adminreviewcancel');
        $client = $this->createUser('client', 'client-review-cancel@example.com', 'clientreviewcancel');
        $booking = $this->createBooking($client, null, 'pending');
        $booking->forceFill([
            'manual_review_status' => 'pending',
            'risk_reasons' => ['This client has created multiple booking requests within the last 24 hours.'],
        ])->save();

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.review', $booking->id), [
                'review_status' => 'blocked',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHas('success', 'Booking blocked during manual review and removed from the active queue.');

        $blockedBooking = $booking->fresh();
        $this->assertSame('blocked', $blockedBooking->manual_review_status);
        $this->assertSame('cancelled', $blockedBooking->status);
        $this->assertSame($admin->id, $blockedBooking->reviewed_by);
        $this->assertNotNull($blockedBooking->reviewed_at);
    }

    public function test_admin_cannot_assign_a_staff_member_to_two_active_bookings_in_the_same_time_slot(): void
    {
        $admin = $this->createUser('admin', 'admin-overlap@example.com', 'adminoverlap');
        $clientOne = $this->createUser('client', 'client-one-overlap@example.com', 'clientoneoverlap');
        $clientTwo = $this->createUser('client', 'client-two-overlap@example.com', 'clienttwooverlap');
        $staff = $this->createUser('staff', 'staff-overlap@example.com', 'staffoverlap');
        $scheduledDate = now()->addDays(2)->toDateString();

        $this->createBooking($clientOne, $staff, 'confirmed', $scheduledDate, '09:00');
        $bookingToAssign = $this->createBooking($clientTwo, null, 'pending', $scheduledDate, '09:00');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $bookingToAssign->id), [
                'status' => 'confirmed',
                'staff_id' => $staff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('staff_id');
        $this->assertNull($bookingToAssign->fresh()->staff_id);
        $this->assertSame('pending', $bookingToAssign->fresh()->status);
    }

    public function test_admin_can_assign_a_different_available_staff_member_for_the_same_time_slot(): void
    {
        $admin = $this->createUser('admin', 'admin-available@example.com', 'adminavailable');
        $clientOne = $this->createUser('client', 'client-one-available@example.com', 'clientoneavailable');
        $clientTwo = $this->createUser('client', 'client-two-available@example.com', 'clienttwoavailable');
        $busyStaff = $this->createUser('staff', 'busy-staff@example.com', 'busystaff');
        $availableStaff = $this->createUser('staff', 'free-staff@example.com', 'freestaff');
        $scheduledDate = now()->addDays(3)->toDateString();

        $this->createBooking($clientOne, $busyStaff, 'confirmed', $scheduledDate, '10:00');
        $bookingToAssign = $this->createBooking($clientTwo, null, 'pending', $scheduledDate, '10:00');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $bookingToAssign->id), [
                'status' => 'confirmed',
                'staff_id' => $availableStaff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasNoErrors();
        $this->assertSame($availableStaff->id, $bookingToAssign->fresh()->staff_id);
        $this->assertSame('confirmed', $bookingToAssign->fresh()->status);
    }

    public function test_admin_assigning_the_requested_cleaner_updates_preference_status_and_notifies_the_client(): void
    {
        $admin = $this->createUser('admin', 'admin-preferred-assign@example.com', 'adminpreferredassign');
        $client = $this->createUser('client', 'client-preferred-assign@example.com', 'clientpreferredassign');
        $preferredCleaner = $this->createUser('staff', 'preferred-assign-cleaner@example.com', 'preferredassigncleaner');
        $preferredCleaner->update([
            'first_name' => 'Preferred',
            'last_name' => 'Cleaner',
        ]);

        $booking = $this->createBooking($client, null, 'pending');
        $booking->forceFill([
            'preferred_staff_id' => $preferredCleaner->id,
            'preferred_staff_status' => 'requested',
        ])->save();

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'confirmed',
                'staff_id' => $preferredCleaner->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasNoErrors();

        $updatedBooking = $booking->fresh();
        $notification = Notification::where('user_id', $client->id)
            ->where('title', 'Preferred cleaner assigned')
            ->latest()
            ->first();

        $this->assertSame('confirmed', $updatedBooking->status);
        $this->assertSame($preferredCleaner->id, $updatedBooking->staff_id);
        $this->assertSame('assigned', $updatedBooking->preferred_staff_status);
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Your booking is now confirmed.', $notification->message);
    }

    public function test_admin_assigning_an_alternative_cleaner_updates_preference_status_and_notifies_the_client(): void
    {
        $admin = $this->createUser('admin', 'admin-alternative-assign@example.com', 'adminalternativeassign');
        $client = $this->createUser('client', 'client-alternative-assign@example.com', 'clientalternativeassign');
        $preferredCleaner = $this->createUser('staff', 'preferred-alt-cleaner@example.com', 'preferredaltcleaner');
        $alternateCleaner = $this->createUser('staff', 'alternate-cleaner@example.com', 'alternatecleaner');
        $preferredCleaner->update([
            'first_name' => 'Requested',
            'last_name' => 'Cleaner',
        ]);
        $alternateCleaner->update([
            'first_name' => 'Alternate',
            'last_name' => 'Cleaner',
        ]);

        $booking = $this->createBooking($client, null, 'pending');
        $booking->forceFill([
            'preferred_staff_id' => $preferredCleaner->id,
            'preferred_staff_status' => 'requested',
        ])->save();

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'confirmed',
                'staff_id' => $alternateCleaner->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasNoErrors();

        $updatedBooking = $booking->fresh();
        $notification = Notification::where('user_id', $client->id)
            ->where('title', 'Alternative cleaner assigned')
            ->latest()
            ->first();

        $this->assertSame('confirmed', $updatedBooking->status);
        $this->assertSame($alternateCleaner->id, $updatedBooking->staff_id);
        $this->assertSame('alternate_assigned', $updatedBooking->preferred_staff_status);
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Requested Cleaner', $notification->message);
        $this->assertStringContainsString('Alternate Cleaner', $notification->message);
    }

    public function test_completed_or_cancelled_bookings_do_not_block_new_staff_assignments(): void
    {
        $admin = $this->createUser('admin', 'admin-history-slot@example.com', 'adminhistoryslot');
        $clientOne = $this->createUser('client', 'client-one-history@example.com', 'clientonehistory');
        $clientTwo = $this->createUser('client', 'client-two-history@example.com', 'clienttwohistory');
        $staff = $this->createUser('staff', 'history-staff@example.com', 'historystaff');
        $scheduledDate = now()->addDays(4)->toDateString();

        $this->createBooking($clientOne, $staff, 'completed', $scheduledDate, '11:00');
        $bookingToAssign = $this->createBooking($clientTwo, null, 'pending', $scheduledDate, '11:00');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $bookingToAssign->id), [
                'status' => 'confirmed',
                'staff_id' => $staff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasNoErrors();
        $this->assertSame($staff->id, $bookingToAssign->fresh()->staff_id);
        $this->assertSame('confirmed', $bookingToAssign->fresh()->status);
    }

    public function test_admin_can_mark_a_pending_payment_as_paid(): void
    {
        $admin = $this->createUser('admin', 'admin-payment@example.com', 'adminpayment');
        $client = $this->createUser('client', 'client-payment@example.com', 'clientpayment');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.payment', $booking->id), [
                'payment_status' => 'paid',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHas('success', 'Payment status updated successfully.');

        $updatedBooking = $booking->fresh();
        $notification = Notification::where('user_id', $client->id)
            ->where('title', 'Payment confirmed')
            ->latest()
            ->first();

        $this->assertSame('paid', $updatedBooking->payment_status);
        $this->assertNotNull($updatedBooking->payment_reference);
        $this->assertNotNull($updatedBooking->paid_at);
        $this->assertNotNull($notification);
    }

    public function test_completing_an_on_site_cash_booking_marks_it_paid_automatically(): void
    {
        $admin = $this->createUser('admin', 'admin-cash-complete@example.com', 'admincashcomplete');
        $client = $this->createUser('client', 'client-cash-complete@example.com', 'clientcashcomplete');
        $staff = $this->createUser('staff', 'staff-cash-complete@example.com', 'staffcashcomplete');
        $booking = $this->createBooking($client, $staff, 'in_progress');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'completed',
                'staff_id' => $staff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasNoErrors();

        $completedBooking = $booking->fresh();

        $this->assertSame('completed', $completedBooking->status);
        $this->assertSame('paid', $completedBooking->payment_status);
        $this->assertNotNull($completedBooking->payment_reference);
        $this->assertNotNull($completedBooking->paid_at);
    }

    private function createUser(string $role, string $email, string $username): User
    {
        $user = User::create([
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function createBooking(
        User $client,
        ?User $staff,
        string $status,
        ?string $scheduledDate = null,
        string $scheduledTime = '09:00'
    ): Booking {
        return Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => $scheduledDate ?? now()->addDay()->toDateString(),
            'scheduled_time' => $scheduledTime,
            'price' => 1200,
            'status' => $status,
            'staff_id' => $staff?->id,
        ]);
    }

    private function fakeImageUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+yF9sAAAAASUVORK5CYII=')
        );
    }
}
