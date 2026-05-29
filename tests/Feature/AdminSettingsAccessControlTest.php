<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminSettingsAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_restrict_client_account_and_login_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create([
            'role' => 'client',
            'email' => 'restricted-client@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.settings.users.access', $client), [
                'action' => 'restrict',
                'restriction_days' => 3,
                'access_restriction_reason' => 'Policy violation',
            ])
            ->assertRedirect();

        $client->refresh();

        $this->assertTrue($client->hasActiveAccessRestriction());
        $this->assertSame('Policy violation', $client->access_restriction_reason);
        $this->assertDatabaseHas('access_restriction_histories', [
            'target_user_id' => $client->id,
            'actor_user_id' => $admin->id,
            'target_role' => 'client',
            'action' => 'account_restricted',
            'duration_days' => 3,
            'reason' => 'Policy violation',
        ]);

        auth()->logout();

        $this->post(route('login.store'), [
            'email' => 'restricted-client@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_restrict_a_staff_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($admin)
            ->patch(route('admin.settings.staff.pages', $staff), [
                'restricted_pages' => ['schedule'],
            ])
            ->assertRedirect();

        $this->actingAs($staff->fresh())
            ->get(route('staff.schedule'))
            ->assertForbidden();

        $this->actingAs($staff->fresh())
            ->get(route('staff.dashboard'))
            ->assertOk();

        $this->assertDatabaseHas('access_restriction_histories', [
            'target_user_id' => $staff->id,
            'actor_user_id' => $admin->id,
            'target_role' => 'staff',
            'action' => 'staff_pages_updated',
        ]);
    }

    public function test_admin_can_see_restriction_history_in_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create([
            'role' => 'client',
            'first_name' => 'History',
            'last_name' => 'Client',
            'email' => 'history-client@example.com',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.settings.users.access', $client), [
                'action' => 'restrict',
                'restriction_days' => 2,
                'access_restriction_reason' => 'Repeated no-show',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Restriction History')
            ->assertSee('History Client')
            ->assertSee('Repeated no-show');
    }

    public function test_admin_can_update_general_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.settings.general'), [
                'website_name' => 'CleanFlow Valencia',
                'logo' => $this->fakePngUpload(),
                'contact_email' => 'hello@cleanflow.test',
                'contact_phone' => '09171234567',
                'contact_address' => 'Valencia City Hall Area',
                'office_hours' => 'Monday - Friday, 9:00 AM - 4:00 PM',
                'admin_name' => 'Operations Admin',
                'admin_email' => 'admin@cleanflow.test',
                'admin_phone' => '09170000000',
            ])
            ->assertRedirect();

        $settings = SiteSetting::first();

        $this->assertSame('CleanFlow Valencia', $settings->website_name);
        $this->assertSame('hello@cleanflow.test', $settings->contact_email);
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('CleanFlow Valencia')
            ->assertSee('hello@cleanflow.test')
            ->assertSee('Operations Admin');
    }

    public function test_admin_can_change_password_from_general_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.settings.general'), [
                'website_name' => 'Home Cleaning Services',
                'admin_current_password' => 'old-password',
                'admin_new_password' => 'new-password-123',
                'admin_new_password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
    }

    public function test_admin_password_change_requires_current_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.settings'))
            ->patch(route('admin.settings.general'), [
                'website_name' => 'Home Cleaning Services',
                'admin_current_password' => 'wrong-password',
                'admin_new_password' => 'new-password-123',
                'admin_new_password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasErrors('admin_current_password');

        $this->assertTrue(Hash::check('old-password', $admin->fresh()->password));
    }

    private function fakePngUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cleanflow-logo-');

        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));

        return new UploadedFile($path, 'logo.png', 'image/png', null, true);
    }
}
