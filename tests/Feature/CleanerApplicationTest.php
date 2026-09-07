<?php

namespace Tests\Feature;

use App\Mail\CleanerApplicationDecision;
use App\Models\CleanerApplication;
use App\Models\CleanerApplicationDocument;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanerApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaner_application_coverage_selector_supports_multiple_areas(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('id="coverage_barangays" name="coverage_barangays[]" multiple', false);
    }

    public function test_background_questions_require_an_explicit_answer(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('name="worked_as_cleaner_before" value="1" required', false);
        $response->assertDontSee('name="worked_as_cleaner_before" value="0" class="h-4 w-4 text-blue-600" checked', false);
    }

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
            'status' => CleanerApplication::STATUS_PENDING,
        ]);

        $this->assertSame('12345', CleanerApplication::where('email', 'bright@example.com')->firstOrFail()->government_id_number);

        $application = CleanerApplication::where('email', 'bright@example.com')->firstOrFail();
        Storage::disk('local')->assertExists($application->government_id_document_path);
        Storage::disk('local')->assertExists($application->selfie_with_id_path);
    }

    public function test_applicant_receives_a_private_tracking_link_after_submission(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'tracking@example.com',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $token = $response->getSession()->get('tracking_token');

        $this->assertNotEmpty($token);
        $statusResponse = $this->get(route('cleaner-applications.status', ['token' => $token]));

        $statusResponse->assertOk();
        $statusResponse->assertSee('Application status', false);
        $statusResponse->assertSee('Pending', false);
    }

    public function test_application_form_shows_upload_guidance_and_final_review(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('JPG, PNG, or PDF. Maximum 5 MB.', false);
        $response->assertSee('Use a clear JPG or PNG image. Maximum 5 MB.', false);
        $response->assertSee('Final review', false);
        $response->assertSee('data-summary-value="files"', false);
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

    public function test_government_id_number_rejects_unsupported_characters(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'invalid-id-format@example.com',
            'government_id_number' => 'ID/12345',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('government_id_number');
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => 'invalid-id-format@example.com',
        ]);
    }

    public function test_government_id_number_accepts_alphanumeric_formats(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'alphanumeric-id@example.com',
            'government_id_number' => 'N01-12-123456',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHas('success');
        $application = CleanerApplication::where('email', 'alphanumeric-id@example.com')->firstOrFail();
        $this->assertSame('N01-12-123456', $application->government_id_number);
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

    public function test_mobile_number_must_be_a_valid_philippine_mobile_number(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'short-phone@example.com',
            'phone' => '1234567890',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => 'short-phone@example.com',
        ]);
    }

    public function test_cleaner_application_phone_field_matches_server_validation(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('pattern="09[0-9]{9}" maxlength="11"', false);
    }

    public function test_cleaner_application_id_field_matches_supported_formats(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('pattern="[A-Za-z0-9][A-Za-z0-9 -]{0,99}" maxlength="100"', false);
    }

    public function test_individual_cleaner_must_be_at_least_18_years_old(): void
    {
        Storage::fake('local');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'underage-cleaner@example.com',
            'date_of_birth' => now()->subYears(17)->toDateString(),
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('date_of_birth');
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => 'underage-cleaner@example.com',
        ]);
    }

    public function test_cleaner_application_date_of_birth_matches_the_18_plus_policy(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('max="'.now(config('cleanflow.attendance_timezone', config('app.timezone')))->subYears(18)->toDateString().'"', false);
    }

    public function test_cleaner_application_reopens_at_the_earliest_step_with_errors(): void
    {
        Storage::fake('local');

        $payload = $this->validCleanerApplicationPayload();
        unset($payload['government_id_document'], $payload['terms_certify_accurate']);

        $this->from(route('cleaner-applications.create'))
            ->post(route('cleaner-applications.store'), $payload)
            ->assertRedirect(route('cleaner-applications.create'));

        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('data-initial-step="3"', false);
    }

    public function test_cleaner_application_prioritizes_step_one_errors_over_later_errors(): void
    {
        $payload = $this->validCleanerApplicationPayload();
        unset($payload['individual_name'], $payload['government_id_document']);

        $this->from(route('cleaner-applications.create'))
            ->post(route('cleaner-applications.store'), $payload)
            ->assertRedirect(route('cleaner-applications.create'));

        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('data-initial-step="1"', false);
    }

    public function test_cleaner_application_explains_verification_data_privacy(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('Your ID, selfie, date of birth, and optional clearance details are collected only for cleaner verification', false);
        $response->assertSee(route('legal.privacy'), false);
    }

    public function test_cleaner_application_draft_notice_excludes_sensitive_browser_storage(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('Personal contact and identity details are never saved in the browser draft.', false);
        $response->assertSee("'government_id_number'", false);
        $response->assertSee("'verification_notes'", false);
        $response->assertSee('Object.entries(savedValues).filter', false);
    }

    public function test_admin_application_list_masks_identity_numbers(): void
    {
        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Masked Identity Cleaner',
            'contact_person' => 'Masked Identity Cleaner',
            'email' => 'masked-identity@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'max_daily_bookings' => 5,
            'government_id_type' => CleanerApplication::GOVERNMENT_ID_NATIONAL_ID,
            'government_id_number' => '1234567890',
            'nbi_clearance_number' => 'NBI-123456',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cleaner-applications.index'));

        $response->assertOk();
        $response->assertSee('••••••7890', false);
        $response->assertSee('••••••3456', false);
        $response->assertSee('5 bookings / day', false);
        $response->assertDontSee('5+ bookings / day', false);
        $response->assertDontSee('1234567890', false);
        $response->assertDontSee('NBI-123456', false);
    }

    public function test_admin_application_list_can_search_and_filter_document_completeness(): void
    {
        $admin = $this->createUser('admin');
        CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Searchable Cleaner',
            'contact_person' => 'Searchable Cleaner',
            'email' => 'searchable@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'government_id_document_path' => 'cleaner-applications/searchable/id.pdf',
            'selfie_with_id_path' => 'cleaner-applications/searchable/selfie.jpg',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cleaner-applications.index', [
            'search' => 'Searchable',
            'documents' => 'complete',
        ]));

        $response->assertOk();
        $response->assertSee('Searchable Cleaner', false);
        $response->assertSee('Required documents complete', false);
    }

    public function test_cleaner_application_capacity_label_matches_enforced_limit(): void
    {
        $response = $this->get(route('cleaner-applications.create'));

        $response->assertOk();
        $response->assertSee('value="5"', false);
        $response->assertDontSee('>5+</option>', false);
    }

    public function test_email_cannot_submit_a_second_pending_application(): void
    {
        Storage::fake('local');

        CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Existing Cleaner',
            'contact_person' => 'Existing Cleaner',
            'email' => 'duplicate@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'status' => CleanerApplication::STATUS_PENDING,
        ]);

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => ' DUPLICATE@EXAMPLE.COM ',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors('email');
        $this->assertSame(1, CleanerApplication::where('email', 'duplicate@example.com')->count());
    }

    public function test_existing_user_email_is_rejected_before_cleaner_application_submission(): void
    {
        Storage::fake('local');
        $user = $this->createUser('client');

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => strtoupper($user->email),
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHasErrors([
            'email' => 'This email already has a CleanFlow account. Use another email or contact CleanFlow admin before applying.',
        ]);
        $this->assertDatabaseMissing('cleaner_applications', [
            'email' => $user->email,
        ]);
    }

    public function test_database_blocks_duplicate_active_application_even_without_http_validation(): void
    {
        CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Existing Cleaner',
            'contact_person' => 'Existing Cleaner',
            'email' => 'database-duplicate@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
        ]);

        $this->expectException(QueryException::class);

        CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Concurrent Cleaner',
            'contact_person' => 'Concurrent Cleaner',
            'email' => ' DATABASE-DUPLICATE@EXAMPLE.COM ',
            'phone' => '09171234568',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'status' => CleanerApplication::STATUS_PENDING,
        ]);
    }

    public function test_verification_numbers_are_encrypted_at_rest(): void
    {
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Encrypted Cleaner',
            'contact_person' => 'Encrypted Cleaner',
            'email' => 'encrypted@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'government_id_number' => 'N01-12-123456',
            'nbi_clearance_number' => 'NBI-123456',
        ]);

        $stored = DB::table('cleaner_applications')->where('id', $application->id)->first();

        $this->assertNotSame('N01-12-123456', $stored->government_id_number);
        $this->assertNotSame('NBI-123456', $stored->nbi_clearance_number);
        $this->assertSame('N01-12-123456', $application->fresh()->government_id_number);
        $this->assertSame('NBI-123456', $application->fresh()->nbi_clearance_number);
    }

    public function test_expired_rejected_application_identity_data_is_purged_but_record_remains(): void
    {
        Storage::fake('local');

        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Expired Cleaner',
            'contact_person' => 'Expired Cleaner',
            'email' => 'expired-privacy@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
            'current_address' => 'Private address',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'government_id_number' => 'N01-12-123456',
            'government_id_document_path' => 'cleaner-applications/1/id.pdf',
            'government_id_document_original_filename' => 'id.pdf',
            'nbi_clearance_number' => 'NBI-123456',
            'nbi_clearance_document_path' => 'cleaner-applications/1/clearance.pdf',
            'nbi_clearance_document_original_filename' => 'clearance.pdf',
            'selfie_with_id_path' => 'cleaner-applications/1/selfie.jpg',
            'selfie_with_id_original_filename' => 'selfie.jpg',
            'status' => CleanerApplication::STATUS_REJECTED,
        ]);
        Storage::disk('local')->put('cleaner-applications/1/id.pdf', 'id');
        Storage::disk('local')->put('cleaner-applications/1/clearance.pdf', 'clearance');
        Storage::disk('local')->put('cleaner-applications/1/selfie.jpg', 'selfie');
        $application->forceFill(['created_at' => now()->subDays(181)])->save();

        $this->artisan('cleaner-applications:purge-sensitive-data')
            ->assertExitCode(0)
            ->expectsOutputToContain('Purged');

        $application->refresh();

        $this->assertSame('[Purged application #'.$application->id.']', $application->business_name);
        $this->assertSame('purged-'.$application->id.'@invalid.cleanflow', $application->email);
        $this->assertNull($application->government_id_number);
        $this->assertNull($application->nbi_clearance_number);
        $this->assertNull($application->government_id_document_path);
        $this->assertNotNull($application->sensitive_data_purged_at);
        Storage::disk('local')->assertMissing('cleaner-applications/1/id.pdf');
        Storage::disk('local')->assertMissing('cleaner-applications/1/clearance.pdf');
        Storage::disk('local')->assertMissing('cleaner-applications/1/selfie.jpg');
        $this->assertDatabaseHas('cleaner_applications', ['id' => $application->id]);
    }

    public function test_rejected_applicant_can_submit_a_new_application(): void
    {
        Storage::fake('local');

        CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Previously Rejected Cleaner',
            'contact_person' => 'Previously Rejected Cleaner',
            'email' => 'reapply@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Basic Cleaning',
            'status' => CleanerApplication::STATUS_REJECTED,
        ]);

        $response = $this->from(route('cleaner-applications.create'))->post(route('cleaner-applications.store'), $this->validCleanerApplicationPayload([
            'email' => 'REAPPLY@EXAMPLE.COM',
        ]));

        $response->assertRedirect(route('cleaner-applications.create'));
        $response->assertSessionHas('success');
        $this->assertSame(2, CleanerApplication::where('email', 'reapply@example.com')->count());
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

    public function test_admin_can_request_changes_and_applicant_can_see_the_note(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin');
        $application = CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Changes Needed Cleaner',
            'contact_person' => 'Changes Needed Cleaner',
            'email' => 'changes-needed@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 2,
            'services_offered' => 'Residential cleaning',
        ]);
        $oldTrackingToken = $application->issueTrackingToken();

        $response = $this->actingAs($admin)->patch(route('admin.cleaner-applications.update', $application), [
            'status' => CleanerApplication::STATUS_NEEDS_CHANGES,
            'admin_notes' => 'Please upload a clearer government ID image.',
        ]);

        $response->assertRedirect(route('admin.cleaner-applications.index'));
        $application->refresh();
        $this->assertSame(CleanerApplication::STATUS_NEEDS_CHANGES, $application->status);
        $this->assertNotEmpty($application->tracking_token_hash);
        $this->get(route('cleaner-applications.status', ['token' => $oldTrackingToken]))->assertOk();
        $this->assertDatabaseHas('cleaner_application_activity_logs', [
            'cleaner_application_id' => $application->id,
            'action' => 'status_changed',
        ]);

        Mail::assertSent(CleanerApplicationDecision::class, function (CleanerApplicationDecision $mail) use ($application): bool {
            return $mail->hasTo($application->email)
                && $mail->application->status === CleanerApplication::STATUS_NEEDS_CHANGES
                && filled($mail->trackingToken);
        });
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
        $this->assertDatabaseHas('cleaner_application_activity_logs', [
            'cleaner_application_id' => $application->id,
            'action' => 'verification_file_downloaded',
        ]);
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
