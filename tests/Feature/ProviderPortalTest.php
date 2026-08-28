<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\CleanerApplicationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_dashboard_shows_only_own_recent_assigned_bookings(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-one@example.com', 'providerone', 'Provider One Cleaners');
        [, $otherApplication] = $this->createProvider('provider-two@example.com', 'providertwo', 'Provider Two Cleaners');
        $client = $this->createUser('client', 'client-provider-dashboard@example.com', 'clientproviderdash');

        $ownBooking = $this->createBooking($client, $application, 'pending', 'Own Provider Service');
        $otherBooking = $this->createBooking($client, $otherApplication, 'pending', 'Other Provider Service');

        $response = $this->actingAs($providerUser)->get(route('provider.dashboard'));

        $response->assertOk();
        $response->assertSee($application->business_name);
        $response->assertSee('Assigned Bookings');
        $response->assertSee('CF-'.str_pad($ownBooking->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('Own Provider Service');
        $response->assertDontSee('CF-'.str_pad($otherBooking->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('Other Provider Service');
    }

    public function test_provider_dashboard_shows_own_earnings_summary(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-earnings@example.com', 'providerearnings', 'Provider Earnings Cleaners');
        [, $otherApplication] = $this->createProvider('provider-earnings-other@example.com', 'providerearningsother', 'Other Earnings Cleaners');
        $client = $this->createUser('client', 'client-provider-earnings@example.com', 'clientproviderearnings');
        $ownBooking = $this->createBooking($client, $application, 'completed', 'Provider Earnings Service');
        $ownBooking->forceFill(array_merge($ownBooking->calculateMarketplaceCommission(), [
            'provider_payout_status' => 'ready',
        ]))->save();
        $otherBooking = $this->createBooking($client, $otherApplication, 'completed', 'Other Earnings Service');
        $otherBooking->forceFill($otherBooking->calculateMarketplaceCommission())->save();

        $response = $this->actingAs($providerUser)->get(route('provider.dashboard'));

        $response->assertOk();
        $response->assertSee('Earnings');
        $response->assertSee('&#8369;1,200.00', false);
        $response->assertSee('&#8369;180.00', false);
        $response->assertSee('&#8369;1,020.00', false);
        $response->assertDontSee('Other Earnings Service');
    }

    public function test_provider_bookings_page_lists_only_own_assigned_bookings(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-list@example.com', 'providerlist', 'Provider List Cleaners');
        [, $otherApplication] = $this->createProvider('provider-list-other@example.com', 'providerlistother', 'Other List Cleaners');
        $client = $this->createUser('client', 'client-provider-list@example.com', 'clientproviderlist');

        $ownBooking = $this->createBooking($client, $application, 'confirmed', 'Provider List Service');
        $otherBooking = $this->createBooking($client, $otherApplication, 'confirmed', 'Hidden List Service');

        $response = $this->actingAs($providerUser)->get(route('provider.bookings'));

        $response->assertOk();
        $response->assertSee('Assigned Bookings');
        $response->assertSee('CF-'.str_pad($ownBooking->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('Provider List Service');
        $response->assertDontSee('CF-'.str_pad($otherBooking->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('Hidden List Service');
    }

    public function test_provider_can_view_own_booking_detail(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-detail@example.com', 'providerdetail', 'Provider Detail Cleaners');
        $client = $this->createUser('client', 'client-provider-detail@example.com', 'clientproviderdetail');
        $booking = $this->createBooking($client, $application, 'confirmed', 'Provider Detail Service');

        $response = $this->actingAs($providerUser)->get(route('provider.bookings.show', $booking));

        $response->assertOk();
        $response->assertSee('CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('Provider Detail Service');
        $response->assertSee($application->business_name);
    }

    public function test_provider_cannot_view_another_provider_booking_detail(): void
    {
        [$providerUser] = $this->createProvider('provider-blocked@example.com', 'providerblocked', 'Provider Blocked Cleaners');
        [, $otherApplication] = $this->createProvider('provider-owner@example.com', 'providerowner', 'Provider Owner Cleaners');
        $client = $this->createUser('client', 'client-provider-blocked@example.com', 'clientproviderblocked');
        $booking = $this->createBooking($client, $otherApplication, 'confirmed', 'Blocked Provider Service');

        $response = $this->actingAs($providerUser)->get(route('provider.bookings.show', $booking));

        $response->assertForbidden();
    }

    public function test_client_cannot_access_provider_bookings_page(): void
    {
        $client = $this->createUser('client', 'client-provider-forbidden@example.com', 'clientproviderforbid');

        $response = $this->actingAs($client)->get(route('provider.bookings'));

        $response->assertForbidden();
    }

    public function test_provider_portal_requires_approved_and_activated_application(): void
    {
        $providerUser = $this->createUser('provider', 'provider-not-active@example.com', 'providernotactive');

        CleanerApplication::create([
            'user_id' => $providerUser->id,
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Not Active Provider',
            'contact_person' => $providerUser->display_name,
            'email' => $providerUser->email,
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
            'activated_at' => null,
        ]);

        $response = $this->actingAs($providerUser)->get(route('provider.dashboard'));

        $response->assertForbidden();
    }

    public function test_provider_payouts_page_lists_only_own_payout_records(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-payouts@example.com', 'providerpayouts', 'Provider Payouts Cleaners');
        [, $otherApplication] = $this->createProvider('provider-payouts-other@example.com', 'providerpayoutsother', 'Other Payouts Cleaners');
        $client = $this->createUser('client', 'client-provider-payouts@example.com', 'clientproviderpayouts');
        $ownBooking = $this->createBooking($client, $application, 'completed', 'Provider Payout Service');
        $ownBooking->forceFill(array_merge($ownBooking->calculateMarketplaceCommission(), [
            'provider_payout_status' => 'paid',
        ]))->save();
        $otherBooking = $this->createBooking($client, $otherApplication, 'completed', 'Hidden Payout Service');
        $otherBooking->forceFill($otherBooking->calculateMarketplaceCommission())->save();

        $response = $this->actingAs($providerUser)->get(route('provider.payouts'));

        $response->assertOk();
        $response->assertSee('Payout History');
        $response->assertSee('CF-'.str_pad($ownBooking->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('Provider Payout Service');
        $response->assertSee('Paid');
        $response->assertDontSee('CF-'.str_pad($otherBooking->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('Hidden Payout Service');
    }

    public function test_client_cannot_access_provider_payouts_page(): void
    {
        $client = $this->createUser('client', 'client-provider-payouts-forbidden@example.com', 'clientproviderpayoutsforbid');

        $response = $this->actingAs($client)->get(route('provider.payouts'));

        $response->assertForbidden();
    }

    public function test_provider_can_update_availability(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-availability@example.com', 'provideravailability', 'Provider Availability Cleaners');

        $response = $this->actingAs($providerUser)->patch(route('provider.availability.update'), [
            'availability_status' => 'paused',
            'availability_notes' => 'Fully booked this weekend.',
            'max_daily_bookings' => 3,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Availability updated.');

        $this->assertDatabaseHas('cleaner_applications', [
            'id' => $application->id,
            'availability_status' => 'paused',
            'availability_notes' => 'Fully booked this weekend.',
            'max_daily_bookings' => 3,
        ]);
    }

    public function test_provider_cannot_set_admin_only_unavailable_status(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-unavailable-block@example.com', 'providerunavailableblock', 'Provider Unavailable Block Cleaners');

        $response = $this->actingAs($providerUser)->from(route('provider.dashboard'))->patch(route('provider.availability.update'), [
            'availability_status' => 'unavailable',
        ]);

        $response->assertRedirect(route('provider.dashboard'));
        $response->assertSessionHasErrors('availability_status');

        $this->assertSame('available', $application->fresh()->availability_status);
    }

    public function test_provider_can_submit_payout_setup(): void
    {
        Storage::fake('local');

        [$providerUser, $application] = $this->createProvider('provider-payout-setup@example.com', 'providerpayoutsetup', 'Provider Payout Setup Cleaners');

        $response = $this->actingAs($providerUser)->patch(route('provider.payout-setup.update'), [
            'payout_method' => CleanerApplication::PAYOUT_METHOD_GCASH,
            'payout_account_name' => 'Provider Payout Setup Cleaners',
            'payout_account_number' => '09171234567',
            'valid_id_front_document' => UploadedFile::fake()->create('valid-id-front.pdf', 200, 'application/pdf'),
            'valid_id_back_document' => UploadedFile::fake()->create('valid-id-back.pdf', 200, 'application/pdf'),
            'business_permit_document' => UploadedFile::fake()->create('business-permit.pdf', 250, 'application/pdf'),
            'payout_account_proof_document' => UploadedFile::fake()->create('payout-proof.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Payout setup submitted for admin verification.');

        $this->assertDatabaseHas('cleaner_applications', [
            'id' => $application->id,
            'payout_method' => CleanerApplication::PAYOUT_METHOD_GCASH,
            'payout_account_name' => 'Provider Payout Setup Cleaners',
            'payout_account_number' => '09171234567',
            'valid_id_submitted' => true,
            'business_permit_submitted' => true,
            'payout_account_proof_submitted' => true,
            'payout_verification_status' => CleanerApplication::PAYOUT_VERIFICATION_PENDING,
        ]);

        $this->assertDatabaseHas('cleaner_application_documents', [
            'cleaner_application_id' => $application->id,
            'document_type' => CleanerApplicationDocument::TYPE_VALID_ID_FRONT,
            'original_filename' => 'valid-id-front.pdf',
        ]);
        $this->assertDatabaseHas('cleaner_application_documents', [
            'cleaner_application_id' => $application->id,
            'document_type' => CleanerApplicationDocument::TYPE_VALID_ID_BACK,
            'original_filename' => 'valid-id-back.pdf',
        ]);
        $this->assertDatabaseHas('cleaner_application_documents', [
            'cleaner_application_id' => $application->id,
            'document_type' => CleanerApplicationDocument::TYPE_BUSINESS_PERMIT,
            'original_filename' => 'business-permit.pdf',
        ]);
        $this->assertDatabaseHas('cleaner_application_documents', [
            'cleaner_application_id' => $application->id,
            'document_type' => CleanerApplicationDocument::TYPE_PAYOUT_ACCOUNT_PROOF,
            'original_filename' => 'payout-proof.pdf',
        ]);
    }

    public function test_provider_must_upload_valid_id_front_and_back_for_payout_setup(): void
    {
        Storage::fake('local');

        [$providerUser, $application] = $this->createProvider('provider-id-both-sides@example.com', 'provideridbothsides', 'Provider ID Both Sides Cleaners');

        $response = $this->actingAs($providerUser)->from(route('provider.dashboard'))->patch(route('provider.payout-setup.update'), [
            'payout_method' => CleanerApplication::PAYOUT_METHOD_GCASH,
            'payout_account_name' => 'Provider ID Both Sides Cleaners',
            'payout_account_number' => '09171234567',
            'valid_id_front_document' => UploadedFile::fake()->create('valid-id-front.pdf', 200, 'application/pdf'),
            'business_permit_document' => UploadedFile::fake()->create('business-permit.pdf', 250, 'application/pdf'),
            'payout_account_proof_document' => UploadedFile::fake()->create('payout-proof.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect(route('provider.dashboard'));
        $response->assertSessionHasErrors('payout_documents');
        $response->assertSessionHasErrors([
            'payout_documents' => 'Upload required payout document(s): Valid ID back.',
        ]);

        $this->assertFalse((bool) $application->fresh()->valid_id_submitted);
        $this->assertSame(CleanerApplication::PAYOUT_VERIFICATION_PENDING, $application->fresh()->payout_verification_status);
    }

    public function test_provider_payout_setup_changes_reset_admin_verification(): void
    {
        Storage::fake('local');

        [$providerUser, $application] = $this->createProvider('provider-payout-reset@example.com', 'providerpayoutreset', 'Provider Payout Reset Cleaners');
        $admin = $this->createUser('admin', 'admin-provider-payout-reset@example.com', 'adminproviderpayoutreset');
        foreach ($application->requiredPayoutDocumentTypes() as $documentType) {
            $application->documents()->create([
                'document_type' => $documentType,
                'original_filename' => $documentType.'.pdf',
                'file_path' => 'provider-documents/'.$application->id.'/'.$documentType.'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'uploaded_by' => $providerUser->id,
            ]);
        }
        $application->forceFill([
            'payout_method' => CleanerApplication::PAYOUT_METHOD_MAYA,
            'payout_account_name' => 'Old Account',
            'payout_account_number' => '09170000000',
            'valid_id_submitted' => true,
            'business_permit_submitted' => true,
            'payout_account_proof_submitted' => true,
            'payout_verification_status' => CleanerApplication::PAYOUT_VERIFICATION_VERIFIED,
            'payout_verified_at' => now(),
            'payout_verified_by' => $admin->id,
        ])->save();

        $response = $this->actingAs($providerUser)->patch(route('provider.payout-setup.update'), [
            'payout_method' => CleanerApplication::PAYOUT_METHOD_GCASH,
            'payout_account_name' => 'New Account',
            'payout_account_number' => '09179999999',
        ]);

        $response->assertRedirect();

        $application->refresh();

        $this->assertSame(CleanerApplication::PAYOUT_VERIFICATION_PENDING, $application->payout_verification_status);
        $this->assertNull($application->payout_verified_at);
        $this->assertNull($application->payout_verified_by);
        $this->assertSame('New Account', $application->payout_account_name);
    }

    public function test_provider_can_accept_own_pending_assignment(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-accept@example.com', 'provideraccept', 'Provider Accept Cleaners');
        $client = $this->createUser('client', 'client-provider-accept@example.com', 'clientprovideraccept');
        $booking = $this->createBooking($client, $application, 'confirmed', 'Provider Accept Service');

        $response = $this->actingAs($providerUser)->patch(route('provider.bookings.response', $booking), [
            'response' => 'accepted',
            'provider_assignment_notes' => 'We can handle this booking.',
        ]);

        $response->assertRedirect(route('provider.bookings.show', $booking));
        $response->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame('accepted', $booking->provider_assignment_status);
        $this->assertNotNull($booking->provider_assignment_responded_at);
        $this->assertSame('We can handle this booking.', $booking->provider_assignment_notes);
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $providerUser->id,
            'action' => 'marketplace_provider_response',
        ]);
    }

    public function test_provider_can_decline_own_pending_assignment(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-decline@example.com', 'providerdecline', 'Provider Decline Cleaners');
        $client = $this->createUser('client', 'client-provider-decline@example.com', 'clientproviderdecline');
        $booking = $this->createBooking($client, $application, 'confirmed', 'Provider Decline Service');

        $response = $this->actingAs($providerUser)->patch(route('provider.bookings.response', $booking), [
            'response' => 'declined',
            'provider_assignment_notes' => 'Team is not available.',
        ]);

        $response->assertRedirect(route('provider.bookings.show', $booking));

        $booking->refresh();

        $this->assertSame('declined', $booking->provider_assignment_status);
        $this->assertNotNull($booking->provider_assignment_responded_at);
        $this->assertSame('Team is not available.', $booking->provider_assignment_notes);
    }

    public function test_provider_can_start_accepted_confirmed_booking_with_before_proof(): void
    {
        Storage::fake('public');

        [$providerUser, $application] = $this->createProvider('provider-start@example.com', 'providerstart', 'Provider Start Cleaners');
        $client = $this->createUser('client', 'client-provider-start@example.com', 'clientproviderstart');
        $booking = $this->createBooking($client, $application, 'confirmed', 'Provider Start Service');
        $booking->forceFill(['provider_assignment_status' => 'accepted'])->save();

        $response = $this->actingAs($providerUser)->patch(route('provider.bookings.status', $booking), [
            'status' => 'in_progress',
            'before_photos' => [
                $this->fakePngUpload('before-service.png'),
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame('in_progress', $booking->status);
        $this->assertNotNull($booking->started_at);
        $this->assertDatabaseHas('booking_service_proofs', [
            'booking_id' => $booking->id,
            'stage' => 'before',
            'media_type' => 'image',
            'uploaded_by' => $providerUser->id,
        ]);
    }

    public function test_provider_can_complete_in_progress_booking_with_after_proof(): void
    {
        Storage::fake('public');

        [$providerUser, $application] = $this->createProvider('provider-complete@example.com', 'providercomplete', 'Provider Complete Cleaners');
        $client = $this->createUser('client', 'client-provider-complete@example.com', 'clientprovidercomplete');
        $booking = $this->createBooking($client, $application, 'in_progress', 'Provider Complete Service');
        $booking->forceFill([
            'provider_assignment_status' => 'accepted',
            'started_at' => now(),
        ])->save();
        $booking->serviceProofs()->create([
            'uploaded_by' => $providerUser->id,
            'stage' => 'before',
            'media_type' => 'image',
            'file_path' => 'booking-proofs/before/existing-before.jpg',
            'original_name' => 'existing-before.jpg',
        ]);

        $response = $this->actingAs($providerUser)->patch(route('provider.bookings.status', $booking), [
            'status' => 'completed',
            'after_photos' => [
                $this->fakePngUpload('after-service.png'),
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame('completed', $booking->status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame('unpaid', $booking->provider_commission_status);
        $this->assertNotNull($booking->completed_at);
        $this->assertDatabaseHas('booking_service_proofs', [
            'booking_id' => $booking->id,
            'stage' => 'after',
            'media_type' => 'image',
            'uploaded_by' => $providerUser->id,
        ]);
    }

    public function test_provider_cannot_update_status_for_another_provider_booking(): void
    {
        Storage::fake('public');

        [$providerUser] = $this->createProvider('provider-status-blocked@example.com', 'providerstatusblocked', 'Provider Status Blocked Cleaners');
        [, $otherApplication] = $this->createProvider('provider-status-owner@example.com', 'providerstatusowner', 'Provider Status Owner Cleaners');
        $client = $this->createUser('client', 'client-provider-status-blocked@example.com', 'clientproviderstatusblocked');
        $booking = $this->createBooking($client, $otherApplication, 'confirmed', 'Blocked Status Service');
        $booking->forceFill(['provider_assignment_status' => 'accepted'])->save();

        $response = $this->actingAs($providerUser)->patch(route('provider.bookings.status', $booking), [
            'status' => 'in_progress',
            'before_photos' => [
                $this->fakePngUpload('before-service.png'),
            ],
        ]);

        $response->assertForbidden();

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_provider_cannot_respond_to_another_provider_assignment(): void
    {
        [$providerUser] = $this->createProvider('provider-response-blocked@example.com', 'providerresponseblocked', 'Provider Response Blocked Cleaners');
        [, $otherApplication] = $this->createProvider('provider-response-owner@example.com', 'providerresponseowner', 'Provider Response Owner Cleaners');
        $client = $this->createUser('client', 'client-provider-response-blocked@example.com', 'clientproviderresponseblocked');
        $booking = $this->createBooking($client, $otherApplication, 'confirmed', 'Blocked Response Service');

        $response = $this->actingAs($providerUser)->patch(route('provider.bookings.response', $booking), [
            'response' => 'accepted',
        ]);

        $response->assertForbidden();
    }

    public function test_provider_cannot_change_assignment_response_after_answering(): void
    {
        [$providerUser, $application] = $this->createProvider('provider-repeat@example.com', 'providerrepeat', 'Provider Repeat Cleaners');
        $client = $this->createUser('client', 'client-provider-repeat@example.com', 'clientproviderrepeat');
        $booking = $this->createBooking($client, $application, 'confirmed', 'Provider Repeat Service');
        $booking->forceFill([
            'provider_assignment_status' => 'accepted',
            'provider_assignment_responded_at' => now(),
        ])->save();

        $response = $this->actingAs($providerUser)->from(route('provider.bookings.show', $booking))->patch(route('provider.bookings.response', $booking), [
            'response' => 'declined',
        ]);

        $response->assertRedirect(route('provider.bookings.show', $booking));
        $response->assertSessionHasErrors('response');

        $this->assertSame('accepted', $booking->fresh()->provider_assignment_status);
    }

    /**
     * @return array{0: User, 1: CleanerApplication}
     */
    private function createProvider(string $email, string $username, string $businessName): array
    {
        $user = $this->createUser('provider', $email, $username);

        $application = CleanerApplication::create([
            'user_id' => $user->id,
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => $businessName,
            'contact_person' => $user->display_name,
            'email' => $email,
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
            'activated_at' => now(),
        ]);

        return [$user->fresh('cleanerApplication'), $application->fresh()];
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

    private function createBooking(User $client, CleanerApplication $application, string $status, string $serviceType): Booking
    {
        return Booking::create([
            'user_id' => $client->id,
            'cleaner_application_id' => $application->id,
            'service_type' => $serviceType,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'duration_minutes' => 60,
            'price' => 1200,
            'status' => $status,
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
        ]);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }
}
