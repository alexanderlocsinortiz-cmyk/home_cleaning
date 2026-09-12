<?php

namespace Tests\Feature;

use App\Models\CleanerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceAreaMapTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_SERVICE_AREA_NAMES = [
        'Poblacion',
        'Bagontaas',
        'Banlag',
        'Barobo',
        'Batangan',
        'Catumbalon',
        'Colonia',
        'Concepcion',
        'Dagat-Kidavao',
        'Guinoyuran',
        'Kahapunan',
        'Laligan',
        'Lilingayon',
        'Lourdes',
        'Lumbayao',
        'Lumbo',
        'Lurogan',
        'Maapag',
        'Mabuhay',
        'Mailag',
        'Mount Nebo',
        'Nabago',
        'Pinatilan',
        'San Carlos',
        'San Isidro',
        'Sinabuagan',
        'Sinayawan',
        'Sugod',
        'Tongantongan',
        'Tugaya',
        'Vintar',
    ];

    public function test_service_area_scope_matches_the_fixed_project_barangays(): void
    {
        $configuredNames = array_column(config('cleanflow.service_areas'), 'name');
        $expectedBarangays = array_combine(self::EXPECTED_SERVICE_AREA_NAMES, self::EXPECTED_SERVICE_AREA_NAMES);

        $this->assertSame(self::EXPECTED_SERVICE_AREA_NAMES, $configuredNames);
        $this->assertSame($expectedBarangays, config('cleanflow.barangays'));
        $this->assertSame(self::EXPECTED_SERVICE_AREA_NAMES, array_keys(config('cleanflow.barangay_centers')));
    }

    public function test_bukidnon_provider_coverage_contains_all_configured_cities_and_municipalities(): void
    {
        $coverageAreas = config('cleanflow.bukidnon_service_areas', []);

        $this->assertCount(22, $coverageAreas);
        $this->assertSame(
            array_values(config('cleanflow.bukidnon_coverage_areas', [])),
            array_column($coverageAreas, 'name')
        );
        $this->assertArrayHasKey('Malaybalay City', config('cleanflow.bukidnon_location_centers'));
        $this->assertArrayHasKey('Valencia City', config('cleanflow.bukidnon_location_centers'));
    }

    public function test_public_map_uses_the_canonical_service_area_config(): void
    {
        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertViewHas('barangays', config('cleanflow.service_areas'));
        $response->assertViewHas('coverageAreas', config('cleanflow.bukidnon_service_areas'));
        $response->assertViewHas('providerCoveragePoints', []);
        $this->assertSame(3, substr_count($response->getContent(), 'type="button" class="filter-btn'));
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['barangays'] === count(config('cleanflow.service_areas'));
        });
    }

    public function test_admin_service_area_map_uses_the_canonical_service_area_config(): void
    {
        $admin = $this->createUser('admin', 'admin-map@example.com', 'adminmap');

        $response = $this->actingAs($admin)->get(route('admin.service-areas'));

        $response->assertOk();
        $response->assertViewHas('barangays', config('cleanflow.service_areas'));
        $response->assertViewHas('coverageAreas', config('cleanflow.bukidnon_service_areas'));
        $this->assertSame(3, substr_count($response->getContent(), 'type="button" class="filter-btn'));
    }

    public function test_staff_service_area_map_uses_the_canonical_service_area_config(): void
    {
        $staff = $this->createUser('staff', 'staff-map@example.com', 'staffmap');

        $response = $this->actingAs($staff)->get(route('staff.service-areas'));

        $response->assertOk();
        $response->assertViewHas('barangays', config('cleanflow.service_areas'));
        $response->assertViewHas('coverageAreas', config('cleanflow.bukidnon_service_areas'));
        $this->assertSame(4, substr_count($response->getContent(), 'type="button" class="filter-btn'));
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['barangays'] === count(config('cleanflow.service_areas'));
        });
    }

    public function test_client_service_area_map_uses_the_canonical_service_area_config(): void
    {
        $client = $this->createUser('client', 'client-map@example.com', 'clientmap');

        $response = $this->actingAs($client)->get(route('client.service-areas'));

        $response->assertOk();
        $response->assertViewHas('barangays', config('cleanflow.service_areas'));
        $response->assertViewHas('coverageAreas', config('cleanflow.bukidnon_service_areas'));
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['barangays'] === count(config('cleanflow.service_areas'));
        });
    }

    public function test_provider_coverage_map_uses_area_centers_without_exposing_exact_provider_coordinates(): void
    {
        CleanerApplication::create([
            'applicant_type' => CleanerApplication::TYPE_INDIVIDUAL,
            'business_name' => 'Bukidnon Wide Cleaner',
            'contact_person' => 'Bukidnon Cleaner',
            'email' => 'bukidnon-wide@example.com',
            'phone' => '09171234567',
            'service_area' => 'All Bukidnon cities and municipalities',
            'coverage_barangays' => ['All Bukidnon cities and municipalities'],
            'services_offered' => 'Basic Cleaning, Deep Cleaning',
            'status' => CleanerApplication::STATUS_APPROVED,
            'activated_at' => now(),
            'location_area' => 'Malaybalay City',
            'location_latitude' => 8.1571234,
            'location_longitude' => 125.1285678,
        ]);

        $response = $this->get(route('map'));
        $points = $response->viewData('providerCoveragePoints');
        $malaybalayPoint = collect($points)->firstWhere('name', 'Malaybalay City');

        $this->assertNotNull($malaybalayPoint);
        $this->assertSame(1, $malaybalayPoint['provider_count']);
        $this->assertSame(8.157, round($malaybalayPoint['lat'], 3));
        $this->assertSame(125.128, round($malaybalayPoint['lng'], 3));
        $this->assertArrayNotHasKey('location_latitude', $malaybalayPoint);
        $this->assertArrayNotHasKey('location_longitude', $malaybalayPoint);
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
}
