<?php

namespace Tests\Feature;

use App\Models\CleanerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProviderDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_provider_directory_defaults_to_approved_providers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $individual = $this->createCleanerApplication('Solo Shine Cleaner', CleanerApplication::TYPE_INDIVIDUAL, CleanerApplication::STATUS_APPROVED);
        $team = $this->createCleanerApplication('Bright Team Cleaners', CleanerApplication::TYPE_TEAM, CleanerApplication::STATUS_APPROVED, 4);
        $pending = $this->createCleanerApplication('Pending Cleaner', CleanerApplication::TYPE_INDIVIDUAL, CleanerApplication::STATUS_PENDING);

        $response = $this->actingAs($admin)->get(route('admin.providers'));

        $response->assertOk();
        $response->assertSee('Provider directory');
        $response->assertSee($individual->business_name);
        $response->assertSee($team->business_name);
        $response->assertSee('4 team members');
        $response->assertDontSee($pending->business_name);
        $response->assertSee('Approved providers');
    }

    public function test_admin_provider_directory_can_filter_by_provider_type_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $individual = $this->createCleanerApplication('Solo Shine Cleaner', CleanerApplication::TYPE_INDIVIDUAL, CleanerApplication::STATUS_APPROVED);
        $team = $this->createCleanerApplication('Bright Team Cleaners', CleanerApplication::TYPE_TEAM, CleanerApplication::STATUS_APPROVED, 4);
        $pendingTeam = $this->createCleanerApplication('Pending Team', CleanerApplication::TYPE_TEAM, CleanerApplication::STATUS_PENDING, 3);

        $response = $this->actingAs($admin)->get(route('admin.providers', [
            'type' => CleanerApplication::TYPE_TEAM,
        ]));

        $response->assertOk();
        $response->assertSee($team->business_name);
        $response->assertDontSee($individual->business_name);
        $response->assertDontSee($pendingTeam->business_name);

        $response = $this->actingAs($admin)->get(route('admin.providers', [
            'status' => CleanerApplication::STATUS_PENDING,
        ]));

        $response->assertOk();
        $response->assertSee($pendingTeam->business_name);
        $response->assertDontSee($team->business_name);
    }

    public function test_non_admin_cannot_open_provider_directory(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->get(route('admin.providers'));

        $response->assertForbidden();
    }

    public function test_admin_can_pause_an_approved_provider_from_the_directory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = $this->createCleanerApplication('Solo Shine Cleaner', CleanerApplication::TYPE_INDIVIDUAL, CleanerApplication::STATUS_APPROVED);

        $response = $this->actingAs($admin)->patch(route('admin.providers.availability', $provider), [
            'availability_status' => CleanerApplication::AVAILABILITY_PAUSED,
            'availability_notes' => 'Provider requested a short break.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cleaner_applications', [
            'id' => $provider->id,
            'availability_status' => CleanerApplication::AVAILABILITY_PAUSED,
            'availability_notes' => 'Provider requested a short break.',
        ]);
        $this->assertDatabaseHas('cleaner_application_activity_logs', [
            'cleaner_application_id' => $provider->id,
            'action' => 'availability_changed',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_admin_provider_directory_shows_pinned_provider_locations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = $this->createCleanerApplication('Mapped Provider', CleanerApplication::TYPE_INDIVIDUAL, CleanerApplication::STATUS_APPROVED);
        $provider->update([
            'location_area' => 'Malaybalay City',
            'location_latitude' => 8.1571234,
            'location_longitude' => 125.1285678,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.providers'));

        $response->assertOk();
        $response->assertSee('Provider location map');
        $response->assertSee('Showing 1 pinned provider');
        $response->assertSee('Malaybalay City');
    }

    private function createCleanerApplication(string $name, string $type, string $status, ?int $teamSize = null): CleanerApplication
    {
        return CleanerApplication::create([
            'applicant_type' => $type,
            'business_name' => $name,
            'contact_person' => $type === CleanerApplication::TYPE_TEAM ? 'Team Manager' : $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.com',
            'phone' => '09171234567',
            'service_area' => 'Valencia City',
            'years_experience' => 3,
            'team_size' => $teamSize,
            'services_offered' => 'Basic Cleaning',
            'status' => $status,
        ]);
    }
}
