<?php

namespace Tests\Feature;

use App\Mail\MarketplaceProviderAssigned;
use App\Mail\ProviderPayoutPaid;
use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\ProviderPayoutTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBookingManagementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bookings_page_defaults_to_active_operational_queue(): void
    {
        $admin = $this->createUser('admin', 'admin-bookings@example.com', 'adminbookings');
        $client = $this->createUser('client', 'client-bookings@example.com', 'clientbookings');
        $staff = $this->createUser('staff', 'staff-bookings@example.com', 'staffbookings');

        $pending = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $confirmed = $this->createBooking($client, $staff, 'confirmed', now()->addDays(2)->toDateString(), '10:00');
        $inProgress = $this->createBooking($client, $staff, 'in_progress', now()->toDateString(), '08:00');
        $completed = $this->createBooking($client, $staff, 'completed', now()->subDay()->toDateString(), '11:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('Active Booking Queue');
        $response->assertSee('Completed Bookings');
        $response->assertSee('CF-'.str_pad($pending->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('CF-'.str_pad($confirmed->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('CF-'.str_pad($inProgress->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('CF-'.str_pad($completed->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_admin_can_assign_staff_to_a_future_booking_before_they_punch_in(): void
    {
        $admin = $this->createUser('admin', 'admin-future-assignment@example.com', 'adminfutureassignment');
        $client = $this->createUser('client', 'client-future-assignment@example.com', 'clientfutureassignment');
        $staff = $this->createUser('staff', 'staff-future-assignment@example.com', 'stafffutureassignment');
        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('value="'.$staff->id.'"', false);
        $response->assertSee($staff->display_name);
    }

    public function test_admin_bookings_page_can_filter_unassigned_active_queue(): void
    {
        $admin = $this->createUser('admin', 'admin-unassigned-filter@example.com', 'adminunassignedfilter');
        $client = $this->createUser('client', 'client-unassigned-filter@example.com', 'clientunassignedfilter');
        $staff = $this->createUser('staff', 'staff-unassigned-filter@example.com', 'staffunassignedfilter');

        $unassigned = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $assigned = $this->createBooking($client, $staff, 'confirmed', now()->addDays(2)->toDateString(), '10:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings', ['tab' => 'active', 'filter' => 'unassigned']));

        $response->assertOk();
        $response->assertSee('Unassigned');
        $response->assertSee('CF-'.str_pad($unassigned->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('CF-'.str_pad($assigned->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_admin_can_open_completed_history_tab_without_operational_controls(): void
    {
        $admin = $this->createUser('admin', 'admin-history@example.com', 'adminhistory');
        $client = $this->createUser('client', 'client-history@example.com', 'clienthistory');
        $staff = $this->createUser('staff', 'staff-history@example.com', 'staffhistory');

        $completed = $this->createBooking($client, $staff, 'completed', now()->subDays(2)->toDateString(), '13:00');
        $cancelled = $this->createBooking($client, null, 'cancelled', now()->subDays(3)->toDateString(), '14:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings', ['tab' => 'completed']));

        $response->assertOk();
        $response->assertSee('Completed Booking History');
        $response->assertSee('CF-'.str_pad($completed->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('CF-'.str_pad($cancelled->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('View Details');
        $response->assertSee('No rating yet');
        $response->assertDontSee('Current staff:');
        $response->assertDontSee('Active Booking Queue');
    }

    public function test_admin_in_progress_booking_links_to_proof_files(): void
    {
        $admin = $this->createUser('admin', 'admin-proof-link@example.com', 'adminprooflink');
        $client = $this->createUser('client', 'client-proof-link@example.com', 'clientprooflink');
        $staff = $this->createUser('staff', 'staff-proof-link@example.com', 'staffprooflink');
        $booking = $this->createBooking($client, $staff, 'in_progress', now()->toDateString(), '09:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('View proof files');
        $response->assertSee(route('bookings.show', $booking->id).'#proof-of-service', false);
    }

    public function test_admin_bookings_page_shows_requested_cleaner_details_when_present(): void
    {
        $admin = $this->createUser('admin', 'admin-requested-cleaner@example.com', 'adminrequestedcleaner');
        $client = $this->createUser('client', 'client-requested-cleaner@example.com', 'clientrequestedcleaner');
        $requestedCleaner = $this->createUser('staff', 'requested-cleaner@example.com', 'requestedcleaner');
        $requestedCleaner->update([
            'first_name' => 'Preferred',
            'last_name' => 'Cleaner',
        ]);

        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $booking->forceFill([
            'preferred_staff_id' => $requestedCleaner->id,
            'preferred_staff_status' => 'requested',
        ])->save();

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('Preferred cleaner');
        $response->assertSee($requestedCleaner->full_name);
        $response->assertSee('Requested');
    }

    public function test_admin_bookings_page_shows_payment_and_subscription_details_when_present(): void
    {
        $admin = $this->createUser('admin', 'admin-payment-queue@example.com', 'adminpaymentqueue');
        $client = $this->createUser('client', 'client-payment-queue@example.com', 'clientpaymentqueue');
        $booking = $this->createBooking($client, null, 'pending', now()->addDays(2)->toDateString(), '09:00');

        $booking->forceFill([
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
            'payment_reference' => 'GCASH-QUEUE-12345',
            'paid_at' => now(),
            'service_plan' => 'subscription',
            'subscription_frequency' => 'weekly',
            'subscription_occurrences' => 4,
            'subscription_group_id' => 'queue-group',
            'subscription_sequence' => 2,
        ])->save();

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('GCash');
        $response->assertSee('Paid');
        $response->assertSee('Weekly');
        $response->assertSee('Visit 2');
    }

    public function test_admin_can_assign_approved_cleaner_application_to_booking(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin', 'admin-provider-assign@example.com', 'adminproviderassign');
        $client = $this->createUser('client', 'client-provider-assign@example.com', 'clientproviderassign');
        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);

        $response = $this->actingAs($admin)->patch(route('admin.bookings.provider', $booking->id), [
            'cleaner_application_id' => $provider->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Marketplace provider assignment has been updated. Provider email notification queued.');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('booking_payouts', [
            'booking_id' => $booking->id,
            'provider_gross_amount' => 1200,
            'platform_commission_rate' => 0.15,
            'platform_commission_amount' => 180,
            'provider_payout_amount' => 1020,
            'provider_payout_status' => 'pending',
            'provider_commission_status' => 'not_applicable',
        ]);

        Mail::assertSent(MarketplaceProviderAssigned::class, function (MarketplaceProviderAssigned $mail) use ($booking, $provider) {
            return $mail->hasTo($provider->email)
                && $mail->booking->id === $booking->id
                && $mail->provider->id === $provider->id;
        });
    }

    public function test_clearing_marketplace_provider_does_not_send_assignment_email(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin', 'admin-provider-clear@example.com', 'adminproviderclear');
        $client = $this->createUser('client', 'client-provider-clear@example.com', 'clientproviderclear');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $booking->forceFill(['cleaner_application_id' => $provider->id])->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.provider', $booking->id), [
            'cleaner_application_id' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Marketplace provider assignment has been cleared.');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'cleaner_application_id' => null,
        ]);
        $this->assertDatabaseHas('booking_payouts', [
            'booking_id' => $booking->id,
            'provider_gross_amount' => null,
            'platform_commission_rate' => null,
            'platform_commission_amount' => null,
            'provider_payout_amount' => null,
            'provider_payout_status' => null,
        ]);

        Mail::assertNotSent(MarketplaceProviderAssigned::class);
    }

    public function test_admin_cannot_assign_pending_cleaner_application_to_booking(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-pending@example.com', 'adminproviderpending');
        $client = $this->createUser('client', 'client-provider-pending@example.com', 'clientproviderpending');
        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_PENDING);

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.provider', $booking->id), [
            'cleaner_application_id' => $provider->id,
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('cleaner_application_id');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'cleaner_application_id' => null,
        ]);
    }

    public function test_admin_cannot_assign_provider_outside_booking_service_area(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-area-block@example.com', 'adminproviderareablock');
        $client = $this->createUser('client', 'client-provider-area-block@example.com', 'clientproviderareablock');
        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED, 'Lourdes');

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.provider', $booking->id), [
            'cleaner_application_id' => $provider->id,
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('cleaner_application_id');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'cleaner_application_id' => null,
        ]);
    }

    public function test_admin_cannot_assign_paused_provider(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-paused-block@example.com', 'adminproviderpausedblock');
        $client = $this->createUser('client', 'client-provider-paused-block@example.com', 'clientproviderpausedblock');
        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $provider->forceFill(['availability_status' => CleanerApplication::AVAILABILITY_PAUSED])->save();

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.provider', $booking->id), [
            'cleaner_application_id' => $provider->id,
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('cleaner_application_id');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'cleaner_application_id' => null,
        ]);
    }

    public function test_admin_cannot_assign_provider_at_daily_limit(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-limit-block@example.com', 'adminproviderlimitblock');
        $client = $this->createUser('client', 'client-provider-limit-block@example.com', 'clientproviderlimitblock');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $provider->forceFill(['max_daily_bookings' => 1])->save();
        $scheduledDate = now()->addDay()->toDateString();
        $existingBooking = $this->createBooking($client, null, 'confirmed', $scheduledDate, '08:00');
        $existingBooking->forceFill([
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'accepted',
        ])->save();
        $targetBooking = $this->createBooking($client, null, 'pending', $scheduledDate, '10:00');

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.provider', $targetBooking->id), [
            'cleaner_application_id' => $provider->id,
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('cleaner_application_id');

        $this->assertDatabaseHas('bookings', [
            'id' => $targetBooking->id,
            'cleaner_application_id' => null,
        ]);
    }

    public function test_declined_provider_assignment_does_not_count_against_daily_limit(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin', 'admin-provider-limit-declined@example.com', 'adminproviderlimitdeclined');
        $client = $this->createUser('client', 'client-provider-limit-declined@example.com', 'clientproviderlimitdeclined');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $provider->forceFill(['max_daily_bookings' => 1])->save();
        $scheduledDate = now()->addDay()->toDateString();
        $declinedBooking = $this->createBooking($client, null, 'confirmed', $scheduledDate, '08:00');
        $declinedBooking->forceFill([
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'declined',
        ])->save();
        $targetBooking = $this->createBooking($client, null, 'pending', $scheduledDate, '10:00');

        $response = $this->actingAs($admin)->patch(route('admin.bookings.provider', $targetBooking->id), [
            'cleaner_application_id' => $provider->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $targetBooking->id,
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'pending',
        ]);
    }

    public function test_admin_booking_dropdown_marks_providers_outside_service_area(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-area-visible@example.com', 'adminproviderareavisible');
        $client = $this->createUser('client', 'client-provider-area-visible@example.com', 'clientproviderareavisible');
        $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED, 'Lourdes');

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('outside service area');
        $response->assertSee('No approved marketplace provider covers Poblacion.');
    }

    public function test_admin_booking_dropdown_marks_paused_providers(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-paused-visible@example.com', 'adminproviderpausedvisible');
        $client = $this->createUser('client', 'client-provider-paused-visible@example.com', 'clientproviderpausedvisible');
        $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $provider->forceFill(['availability_status' => CleanerApplication::AVAILABILITY_PAUSED])->save();

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('Paused');
        $response->assertSee('Approved providers for Poblacion are currently paused or unavailable.');
    }

    public function test_admin_booking_dropdown_marks_providers_at_daily_limit(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-limit-visible@example.com', 'adminproviderlimitvisible');
        $client = $this->createUser('client', 'client-provider-limit-visible@example.com', 'clientproviderlimitvisible');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $provider->forceFill(['max_daily_bookings' => 1])->save();
        $scheduledDate = now()->addDay()->toDateString();
        $existingBooking = $this->createBooking($client, null, 'confirmed', $scheduledDate, '08:00');
        $existingBooking->forceFill([
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'accepted',
        ])->save();
        $this->createBooking($client, null, 'pending', $scheduledDate, '10:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('at daily limit');
        $response->assertSee('Approved providers for Poblacion have reached their daily booking limit.');
    }

    public function test_admin_bookings_page_shows_assigned_marketplace_provider(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-visible@example.com', 'adminprovidervisible');
        $client = $this->createUser('client', 'client-provider-visible@example.com', 'clientprovidervisible');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
        ], $booking->calculateMarketplaceCommission()))->save();

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('Marketplace Provider');
        $response->assertSee($provider->business_name);
        $response->assertSee('Gross');
        $response->assertSee('Commission');
        $response->assertSee('Payout');
        $response->assertSee('Pending payout');
    }

    public function test_admin_can_filter_declined_provider_assignments(): void
    {
        $admin = $this->createUser('admin', 'admin-provider-declined-filter@example.com', 'adminproviderdeclinedfilter');
        $client = $this->createUser('client', 'client-provider-declined-filter@example.com', 'clientproviderdeclinedfilter');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $declinedBooking = $this->createBooking($client, null, 'confirmed', now()->addDay()->toDateString(), '09:00');
        $normalBooking = $this->createBooking($client, null, 'confirmed', now()->addDays(2)->toDateString(), '10:00');

        $declinedBooking->forceFill([
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'declined',
            'provider_assignment_responded_at' => now(),
            'provider_assignment_notes' => 'Not available.',
        ])->save();

        $response = $this->actingAs($admin)->get(route('admin.bookings', [
            'tab' => 'active',
            'filter' => 'provider_declined',
        ]));

        $response->assertOk();
        $response->assertSee('Provider Declined');
        $response->assertSee('Provider declined');
        $response->assertSee('CF-'.str_pad($declinedBooking->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('CF-'.str_pad($normalBooking->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_admin_reassigning_provider_resets_declined_assignment_response(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin', 'admin-provider-reassign@example.com', 'adminproviderreassign');
        $client = $this->createUser('client', 'client-provider-reassign@example.com', 'clientproviderreassign');
        $oldProvider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $newProvider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'confirmed', now()->addDay()->toDateString(), '09:00');
        $booking->forceFill([
            'cleaner_application_id' => $oldProvider->id,
            'provider_assignment_status' => 'declined',
            'provider_assignment_responded_at' => now(),
            'provider_assignment_notes' => 'Team unavailable.',
        ])->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.provider', $booking->id), [
            'cleaner_application_id' => $newProvider->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'cleaner_application_id' => $newProvider->id,
            'provider_assignment_status' => 'pending',
            'provider_assignment_responded_at' => null,
            'provider_assignment_notes' => null,
        ]);

        Mail::assertSent(MarketplaceProviderAssigned::class, function (MarketplaceProviderAssigned $mail) use ($booking, $newProvider) {
            return $mail->hasTo($newProvider->email)
                && $mail->booking->id === $booking->id
                && $mail->provider->id === $newProvider->id;
        });
    }

    public function test_admin_can_mark_completed_provider_payout_ready(): void
    {
        $admin = $this->createUser('admin', 'admin-payout-ready@example.com', 'adminpayoutready');
        $client = $this->createUser('client', 'client-payout-ready@example.com', 'clientpayoutready');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'accepted',
            'payment_status' => 'paid',
        ], $booking->calculateMarketplaceCommission()))->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'ready',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Provider payout status updated.');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
        ]);
        $this->assertDatabaseHas('booking_payouts', [
            'booking_id' => $booking->id,
            'provider_payout_status' => 'ready',
        ]);
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $admin->id,
            'action' => 'provider_payout_updated',
        ]);
    }

    public function test_admin_can_mark_cash_provider_commission_paid(): void
    {
        $admin = $this->createUser('admin', 'admin-cash-commission@example.com', 'admincashcommission');
        $client = $this->createUser('client', 'client-cash-commission@example.com', 'clientcashcommission');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill([
            'cleaner_application_id' => $provider->id,
            'provider_assignment_status' => 'accepted',
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
        ])->save();
        $booking->forceFill($booking->fresh()->calculateMarketplaceCommission())->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.provider-commission', $booking->id), [
            'provider_commission_status' => 'paid',
            'provider_commission_reference' => 'CASH-COMMISSION-001',
            'provider_commission_paid_at' => '2026-06-10 10:30:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Provider commission collection updated.');

        $booking->refresh();

        $this->assertSame('paid', $booking->provider_commission_status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame('CASH-COMMISSION-001', $booking->provider_commission_reference);
        $this->assertSame($admin->id, $booking->provider_commission_collected_by);
        $this->assertSame('2026-06-10 10:30:00', $booking->provider_commission_paid_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $admin->id,
            'action' => 'provider_commission_updated',
        ]);
    }

    public function test_marketplace_amounts_recalculate_when_unsettled_online_booking_price_changes(): void
    {
        $client = $this->createUser('client', 'client-online-recalc@example.com', 'clientonlinerecalc');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'confirmed', now()->addDay()->toDateString(), '09:00');

        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
        ], $booking->calculateMarketplaceCommission()))->save();

        $booking->forceFill(['price' => 2000])->save();
        $booking->refresh();

        $this->assertEquals(2000, $booking->provider_gross_amount);
        $this->assertEquals(300, $booking->platform_commission_amount);
        $this->assertEquals(1700, $booking->provider_payout_amount);
        $this->assertSame('pending', $booking->provider_payout_status);
        $this->assertSame('not_applicable', $booking->provider_commission_status);
    }

    public function test_marketplace_amounts_recalculate_when_unsettled_booking_changes_to_cash(): void
    {
        $client = $this->createUser('client', 'client-cash-recalc@example.com', 'clientcashrecalc');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'confirmed', now()->addDay()->toDateString(), '09:00');

        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
        ], $booking->calculateMarketplaceCommission()))->save();

        $booking->forceFill([
            'price' => 2000,
            'payment_method' => 'on_site_cash',
        ])->save();
        $booking->refresh();

        $this->assertEquals(2000, $booking->provider_gross_amount);
        $this->assertEquals(300, $booking->platform_commission_amount);
        $this->assertEquals(1700, $booking->provider_payout_amount);
        $this->assertEquals(2000, $booking->cash_collected_amount);
        $this->assertEquals(300, $booking->provider_commission_due);
        $this->assertSame('cash_collected', $booking->provider_payout_status);
        $this->assertSame('unpaid', $booking->provider_commission_status);
    }

    public function test_paid_provider_payout_amounts_do_not_recalculate_when_booking_price_changes(): void
    {
        $client = $this->createUser('client', 'client-paid-payout-frozen@example.com', 'clientpaidpayoutfrozen');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');

        $booking->forceFill(array_merge($booking->calculateMarketplaceCommission(), [
            'cleaner_application_id' => $provider->id,
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'GCASH-PAID-FROZEN',
            'provider_payout_paid_at' => now(),
        ]))->save();

        $booking->forceFill(['price' => 2000])->save();
        $booking->refresh();

        $this->assertEquals(1200, $booking->provider_gross_amount);
        $this->assertEquals(180, $booking->platform_commission_amount);
        $this->assertEquals(1020, $booking->provider_payout_amount);
        $this->assertSame('paid', $booking->provider_payout_status);
        $this->assertSame('GCASH-PAID-FROZEN', $booking->provider_payout_reference);
    }

    public function test_paid_cash_commission_amounts_do_not_recalculate_when_booking_price_changes(): void
    {
        $admin = $this->createUser('admin', 'admin-paid-cash-frozen@example.com', 'adminpaidcashfrozen');
        $client = $this->createUser('client', 'client-paid-cash-frozen@example.com', 'clientpaidcashfrozen');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(['payment_method' => 'on_site_cash']);

        $booking->forceFill(array_merge($booking->calculateMarketplaceCommission(), [
            'cleaner_application_id' => $provider->id,
            'provider_commission_status' => 'paid',
            'provider_commission_reference' => 'CASH-PAID-FROZEN',
            'provider_commission_paid_at' => now(),
            'provider_commission_collected_by' => $admin->id,
        ]))->save();

        $booking->forceFill(['price' => 2000])->save();
        $booking->refresh();

        $this->assertEquals(1200, $booking->provider_gross_amount);
        $this->assertEquals(180, $booking->platform_commission_amount);
        $this->assertEquals(1020, $booking->provider_payout_amount);
        $this->assertEquals(1200, $booking->cash_collected_amount);
        $this->assertEquals(180, $booking->provider_commission_due);
        $this->assertSame('paid', $booking->provider_commission_status);
        $this->assertSame('CASH-PAID-FROZEN', $booking->provider_commission_reference);
    }

    public function test_admin_cannot_mark_payout_ready_before_booking_completed(): void
    {
        $admin = $this->createUser('admin', 'admin-payout-ready-block@example.com', 'adminpayoutreadyblock');
        $client = $this->createUser('client', 'client-payout-ready-block@example.com', 'clientpayoutreadyblock');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'confirmed', now()->addDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
        ], $booking->calculateMarketplaceCommission()))->save();

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'ready',
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('provider_payout_status');

        $this->assertSame('pending', $booking->fresh()->provider_payout_status);
    }

    public function test_admin_cannot_mark_payout_paid_before_customer_payment_paid(): void
    {
        $admin = $this->createUser('admin', 'admin-payout-paid-block@example.com', 'adminpayoutpaidblock');
        $client = $this->createUser('client', 'client-payout-paid-block@example.com', 'clientpayoutpaidblock');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'pending',
        ], $booking->calculateMarketplaceCommission()))->save();

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'GCASH-UNPAID-001',
            'provider_payout_paid_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('provider_payout_status');

        $this->assertSame('pending', $booking->fresh()->provider_payout_status);
    }

    public function test_admin_can_mark_paid_after_completed_and_customer_payment_paid(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin', 'admin-payout-paid@example.com', 'adminpayoutpaid');
        $client = $this->createUser('client', 'client-payout-paid@example.com', 'clientpayoutpaid');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge($booking->calculateMarketplaceCommission(), [
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'paid',
            'provider_payout_status' => 'ready',
        ]))->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'GCASH-PAID-001',
            'provider_payout_paid_at' => '2026-06-08 15:30:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Provider payout status updated.');

        $booking->refresh();

        $this->assertSame('paid', $booking->provider_payout_status);
        $this->assertSame('GCASH-PAID-001', $booking->provider_payout_reference);
        $this->assertSame($admin->id, $booking->provider_payout_processed_by);
        $this->assertSame('2026-06-08 15:30:00', $booking->provider_payout_paid_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('provider_payout_transactions', [
            'booking_id' => $booking->id,
            'cleaner_application_id' => $provider->id,
            'processed_by' => $admin->id,
            'from_status' => 'ready',
            'to_status' => 'paid',
            'payout_reference' => 'GCASH-PAID-001',
        ]);
        Mail::assertSent(ProviderPayoutPaid::class, function (ProviderPayoutPaid $mail) use ($booking, $provider) {
            return $mail->hasTo($provider->email)
                && $mail->booking->id === $booking->id
                && $mail->provider->id === $provider->id;
        });
    }

    public function test_admin_cannot_mark_provider_payout_paid_without_transaction_reference_and_date(): void
    {
        $admin = $this->createUser('admin', 'admin-payout-paid-evidence-block@example.com', 'adminpayoutpaidevidenceblock');
        $client = $this->createUser('client', 'client-payout-paid-evidence-block@example.com', 'clientpayoutpaidevidenceblock');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge($booking->calculateMarketplaceCommission(), [
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'paid',
            'provider_payout_status' => 'ready',
        ]))->save();

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'paid',
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors(['provider_payout_reference', 'provider_payout_paid_at']);

        $booking->refresh();

        $this->assertSame('ready', $booking->provider_payout_status);
        $this->assertNull($booking->provider_payout_reference);
        $this->assertNull($booking->provider_payout_paid_at);
    }

    public function test_admin_can_upload_provider_payout_proof_when_marking_paid(): void
    {
        Storage::fake('local');

        $admin = $this->createUser('admin', 'admin-payout-proof@example.com', 'adminpayoutproof');
        $client = $this->createUser('client', 'client-payout-proof@example.com', 'clientpayoutproof');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'paid',
            'provider_payout_status' => 'ready',
        ], $booking->calculateMarketplaceCommission()))->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'MAYA-PROOF-001',
            'provider_payout_paid_at' => '2026-06-08 16:45:00',
            'provider_payout_proof' => UploadedFile::fake()->create('payout-proof.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Provider payout status updated.');

        $booking->refresh();

        $this->assertSame('paid', $booking->provider_payout_status);
        $this->assertSame('MAYA-PROOF-001', $booking->provider_payout_reference);
        $this->assertSame('payout-proof.pdf', $booking->provider_payout_proof_original_filename);
        $this->assertNotNull($booking->provider_payout_proof_path);
        Storage::disk('local')->assertExists($booking->provider_payout_proof_path);
        $this->assertDatabaseHas('provider_payout_transactions', [
            'booking_id' => $booking->id,
            'from_status' => 'pending',
            'to_status' => 'paid',
            'payout_reference' => 'MAYA-PROOF-001',
            'payout_proof_original_filename' => 'payout-proof.pdf',
        ]);
    }

    public function test_admin_does_not_send_duplicate_provider_payout_paid_email_when_already_paid(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin', 'admin-payout-paid-duplicate@example.com', 'adminpayoutpaidduplicate');
        $client = $this->createUser('client', 'client-payout-paid-duplicate@example.com', 'clientpayoutpaidduplicate');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge($booking->calculateMarketplaceCommission(), [
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'paid',
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'GCASH-EXISTING-001',
            'provider_payout_paid_at' => '2026-06-08 15:00:00',
            'provider_payout_processed_by' => $admin->id,
        ]))->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'GCASH-UPDATED-001',
            'provider_payout_paid_at' => '2026-06-08 16:00:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Provider payout status updated.');

        Mail::assertNotSent(ProviderPayoutPaid::class);
        $this->assertSame('GCASH-UPDATED-001', $booking->fresh()->provider_payout_reference);
    }

    public function test_admin_can_download_provider_payout_proof(): void
    {
        Storage::fake('local');

        $admin = $this->createUser('admin', 'admin-payout-proof-download@example.com', 'adminpayoutproofdownload');
        $client = $this->createUser('client', 'client-payout-proof-download@example.com', 'clientpayoutproofdownload');
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $path = 'provider-payout-proofs/'.$booking->id.'/proof.pdf';

        Storage::disk('local')->put($path, 'proof file');

        $booking->forceFill([
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'BANK-DOWNLOAD-001',
            'provider_payout_paid_at' => now(),
            'provider_payout_proof_path' => $path,
            'provider_payout_proof_original_filename' => 'bank-proof.pdf',
        ])->save();

        $response = $this->actingAs($admin)->get(route('admin.bookings.payout-proof', $booking->id));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_reversing_paid_provider_payout_preserves_transaction_proof_snapshot(): void
    {
        Storage::fake('local');

        $admin = $this->createUser('admin', 'admin-payout-reversal@example.com', 'adminpayoutreversal');
        $client = $this->createUser('client', 'client-payout-reversal@example.com', 'clientpayoutreversal');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $this->verifyProviderPayoutSetup($provider, $admin);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge($booking->calculateMarketplaceCommission(), [
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'paid',
            'provider_payout_status' => 'ready',
        ]))->save();

        $this->actingAs($admin)->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'paid',
            'provider_payout_reference' => 'GCASH-REVERSAL-001',
            'provider_payout_paid_at' => '2026-06-08 17:00:00',
            'provider_payout_proof' => UploadedFile::fake()->create('reversal-proof.pdf', 200, 'application/pdf'),
        ])->assertRedirect();

        $booking->refresh();
        $proofPath = $booking->provider_payout_proof_path;

        $this->actingAs($admin)->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'held',
        ])->assertRedirect();

        $booking->refresh();

        $this->assertSame('held', $booking->provider_payout_status);
        $this->assertNull($booking->provider_payout_reference);
        $this->assertNull($booking->provider_payout_proof_path);
        Storage::disk('local')->assertExists($proofPath);

        $reversal = ProviderPayoutTransaction::where('booking_id', $booking->id)
            ->where('from_status', 'paid')
            ->where('to_status', 'held')
            ->firstOrFail();

        $this->assertSame('GCASH-REVERSAL-001', $reversal->payout_reference);
        $this->assertSame($proofPath, $reversal->payout_proof_path);
        $this->assertSame('Paid payout status was reversed by admin.', $reversal->notes);

        $response = $this->actingAs($admin)->get(route('admin.provider-payout-transactions.proof', $reversal));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_admin_cannot_mark_payout_ready_until_provider_payout_setup_is_verified(): void
    {
        $admin = $this->createUser('admin', 'admin-payout-verification-block@example.com', 'adminpayoutverificationblock');
        $client = $this->createUser('client', 'client-payout-verification-block@example.com', 'clientpayoutverificationblock');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'paid',
        ], $booking->calculateMarketplaceCommission()))->save();

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'ready',
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('provider_payout_status');
        $response->assertSessionHasErrors([
            'provider_payout_status' => 'Provider payout is on hold until payout setup is verified. Payout details missing; Documents not verified',
        ]);

        $this->assertSame('pending', $booking->fresh()->provider_payout_status);
    }

    public function test_admin_can_mark_unverified_provider_payout_held(): void
    {
        $admin = $this->createUser('admin', 'admin-payout-held@example.com', 'adminpayoutheld');
        $client = $this->createUser('client', 'client-payout-held@example.com', 'clientpayoutheld');
        $provider = $this->createCleanerApplication(CleanerApplication::STATUS_APPROVED);
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');
        $booking->forceFill(array_merge([
            'cleaner_application_id' => $provider->id,
            'payment_status' => 'paid',
        ], $booking->calculateMarketplaceCommission()))->save();

        $response = $this->actingAs($admin)->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'held',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Provider payout status updated.');

        $this->assertSame('held', $booking->fresh()->provider_payout_status);
    }

    public function test_admin_cannot_manage_payout_without_provider_commission_snapshot(): void
    {
        $admin = $this->createUser('admin', 'admin-payout-missing@example.com', 'adminpayoutmissing');
        $client = $this->createUser('client', 'client-payout-missing@example.com', 'clientpayoutmissing');
        $booking = $this->createBooking($client, null, 'completed', now()->subDay()->toDateString(), '09:00');

        $response = $this->actingAs($admin)->from(route('admin.bookings'))->patch(route('admin.bookings.payout', $booking->id), [
            'provider_payout_status' => 'ready',
        ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('provider_payout_status');
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

    private function createBooking(User $client, ?User $staff, string $status, string $scheduledDate, string $scheduledTime): Booking
    {
        $booking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'price' => 1200,
            'status' => $status,
            'staff_id' => $staff?->id,
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
        ]);

        if (in_array($status, ['completed', 'cancelled'], true)) {
            $booking->forceFill(['updated_at' => now()->subHour()])->save();
        }

        return $booking->fresh();
    }

    private function createCleanerApplication(string $status, string $serviceArea = 'Valencia City'): CleanerApplication
    {
        static $applicationSequence = 0;

        $applicationSequence++;

        return CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Bright Team Cleaners '.$status.' '.$applicationSequence,
            'contact_person' => 'Maria Santos',
            'email' => 'bright-'.$status.'-'.$applicationSequence.'@example.com',
            'phone' => '09171234567',
            'service_area' => $serviceArea,
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => $status,
        ]);
    }

    private function verifyProviderPayoutSetup(CleanerApplication $provider, User $admin): void
    {
        foreach ($provider->requiredPayoutDocumentTypes() as $documentType) {
            $provider->documents()->create([
                'document_type' => $documentType,
                'original_filename' => $documentType.'.pdf',
                'file_path' => 'provider-documents/'.$provider->id.'/'.$documentType.'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'uploaded_by' => $admin->id,
            ]);
        }

        $provider->forceFill([
            'payout_method' => CleanerApplication::PAYOUT_METHOD_GCASH,
            'payout_account_name' => $provider->business_name,
            'payout_account_number' => '09171234567',
            'valid_id_submitted' => true,
            'business_permit_submitted' => $provider->isTeam(),
            'payout_account_proof_submitted' => true,
            'payout_verification_status' => CleanerApplication::PAYOUT_VERIFICATION_VERIFIED,
            'payout_verified_at' => now(),
            'payout_verified_by' => $admin->id,
        ])->save();
    }
}
