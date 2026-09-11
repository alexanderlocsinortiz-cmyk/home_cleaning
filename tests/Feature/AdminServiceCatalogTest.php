<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceAddOn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        Service::where('slug', 'deep')->delete();

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

    public function test_admin_service_price_cannot_overflow_the_decimal_column(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'Oversized Service',
            'price' => '100000000.00',
            'duration_minutes' => 120,
        ]);

        $response->assertSessionHasErrors('price');
        $this->assertDatabaseMissing('services', ['name' => 'Oversized Service']);
    }

    public function test_admin_service_description_has_a_practical_length_limit(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'Overlong Description Service',
            'description' => str_repeat('A', 5001),
            'price' => 100,
            'duration_minutes' => 120,
        ]);

        $response->assertSessionHasErrors('description');
        $this->assertDatabaseMissing('services', ['name' => 'Overlong Description Service']);
    }

    public function test_admin_add_on_price_cannot_overflow_the_decimal_column(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.add-ons.store'), [
            'label' => 'Oversized Add-on',
            'price' => '100000000.00',
        ]);

        $response->assertSessionHasErrors('price');
        $this->assertDatabaseMissing('service_add_ons', ['label' => 'Oversized Add-on']);
    }

    public function test_admin_service_price_cannot_have_more_than_two_decimal_places(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'name' => 'Fractional Service',
            'price' => '100.123',
            'duration_minutes' => 120,
        ]);

        $response->assertSessionHasErrors('price');
        $this->assertDatabaseMissing('services', ['name' => 'Fractional Service']);
    }

    public function test_admin_can_manually_edit_measurable_scope_controls(): void
    {
        $admin = $this->createAdmin();
        $service = $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'scope_max_floor_area' => 45,
            'scope_cleaner_count' => 2,
            'scope_status' => 'provisional',
            'scope_manual_review_above_limit' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => $service->name,
            'description' => $service->description,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'scope_max_floor_area' => 60,
            'scope_cleaner_count' => 2,
            'scope_status' => 'approved',
            'scope_manual_review_above_limit' => '1',
            'is_active' => '1',
            'scope_included_areas' => 'Living areas and kitchen.',
            'scope_included_tasks' => 'Dusting, sweeping, and mopping.',
            'scope_excluded_tasks' => 'Mold remediation and repairs.',
            'scope_condition_limits' => 'Routine condition only.',
            'scope_equipment_policy' => 'Company brings standard tools; customer provides water and power.',
            'scope_access_limits' => 'Safe and accessible areas only.',
            'scope_extra_work_policy' => 'Extra work requires a re-quote.',
            'scope_acceptance_criteria' => 'Customer reviews the completion checklist.',
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'scope_max_floor_area' => 60,
            'scope_cleaner_count' => 2,
            'scope_status' => 'approved',
            'scope_manual_review_above_limit' => true,
            'scope_included_tasks' => 'Dusting, sweeping, and mopping.',
        ]);
    }

    public function test_admin_cannot_approve_an_incomplete_scope_definition(): void
    {
        $admin = $this->createAdmin();
        $service = $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'scope_status' => 'provisional',
            'scope_max_floor_area' => 60,
            'scope_cleaner_count' => 2,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => $service->name,
            'description' => $service->description,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'scope_max_floor_area' => 60,
            'scope_cleaner_count' => 2,
            'scope_status' => 'approved',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'scope_included_areas',
            'scope_included_tasks',
            'scope_excluded_tasks',
            'scope_condition_limits',
            'scope_equipment_policy',
            'scope_access_limits',
            'scope_extra_work_policy',
            'scope_acceptance_criteria',
        ]);
        $this->assertSame('provisional', $service->fresh()->scope_status);
    }

    public function test_incomplete_approved_scope_is_treated_as_provisional_at_runtime(): void
    {
        $service = $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'scope_status' => 'approved',
            'scope_max_floor_area' => null,
        ]);

        $this->assertFalse($service->scopeApprovalIsComplete());
        $this->assertFalse($service->scopeIsApproved());
        $this->assertSame('provisional', $service->scopeSummary()['status']);
    }

    public function test_admin_can_set_service_display_order(): void
    {
        $admin = $this->createAdmin();
        $service = $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => $service->name,
            'description' => $service->description,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'sort_order' => 2,
            'scope_cleaner_count' => 1,
            'scope_status' => 'provisional',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'sort_order' => 2,
        ]);
    }

    public function test_admin_can_upload_replace_and_remove_a_service_image(): void
    {
        $admin = $this->createAdmin();
        $service = $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'description' => 'Detailed cleaning',
            'price' => 95,
            'is_active' => true,
        ]);
        $disk = config('filesystems.public_uploads_disk');
        Storage::fake($disk);

        $uploadResponse = $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => $service->name,
            'description' => $service->description,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'scope_cleaner_count' => 1,
            'scope_status' => 'provisional',
            'is_active' => '1',
            'image' => UploadedFile::fake()->create('deep-clean.jpg', 100, 'image/jpeg'),
        ]);

        $uploadResponse->assertRedirect(route('admin.services.index'));
        $service->refresh();
        $customPath = $service->image_path;
        $this->assertNotNull($customPath);
        Storage::disk($disk)->assertExists($customPath);

        $removeResponse = $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'name' => $service->name,
            'description' => $service->description,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'scope_cleaner_count' => 1,
            'scope_status' => 'provisional',
            'is_active' => '1',
            'remove_image' => '1',
        ]);

        $removeResponse->assertRedirect(route('admin.services.index'));
        $service->refresh();
        $this->assertNull($service->image_path);
        Storage::disk($disk)->assertMissing($customPath);
        $this->assertStringEndsWith('/images/services/optimized/deep.jpg', $service->image_url);
    }

    public function test_admin_service_index_shows_package_badges_without_quick_add_templates(): void
    {
        $admin = $this->createAdmin();

        $this->canonicalService([
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
        $response->assertSee('id="addon-sort-order" type="number"', false);
        $response->assertSee('min="0" max="9999" step="1"', false);
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
        $service = $this->canonicalService([
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
