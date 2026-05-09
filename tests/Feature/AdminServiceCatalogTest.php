<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_service_create_page_can_prefill_a_package_template(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.services.create', ['template' => 'weeklymaintenance']));

        $response->assertOk();
        $response->assertSee('Template selected: Maintenance Plan', false);
        $response->assertSee('Weekly Maintenance Plan', false);
        $response->assertSee('&#8369;900 suggested start', false);
    }

    public function test_admin_can_store_a_standard_package_without_writing_the_default_description_manually(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'Deep Clean',
            'description' => '',
            'price' => 1200,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseHas('services', [
            'slug' => 'deep',
            'description' => Service::packageMetadataFor('deep')['default_description'],
            'price' => 1200,
            'is_active' => 1,
        ]);
    }

    public function test_admin_service_index_shows_package_badges_and_quick_add_templates(): void
    {
        $admin = $this->createAdmin();

        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.services.index'));

        $response->assertOk();
        $response->assertSee('Signature Package', false);
        $response->assertSee('Recommended package templates', false);
        $response->assertSee('Add This Package', false);
    }

    public function test_admin_archives_service_instead_of_deleting_it(): void
    {
        $admin = $this->createAdmin();
        $service = Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.services.destroy', $service));

        $response->assertRedirect(route('admin.services.index'));
        $response->assertSessionHas('success', 'Service archived successfully. Existing booking history remains intact.');
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'is_active' => false,
        ]);
    }

    private function createAdmin(): User
    {
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin-service@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '1995-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'adminservice',
            'role' => 'admin',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }
}
