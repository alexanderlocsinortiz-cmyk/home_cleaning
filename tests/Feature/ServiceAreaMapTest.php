<?php

namespace Tests\Feature;

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

    public function test_public_map_uses_the_canonical_service_area_config(): void
    {
        $response = $this->get(route('map'));

        $response->assertOk();
        $response->assertViewHas('barangays', config('cleanflow.service_areas'));
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
    }

    public function test_staff_service_area_map_uses_the_canonical_service_area_config(): void
    {
        $staff = $this->createUser('staff', 'staff-map@example.com', 'staffmap');

        $response = $this->actingAs($staff)->get(route('staff.service-areas'));

        $response->assertOk();
        $response->assertViewHas('barangays', config('cleanflow.service_areas'));
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
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['barangays'] === count(config('cleanflow.service_areas'));
        });
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
