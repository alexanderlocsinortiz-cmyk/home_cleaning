<?php

namespace Tests\Feature;

use App\Mail\CleanerApplicationDecision;
use App\Models\CleanerApplication;
use App\Models\CleanerApplicationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanerApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaner_team_can_submit_application(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'team_business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'bright@example.com',
            'business_address' => 'Poblacion, Valencia City, Bukidnon',
            'team_size' => 5,
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cleaner_applications', [
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Bright Team Cleaners',
            'email' => 'bright@example.com',
            'team_size' => 5,
            'services_offered' => 'Basic Cleaning, Deep Cleaning',
            'government_id_type' => CleanerApplication::GOVERNMENT_ID_NATIONAL_ID,
            'government_id_number' => '12345',
            'status' => CleanerApplication::STATUS_PENDING,
        ]);

        $application = CleanerApplication::where('email', 'bright@example.com')->firstOrFail();
        Storage::disk('local')->assertExists($application->government_id_document_path);
        Storage::disk('local')->assertExists($application->selfie_with_id_path);
    }

    public function test_cleaner_application_files_use_the_configured_private_disk(): void
    {
        Config::set('filesystems.disks.private-test', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/private-uploads'),
        ]);
        Config::set('filesystems.private_uploads_disk', 'private-test');
        Storage::fake('private-test');

        $this->from(route('cleaner-applications.create'))
            ->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
                'email' => 'configured-private-disk@example.com',
            ]))
            ->assertRedirect(route('cleaner-applications.create'));

        $application = CleanerApplication::where('email', 'configured-private-disk@example.com')->firstOrFail();

        Storage::disk('private-test')->assertExists($application->government_id_document_path);
        Storage::disk('private-test')->assertExists($application->selfie_with_id_path);
    }

    public function test_team_application_requires_team_size(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'team_business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'bright@example.com',
            'team_size' => null,
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('team_size');
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => 'bright@example.com',
        ]);
    }

    public function test_individual_application_clears_team_size(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'individual_name' => 'Juan Dela Cruz',
            'email' => 'juan-cleaner@example.com',
            'profile_photo' => null,
            'team_size' => 4,
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));

        $this->assertDatabaseHas('cleaner_applications', [
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Juan Dela Cruz',
            'contact_person' => 'Juan Dela Cruz',
            'email' => 'juan-cleaner@example.com',
            'team_size' => null,
        ]);
    }

    public function test_cleaner_application_can_submit_specific_bukidnon_coverage_area(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'team_business_name' => 'Bukidnon Area Team Cleaners',
            'contact_person' => 'Ana Reyes',
            'email' => 'bukidnon-area-team@example.com',
            'coverage_mode' => 'specific',
            'coverage_barangays' => ['Malaybalay City'],
            'team_size' => 6,
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHas('success');

        $application = CleanerApplication::where('email', 'bukidnon-area-team@example.com')->firstOrFail();

        $this->assertSame(['Malaybalay City'], $application->coverage_barangays);
        $this->assertSame('Malaybalay City', $application->service_area);
        $this->assertFalse($application->coversBarangay('Bagontaas'));
    }

    public function test_valencia_city_coverage_matches_valencia_barangay_bookings(): void
    {
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Valencia Area Team Cleaners',
            'contact_person' => 'Ana Reyes',
            'email' => 'valencia-area-team@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'coverage_barangays' => ['Valencia City'],
            'years_experience' => 4,
            'team_size' => 6,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);

        $this->assertTrue($application->coversBarangay('Poblacion'));
        $this->assertTrue($application->coversBarangay('Bagontaas'));
        $this->assertFalse($application->coversBarangay('Malaybalay City'));
    }

    public function test_specific_coverage_requires_at_least_one_bukidnon_area(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'individual_name' => 'Coverage Missing Cleaner',
            'email' => 'coverage-missing@example.com',
            'coverage_mode' => 'specific',
            'coverage_barangays' => null,
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('coverage_barangays');
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => 'coverage-missing@example.com',
        ]);
    }

    public function test_government_id_number_must_be_numeric(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'non-numeric-id@example.com',
            'government_id_number' => 'ADADADAAD',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('government_id_number');
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => 'non-numeric-id@example.com',
        ]);
    }

    public function test_mobile_number_must_be_numeric(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'non-numeric-phone@example.com',
            'phone' => 'FAFAFAFA',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => 'non-numeric-phone@example.com',
        ]);
    }

    public function test_admin_can_approve_pending_cleaner_application(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'bright@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.cleaner-applications.update', $application), [
            'status' => CleanerApplication::STATUS_APPROVED,
            'admin_notes' => 'Verified by phone.',
        ]);

        $response->assertRedirect(route('admin.cleaner-applications.index'));
        $response->assertSessionHas('success', 'Cleaner application marked as approved. Applicant email notification queued.');

        $this->assertDatabaseHas('cleaner_applications', [
            'id' => $application->id,
            'status' => CleanerApplication::STATUS_APPROVED,
            'admin_notes' => 'Verified by phone.',
            'reviewed_by' => $admin->id,
        ]);

        $this->assertNotNull($application->fresh()->reviewed_at);

        Mail::assertSent(CleanerApplicationDecision::class, function (CleanerApplicationDecision $mail) use ($application) {
            return $mail->hasTo($application->email)
                && $mail->application->status === CleanerApplication::STATUS_APPROVED
                && filled($mail->activationToken);
        });

        $this->assertNotNull($application->fresh()->activation_token_hash);
        $this->assertNotNull($application->fresh()->activation_token_expires_at);
    }

    public function test_admin_rejection_sends_applicant_email_notification(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Juan Dela Cruz',
            'contact_person' => 'Juan Dela Cruz',
            'email' => 'juan-rejected@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Residential cleaning',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.cleaner-applications.update', $application), [
            'status' => CleanerApplication::STATUS_REJECTED,
            'admin_notes' => 'Verification details were incomplete.',
        ]);

        $response->assertRedirect(route('admin.cleaner-applications.index'));

        $this->assertDatabaseHas('cleaner_applications', [
            'id' => $application->id,
            'status' => CleanerApplication::STATUS_REJECTED,
            'admin_notes' => 'Verification details were incomplete.',
            'reviewed_by' => $admin->id,
        ]);

        Mail::assertSent(CleanerApplicationDecision::class, function (CleanerApplicationDecision $mail) use ($application) {
            return $mail->hasTo($application->email)
                && $mail->application->status === CleanerApplication::STATUS_REJECTED
                && $mail->activationToken === null;
        });
    }

    public function test_approved_cleaner_can_activate_provider_account(): void
    {
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'provider-activate@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);
        $token = $application->issueActivationToken();

        $response = $this->post(route('provider.activate.store', ['token' => $token]), [
            'username' => 'provideractivate',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('provider.dashboard'));
        $response->assertSessionHas('success', 'Provider account activated successfully.');

        $user = User::where('email', 'provider-activate@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('provider', $user->role);
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('cleaner_applications', [
            'id' => $application->id,
            'user_id' => $user->id,
            'activation_token_hash' => null,
        ]);
        $this->assertNotNull($application->fresh()->activated_at);
    }

    public function test_expired_activation_link_cannot_create_provider_account(): void
    {
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Juan Dela Cruz',
            'contact_person' => 'Juan Dela Cruz',
            'email' => 'expired-provider@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);
        $token = $application->issueActivationToken();
        $application->forceFill(['activation_token_expires_at' => now()->subMinute()])->save();

        $response = $this->post(route('provider.activate.store', ['token' => $token]), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('provider.activate.invalid'));
        $this->assertDatabaseMissing('users', ['email' => 'expired-provider@example.com']);
    }

    public function test_client_cannot_access_admin_cleaner_applications(): void
    {
        $client = $this->createUser('client');

        $response = $this->actingAs($client)->get(route('admin.cleaner-applications.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_verify_complete_provider_payout_setup(): void
    {
        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Verified Payout Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'verified-payout@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
            'payout_method' => CleanerApplication::PAYOUT_METHOD_GCASH,
            'payout_account_name' => 'Verified Payout Cleaners',
            'payout_account_number' => '09171234567',
            'valid_id_submitted' => true,
            'business_permit_submitted' => true,
            'payout_account_proof_submitted' => true,
        ]);
        $this->attachRequiredPayoutDocuments($application, $admin);

        $response = $this->actingAs($admin)->patch(route('admin.cleaner-applications.payout-verification', $application), [
            'payout_verification_status' => CleanerApplication::PAYOUT_VERIFICATION_VERIFIED,
            'admin_notes' => 'Payout details checked.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Provider payout verification updated.');

        $application->refresh();

        $this->assertSame(CleanerApplication::PAYOUT_VERIFICATION_VERIFIED, $application->payout_verification_status);
        $this->assertSame('Payout details checked.', $application->admin_notes);
        $this->assertSame($admin->id, $application->payout_verified_by);
        $this->assertNotNull($application->payout_verified_at);
    }

    public function test_admin_cannot_verify_incomplete_provider_payout_setup(): void
    {
        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Incomplete Payout Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'incomplete-payout@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.cleaner-applications.index', ['status' => 'approved']))
            ->patch(route('admin.cleaner-applications.payout-verification', $application), [
                'payout_verification_status' => CleanerApplication::PAYOUT_VERIFICATION_VERIFIED,
            ]);

        $response->assertRedirect(route('admin.cleaner-applications.index', ['status' => 'approved']));
        $response->assertSessionHasErrors('payout_verification_status');

        $this->assertSame(CleanerApplication::PAYOUT_VERIFICATION_PENDING, $application->fresh()->payout_verification_status);
    }

    public function test_admin_can_download_provider_payout_document(): void
    {
        Storage::fake('local');

        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Download Document Cleaner',
            'contact_person' => 'Juan Dela Cruz',
            'email' => 'download-document@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Residential cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);
        Storage::disk('local')->put('provider-documents/'.$application->id.'/valid-id-front.pdf', 'sample document');
        $document = $application->documents()->create([
            'document_type' => CleanerApplicationDocument::TYPE_VALID_ID_FRONT,
            'original_filename' => 'valid-id-front.pdf',
            'file_path' => 'provider-documents/'.$application->id.'/valid-id-front.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 15,
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cleaner-applications.documents.download', [$application, $document]));

        $response->assertOk();
        $this->assertStringContainsString('attachment; filename=valid-id-front.pdf', $response->headers->get('content-disposition'));
    }

    public function test_admin_can_download_application_verification_file(): void
    {
        Storage::fake('local');

        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Download Application Cleaner',
            'contact_person' => 'Download Application Cleaner',
            'email' => 'download-application-file@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'government_id_document_path' => 'cleaner-applications/1/government-id.pdf',
            'government_id_document_original_filename' => 'government-id.pdf',
        ]);
        Storage::disk('local')->put('cleaner-applications/1/government-id.pdf', 'sample id');

        $response = $this->actingAs($admin)->get(route('admin.cleaner-applications.application-files.download', [$application, 'government-id']));

        $response->assertOk();
        $this->assertStringContainsString('attachment; filename=government-id.pdf', $response->headers->get('content-disposition'));
    }

    public function test_guest_cannot_download_application_verification_file(): void
    {
        Storage::fake('local');

        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Private Application Cleaner',
            'contact_person' => 'Private Application Cleaner',
            'email' => 'private-application-file@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'government_id_document_path' => 'cleaner-applications/2/government-id.pdf',
            'government_id_document_original_filename' => 'government-id.pdf',
        ]);
        Storage::disk('local')->put('cleaner-applications/2/government-id.pdf', 'private sample id');

        $response = $this->get(route('admin.cleaner-applications.application-files.download', [$application, 'government-id']));

        $response->assertRedirect(route('login'));
    }

    private function createUser(string $role): User
    {
        $user = User::create([
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $role.'-cleaner-applications@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $role.'cleanerapp',
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function validCleanerApplicationPayload(array $overrides = []): array
    {
        return array_merge([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'individual_name' => 'Juan Dela Cruz',
            'team_business_name' => 'Bright Team Cleaners',
            'contact_person' => 'Maria Santos',
            'email' => 'cleaner-application@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '1995-01-15',
            'individual_current_address' => 'Poblacion, Valencia City, Bukidnon',
            'business_address' => 'Poblacion, Valencia City, Bukidnon',
            'profile_photo' => UploadedFile::fake()->create('profile.jpg', 120, 'image/jpeg'),
            'business_logo' => UploadedFile::fake()->create('logo.jpg', 120, 'image/jpeg'),
            'coverage_mode' => 'all',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => ['basic_cleaning', 'deep_cleaning'],
            'government_id_type' => CleanerApplication::GOVERNMENT_ID_NATIONAL_ID,
            'government_id_number' => '12345',
            'government_id_document' => UploadedFile::fake()->create('government-id.jpg', 120, 'image/jpeg'),
            'nbi_clearance_number' => 'NBI-12345',
            'nbi_clearance_document' => UploadedFile::fake()->create('clearance.pdf', 120, 'application/pdf'),
            'selfie_with_id' => UploadedFile::fake()->create('selfie.jpg', 120, 'image/jpeg'),
            'worked_as_cleaner_before' => '1',
            'worked_for_cleaning_company_before' => '1',
            'has_cleaning_certifications' => '0',
            'owns_cleaning_equipment' => '1',
            'available_days' => ['monday', 'wednesday', 'friday'],
            'max_daily_bookings' => 3,
            'terms_certify_accurate' => '1',
            'terms_agree_verification' => '1',
            'terms_approval_not_guaranteed' => '1',
            'terms_service_standards' => '1',
            'verification_notes' => 'Business permit and references available.',
        ], $overrides);
    }

    private function attachRequiredPayoutDocuments(CleanerApplication $application, User $uploader): void
    {
        foreach ($application->requiredPayoutDocumentTypes() as $documentType) {
            $application->documents()->create([
                'document_type' => $documentType,
                'original_filename' => $documentType.'.pdf',
                'file_path' => 'provider-documents/'.$application->id.'/'.$documentType.'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'uploaded_by' => $uploader->id,
            ]);
        }
    }
}
