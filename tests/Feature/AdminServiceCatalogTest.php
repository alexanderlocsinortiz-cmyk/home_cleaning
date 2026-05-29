<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceAddOn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_service_create_page_shows_direct_service_form_without_package_templates(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.services.create', ['template' => 'weeklymaintenance']));

        $response->assertOk();
        $response->assertSee('Service Details', false);
        $response->assertDontSee('Start from a package template', false);
        $response->assertDontSee('Template selected:', false);
        $response->assertDontSee('General/Regular Cleaning', false);
    }

    public function test_admin_can_store_a_standard_package_without_writing_the_default_description_manually(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'Deep Clean',
            'description' => '',
            'price' => 95,
            'duration_minutes' => 180,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseHas('services', [
            'slug' => 'deep',
            'description' => Service::packageMetadataFor('deep')['default_description'],
            'price' => 95,
            'is_active' => 1,
        ]);
    }

    public function test_admin_service_index_shows_package_badges_without_quick_add_templates(): void
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
        $response->assertDontSee('Recommended package templates', false);
        $response->assertDontSee('Add This Package', false);
    }

    public function test_admin_service_index_shows_booking_add_ons(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.services.index'));

        $response->assertOk();
        $response->assertSee('Booking Add-ons', false);
        $response->assertSee('Window Glass Cleaning', false);
        $response->assertSee('Add Add-on', false);
    }

    public function test_admin_can_create_update_and_deactivate_booking_add_on(): void
    {
        $admin = $this->createAdmin();

        $createResponse = $this->actingAs($admin)->post(route('admin.services.add-ons.store'), [
            'label' => 'Oven Cleaning',
            'description' => 'Interior oven degreasing.',
            'price' => 450,
            'sort_order' => 20,
            'is_active' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('service_add_ons', [
            'key' => 'oven_cleaning',
            'label' => 'Oven Cleaning',
            'price' => 450,
            'is_active' => true,
        ]);

        $addOn = ServiceAddOn::where('key', 'oven_cleaning')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('admin.services.add-ons.update', $addOn), [
            'label' => 'Oven Detail Cleaning',
            'description' => 'Interior oven degreasing and wipe-down.',
            'price' => 500,
            'sort_order' => 10,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('service_add_ons', [
            'id' => $addOn->id,
            'key' => 'oven_cleaning',
            'label' => 'Oven Detail Cleaning',
            'price' => 500,
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.services.add-ons.destroy', $addOn));

        $deleteResponse->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('service_add_ons', [
            'id' => $addOn->id,
            'is_active' => false,
        ]);
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
        $response->assertSessionHas('success', 'Service deactivated successfully. It is hidden from new bookings.');
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
