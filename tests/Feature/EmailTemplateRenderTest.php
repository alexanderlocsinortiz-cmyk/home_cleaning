<?php

namespace Tests\Feature;

use App\Mail\BookingCompleted;
use App\Mail\BookingConfirmed;
use App\Mail\BookingInProgress;
use App\Mail\BookingStaffAssigned;
use App\Mail\BookingSubmitted;
use App\Mail\CleanerApplicationDecision;
use App\Mail\MarketplaceProviderAssigned;
use App\Mail\ProviderPayoutPaid;
use App\Mail\QuickNotification;
use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\Notification;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailTemplateRenderTest extends TestCase
{
    public function test_booking_lifecycle_emails_render_without_encoding_artifacts(): void
    {
        [$booking] = $this->createBookingContext();

        $renderedSubmitted = (new BookingSubmitted($booking))->render();
        $renderedConfirmed = (new BookingConfirmed($booking))->render();
        $renderedAssigned = (new BookingStaffAssigned($booking))->render();
        $renderedInProgress = (new BookingInProgress($booking))->render();
        $renderedCompleted = (new BookingCompleted($booking))->render();

        $this->assertStringContainsString('Booking Submitted', $renderedSubmitted);
        $this->assertStringContainsString('Booking Confirmed', $renderedConfirmed);
        $this->assertStringContainsString('Cleaner Assigned', $renderedAssigned);
        $this->assertStringContainsString('Service In Progress', $renderedInProgress);
        $this->assertStringContainsString('Service Completed', $renderedCompleted);

        foreach ([$renderedSubmitted, $renderedConfirmed, $renderedAssigned, $renderedInProgress, $renderedCompleted] as $markup) {
            $this->assertStringNotContainsString('ð', $markup);
            $this->assertStringNotContainsString('â', $markup);
        }
    }

    public function test_quick_notification_and_verify_email_templates_render(): void
    {
        [$booking, $client] = $this->createBookingContext();

        $notification = Notification::create([
            'user_id' => $client->id,
            'booking_id' => $booking->id,
            'title' => 'Booking Update',
            'subject' => 'Booking Update - Home Cleaning Service',
            'message' => 'Your booking has a new status update.',
            'type' => 'booking_status',
            'link' => url('/bookings/'.$booking->id),
            'sent_at' => now(),
        ]);

        $quickNotificationMarkup = (new QuickNotification($notification))->render();
        $verifyEmailMarkup = view('emails.verify-email', [
            'user' => $client,
            'code' => '123456',
            'expiresInMinutes' => 15,
        ])->render();

        $this->assertStringContainsString('Booking Update - Home Cleaning Service', $quickNotificationMarkup);
        $this->assertStringContainsString('Your booking has a new status update.', $quickNotificationMarkup);
        $this->assertStringContainsString('Verify Your Email', $verifyEmailMarkup);
        $this->assertStringContainsString('123456', $verifyEmailMarkup);
        $this->assertStringNotContainsString('ð', $quickNotificationMarkup.$verifyEmailMarkup);
        $this->assertStringNotContainsString('â', $quickNotificationMarkup.$verifyEmailMarkup);
    }

    public function test_cleaner_application_decision_email_template_renders(): void
    {
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'bright-email-template@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
            'admin_notes' => 'Verified by phone.',
            'reviewed_at' => now(),
        ]);

        $rendered = (new CleanerApplicationDecision($application, 'sample-activation-token'))->render();

        $this->assertStringContainsString('Application Approved', $rendered);
        $this->assertStringContainsString('Bright Team Cleaners', $rendered);
        $this->assertStringContainsString('Approved', $rendered);
        $this->assertStringContainsString('Create Provider Account', $rendered);
        $this->assertStringContainsString('providers/activate/sample-activation-token', $rendered);
        $this->assertStringNotContainsString('Ã°', $rendered);
        $this->assertStringNotContainsString('Ã¢', $rendered);
    }

    public function test_marketplace_provider_assignment_email_template_renders(): void
    {
        [$booking] = $this->createBookingContext();
        $provider = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'bright-assigned@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);

        $rendered = (new MarketplaceProviderAssigned($booking, $provider))->render();

        $this->assertStringContainsString('Booking Assignment', $rendered);
        $this->assertStringContainsString('Bright Team Cleaners', $rendered);
        $this->assertStringContainsString('CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT), $rendered);
        $this->assertStringNotContainsString('Ã°', $rendered);
        $this->assertStringNotContainsString('Ã¢', $rendered);
    }

    public function test_provider_payout_paid_email_template_renders(): void
    {
        [$booking] = $this->createBookingContext();
        $provider = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'bright-payout-paid@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);
        $booking->forceFill([
            'cleaner_application_id' => $provider->id,
            'provider_payout_amount' => 1003,
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'GCASH-TEMPLATE-001',
            'provider_payout_paid_at' => '2026-06-08 15:30:00',
        ])->save();

        $rendered = (new ProviderPayoutPaid($booking->fresh(['service']), $provider))->render();

        $this->assertStringContainsString('Provider Payout Paid', $rendered);
        $this->assertStringContainsString('Bright Team Cleaners', $rendered);
        $this->assertStringContainsString('GCASH-TEMPLATE-001', $rendered);
        $this->assertStringContainsString('PHP 1,003.00', $rendered);
        $this->assertStringNotContainsString('ÃƒÂ°', $rendered);
        $this->assertStringNotContainsString('ÃƒÂ¢', $rendered);
    }

    /**
     * @return array{0: Booking, 1: User, 2: User}
     */
    private function createBookingContext(): array
    {
        $this->canonicalService([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $client = $this->createUser([
            'email' => 'email-client@example.com',
            'username' => 'emailclient',
            'role' => 'client',
            'first_name' => 'Client',
            'last_name' => 'User',
        ]);

        $staff = $this->createUser([
            'email' => 'email-staff@example.com',
            'username' => 'emailstaff',
            'role' => 'staff',
            'first_name' => 'Assigned',
            'last_name' => 'Cleaner',
            'phone' => '09170000000',
        ]);

        $booking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 35,
            'barangay' => 'poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1180,
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
            'payment_reference' => 'GCASH-TEST-001',
            'service_plan' => 'one_time',
            'status' => 'completed',
            'staff_id' => $staff->id,
            'preferred_staff_id' => $staff->id,
            'preferred_staff_status' => 'assigned',
        ]);

        return [$booking->fresh(['user', 'staff', 'preferredStaff', 'service']), $client, $staff];
    }

    private function createUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'testuser',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }
}
