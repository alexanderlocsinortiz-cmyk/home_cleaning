<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceDiscoveryTest extends TestCase
{
    public function test_homepage_shows_active_services_as_clickable_cards(): void
    {
        $visibleService = $this->canonicalService([
            'slug' => 'deep',
            'name' => 'Deep Clean',
            'image_path' => null,
        ]);
        $hiddenService = Service::factory()->create([
            'name' => 'Hidden Service',
            'slug' => 'hidden-service',
            'is_active' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('services.show', $visibleService), false);
        $response->assertSee($visibleService->name, false);
        $response->assertDontSee($hiddenService->name, false);
    }

    public function test_public_service_page_shows_customer_facing_details_and_booking_link(): void
    {
        $service = $this->canonicalService([
            'slug' => 'deep',
            'name' => 'Deep Clean',
            'description' => 'Detailed cleaning for buildup and neglected zones.',
            'scope_included_areas' => 'Living rooms, bedrooms, kitchen, and bathrooms.',
            'scope_included_tasks' => 'Scrubbing, wiping, sweeping, and mopping.',
            'scope_excluded_tasks' => 'Hazardous materials and specialist restoration.',
            'scope_condition_limits' => 'The property must have safe and accessible areas.',
            'scope_equipment_policy' => 'The company brings standard tools; the customer provides water and power.',
            'scope_access_limits' => 'Locked or unsafe areas are excluded.',
            'scope_extra_work_policy' => 'Extra work requires a re-quote or second visit.',
            'scope_acceptance_criteria' => 'The customer reviews the checklist before completion.',
            'scope_status' => 'provisional',
        ]);

        $response = $this->get(route('services.show', $service));

        $response->assertOk();
        $response->assertSee($service->name, false);
        $response->assertSee($service->description, false);
        $response->assertSee('Scrubbing, wiping, sweeping, and mopping.', false);
        $response->assertSee('Hazardous materials and specialist restoration.', false);
        $response->assertSee('Supplies and equipment', false);
        $response->assertSee('Access and safety', false);
        $response->assertSee('If the work exceeds this scope', false);
        $response->assertSee('Completion and acceptance', false);
        $response->assertSee('Provisional planning scope', false);
        $response->assertSee('not a promise that every possible task is included', false);
        $response->assertSee(route('bookings.create', ['service' => $service->slug]), false);
    }

    public function test_public_office_service_page_uses_the_correct_category_label(): void
    {
        $service = $this->canonicalService([
            'slug' => 'office-deep',
            'name' => 'Office Cleaning (Deep)',
            'scope_included_areas' => 'Workstations, common areas, pantry, and restrooms.',
            'scope_included_tasks' => 'Detailed workstation and high-touch cleaning.',
            'scope_excluded_tasks' => 'Electronics disassembly and confidential-file handling.',
            'scope_condition_limits' => 'Moderate office buildup only.',
            'scope_equipment_policy' => 'The customer protects sensitive equipment.',
            'scope_access_limits' => 'Restricted areas remain closed.',
            'scope_extra_work_policy' => 'Specialist sanitation requires review.',
            'scope_acceptance_criteria' => 'The office contact reviews the checklist.',
        ]);

        $response = $this->get(route('services.show', $service));

        $response->assertOk();
        $response->assertSee('Office cleaning service', false);
        $response->assertDontSee('Home cleaning service', false);
        $response->assertSee('The customer protects sensitive equipment.', false);
    }

    public function test_homepage_uses_admin_display_order_for_service_cards(): void
    {
        $laterService = $this->canonicalService([
            'slug' => 'basic',
            'name' => 'Basic Clean',
            'sort_order' => 20,
        ]);
        $earlierService = $this->canonicalService([
            'slug' => 'deep',
            'name' => 'Deep Clean',
            'sort_order' => 10,
        ]);

        $html = $this->get(route('home'))->getContent();

        $this->assertLessThan(
            strpos($html, $laterService->name),
            strpos($html, $earlierService->name)
        );
    }

    public function test_homepage_service_cards_show_real_rating_stats_only(): void
    {
        $ratedService = $this->canonicalService([
            'slug' => 'deep',
            'name' => 'Deep Clean',
            'price' => 95,
        ]);
        $unratedService = $this->canonicalService([
            'slug' => 'postconstruction',
            'name' => 'Post Construction Cleaning',
            'price' => 105,
        ]);
        $completedBooking = Booking::factory()->create([
            'service_id' => $ratedService->id,
            'status' => 'completed',
        ]);
        $pendingBooking = Booking::factory()->create([
            'service_id' => $unratedService->id,
            'status' => 'pending',
        ]);

        Rating::factory()->create([
            'booking_id' => $completedBooking->id,
            'stars' => 5,
        ]);
        Rating::factory()->create([
            'booking_id' => $pendingBooking->id,
            'stars' => 1,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('&#8369;95 per sqm', false);
        $response->assertSee('5.0', false);
        $response->assertSee('1 review', false);
        $response->assertSee('No reviews yet', false);
    }

    public function test_inactive_service_cannot_be_opened_publicly(): void
    {
        $service = Service::factory()->create([
            'slug' => 'inactive-service',
            'is_active' => false,
        ]);

        $this->get(route('services.show', $service))->assertNotFound();
    }

    public function test_booking_page_prefills_the_service_from_a_service_detail_link(): void
    {
        $service = $this->canonicalService([
            'slug' => 'deep',
            'name' => 'Deep Clean',
        ]);
        $user = User::create([
            'first_name' => 'Service',
            'last_name' => 'Client',
            'email' => 'service-discovery@example.com',
            'phone' => '09171234568',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'servicediscovery',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($user)->get(route('bookings.create', ['service' => $service->slug]));

        $response->assertOk();
        $response->assertSee('value="deep" class="sr-only" checked', false);
    }
}
