<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\CleanerTeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanerTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_person_can_add_member_and_member_can_submit_private_verification_link(): void
    {
        Storage::fake('local');
        [$provider, $application] = $this->createProvider('team-owner@example.com', 'teamowner');

        $response = $this->actingAs($provider)->post(route('provider.team-members.store'), [
            'full_name' => 'Juan Cruz',
            'phone' => '09171234567',
        ]);

        $response->assertRedirect(route('provider.team-members'));
        $url = $response->getSession()->get('verification_url');
        $this->assertIsString($url);
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        $member = CleanerTeamMember::where('full_name', 'Juan Cruz')->firstOrFail();
        $this->assertSame(CleanerTeamMember::STATUS_INVITED, $member->status);
        $this->assertSame($application->id, $member->cleaner_application_id);
        $this->get($url)->assertOk()->assertSee('Verify your cleaner profile');

        $this->post(route('cleaner-team-members.verify.store', ['token' => $token]), [
            'full_name' => 'Juan Cruz',
            'phone' => '09171234567',
            'date_of_birth' => '1995-01-01',
            'current_address' => 'Poblacion, Valencia City',
            'government_id_type' => CleanerApplication::GOVERNMENT_ID_NATIONAL_ID,
            'government_id_number' => 'PH-12345',
            'government_id_front_document' => UploadedFile::fake()->create('id-front.pdf', 100, 'application/pdf'),
            'government_id_back_document' => UploadedFile::fake()->create('id-back.pdf', 100, 'application/pdf'),
            'nbi_clearance_number' => 'NBI-12345',
            'nbi_clearance_document' => UploadedFile::fake()->create('nbi.pdf', 100, 'application/pdf'),
            'selfie_with_id' => UploadedFile::fake()->createWithContent('selfie.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')),
            'consent' => '1',
        ])->assertOk()->assertSee('Verification submitted');

        $member->refresh();
        $this->assertSame(CleanerTeamMember::STATUS_PENDING, $member->status);
        $this->assertNull($member->verification_token_hash);
        $this->assertTrue($member->verificationDocumentsComplete());
        Storage::disk('local')->assertExists($member->government_id_front_document_path);
        Storage::disk('local')->assertExists($member->government_id_back_document_path);
        Storage::disk('local')->assertExists($member->nbi_clearance_document_path);
        Storage::disk('local')->assertExists($member->selfie_with_id_path);
    }

    public function test_admin_can_approve_member_and_contact_can_assign_only_approved_members(): void
    {
        [$provider, $application] = $this->createProvider('team-assign@example.com', 'teamassign');
        $admin = $this->createUser('admin', 'team-admin@example.com', 'teamadmin');
        $member = $application->teamMembers()->create($this->memberAttributes('Pending Cleaner', CleanerTeamMember::STATUS_PENDING));

        $this->actingAs($admin)->patch(route('admin.cleaner-team-members.update', $member), [
            'status' => CleanerTeamMember::STATUS_APPROVED,
            'admin_notes' => 'Documents checked.',
        ])->assertRedirect();

        $this->assertSame(CleanerTeamMember::STATUS_APPROVED, $member->fresh()->status);

        $rejected = $application->teamMembers()->create($this->memberAttributes('Rejected Cleaner', CleanerTeamMember::STATUS_REJECTED));
        $booking = Booking::create([
            'user_id' => $this->createUser('client', 'team-client@example.com', 'teamclient')->id,
            'cleaner_application_id' => $application->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'duration_minutes' => 60,
            'required_cleaners' => 1,
            'price' => 1200,
            'status' => 'confirmed',
            'provider_assignment_status' => 'accepted',
        ]);

        $this->actingAs($provider)->patch(route('provider.bookings.team-members.update', $booking), [
            'member_ids' => [$rejected->id],
        ])->assertRedirect()->assertSessionHasErrors('member_ids.0');
        $this->assertDatabaseMissing('booking_cleaner_team_members', [
            'booking_id' => $booking->id,
            'cleaner_team_member_id' => $rejected->id,
        ]);

        $this->actingAs($provider)->patch(route('provider.bookings.team-members.update', $booking), [
            'member_ids' => [$member->id],
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('booking_cleaner_team_members', [
            'booking_id' => $booking->id,
            'cleaner_team_member_id' => $member->id,
            'assigned_by' => $provider->id,
        ]);
    }

    public function test_contact_person_cannot_manage_another_team_members(): void
    {
        [$providerOne, $applicationOne] = $this->createProvider('team-one@example.com', 'teamone');
        [$providerTwo] = $this->createProvider('team-two@example.com', 'teamtwo');
        $member = $applicationOne->teamMembers()->create($this->memberAttributes('Team One Cleaner', CleanerTeamMember::STATUS_INVITED));

        $this->actingAs($providerTwo)->post(route('provider.team-members.resend-verification', $member))
            ->assertNotFound();

        $this->assertAuthenticatedAs($providerTwo);
    }

    private function createProvider(string $email, string $username): array
    {
        $user = $this->createUser('provider', $email, $username);
        $application = CleanerApplication::create([
            'user_id' => $user->id,
            'applicant_type' => CleanerApplication::TYPE_TEAM,
            'business_name' => 'Team '.$username,
            'contact_person' => $user->display_name,
            'email' => $email,
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => 5,
            'services_offered' => 'Basic Cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
            'activated_at' => now(),
        ]);

        return [$user->fresh('cleanerApplication'), $application];
    }

    private function memberAttributes(string $name, string $status): array
    {
        return [
            'full_name' => $name,
            'phone' => '09171234567',
            'date_of_birth' => '1995-01-01',
            'current_address' => 'Poblacion, Valencia City',
            'government_id_type' => CleanerApplication::GOVERNMENT_ID_NATIONAL_ID,
            'government_id_number' => 'PH-'.str_replace(' ', '-', strtolower($name)),
            'government_id_front_document_path' => 'test/id-front.pdf',
            'government_id_back_document_path' => 'test/id-back.pdf',
            'nbi_clearance_number' => 'NBI-'.str_replace(' ', '-', strtolower($name)),
            'nbi_clearance_document_path' => 'test/nbi.pdf',
            'selfie_with_id_path' => 'test/selfie.png',
            'consent_at' => now(),
            'submitted_at' => now(),
            'status' => $status,
            'availability_status' => CleanerTeamMember::AVAILABILITY_AVAILABLE,
        ];
    }

    private function createUser(string $role, string $email, string $username): User
    {
        $user = User::create([
            'first_name' => ucfirst($role),
            'last_name' => 'Tester',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
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
}
