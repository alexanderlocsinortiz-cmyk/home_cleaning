<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_settings_restriction_forms_require_a_duration_in_the_browser(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'staff']);
        User::factory()->create(['role' => 'client']);

        $html = $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->getContent();

        $this->assertSame(2, preg_match_all(
            '/<input[^>]+name="restriction_days"[^>]+required[^>]*>/i',
            $html
        ));
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
                'admin_new_password' => 'CleanFlow!Admin123',
                'admin_new_password_confirmation' => 'CleanFlow!Admin123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('CleanFlow!Admin123', $admin->fresh()->password));
    }

    public function test_admin_settings_show_database_backup_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Database Backup')
            ->assertSee(route('admin.settings.database-backup'), false)
            ->assertSee('data-database-backup-confirm', false)
            ->assertSee('text-amber-900 hidden" data-database-backup-confirm', false)
            ->assertSee('<label class="hidden" data-database-backup-confirm>', false)
            ->assertSee('name="database_backup_password"', false)
            ->assertSee('disabled', false)
            ->assertSee('Change backup password')
            ->assertSee(route('admin.settings.database-backup.password'), false)
            ->assertSee('Backup files may contain confidential information')
            ->assertSee('Store backup files securely.')
            ->assertSee('Database backup password')
            ->assertSee('Upload database to private cloud storage')
            ->assertSee(route('admin.settings.database-backup.cloud'), false);
    }

    public function test_database_backup_password_form_is_visible_without_javascript(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'id="database-backup-password-form" method="POST" action="'.route('admin.settings.database-backup.password').'" class="mt-4 rounded-2xl',
            $html
        );
        $this->assertStringContainsString('Set a backup password before downloading database backups.', $html);
    }

    public function test_database_backup_settings_show_when_the_password_is_configured(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SiteSetting::current()->update([
            'database_backup_password_hash' => Hash::make('backup-password-123'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Database backup password is configured. You can now download or upload backups securely.')
            ->assertDontSee('Set a backup password before downloading database backups.');
    }

    public function test_admin_must_set_backup_password_before_database_backup_download(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('correct-password'),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.settings').'#database-backup')
            ->post(route('admin.settings.database-backup'), [
                'database_backup_password' => 'correct-password',
            ])
            ->assertRedirect(route('admin.settings').'#database-backup')
            ->assertSessionHasErrors('database_backup_password');
    }

    public function test_admin_can_change_database_backup_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('admin-password'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.settings.database-backup.password'), [
                'database_backup_admin_password' => 'admin-password',
                'database_backup_new_password' => 'CleanFlow!Backup123',
                'database_backup_new_password_confirmation' => 'CleanFlow!Backup123',
            ])
            ->assertRedirect(route('admin.settings').'#database-backup')
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('CleanFlow!Backup123', SiteSetting::current()->database_backup_password_hash));
    }

    public function test_database_backup_password_validation_returns_to_database_backup_tab(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('admin-password'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.settings.database-backup.password'), [
                'database_backup_admin_password' => 'admin-password',
                'database_backup_new_password' => 'short',
                'database_backup_new_password_confirmation' => 'short',
            ])
            ->assertRedirect(route('admin.settings').'#database-backup')
            ->assertSessionHasErrors('database_backup_new_password');

        $this->assertNull(SiteSetting::current()->database_backup_password_hash);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('We could not save the database backup password.');
    }

    public function test_admin_can_download_sqlite_database_backup_with_backup_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('admin-password'),
        ]);
        SiteSetting::current()->update([
            'database_backup_password_hash' => Hash::make('backup-password-123'),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.database-backup'), [
                'database_backup_password' => 'backup-password-123',
            ]);

        $response->assertOk();
        $response->assertDownload();
        $this->assertMatchesRegularExpression('/\.(sqlite|sql)/', $response->headers->get('content-disposition'));
    }

    public function test_admin_can_upload_sqlite_database_backup_to_private_cloud_disk(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('admin-password'),
        ]);
        SiteSetting::current()->update([
            'database_backup_password_hash' => Hash::make('backup-password-123'),
        ]);

        config([
            'filesystems.disks.remote-backup' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/remote-backup'),
            ],
            'filesystems.database_backup_disk' => 'remote-backup',
            'filesystems.database_backup_prefix' => 'database-backups',
            'filesystems.database_backup_retention_count' => 30,
        ]);
        Storage::fake('remote-backup');

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.database-backup.cloud'), [
                'database_backup_password' => 'backup-password-123',
            ]);

        $response->assertRedirect(route('admin.settings').'#database-backup');
        $response->assertSessionHas('success');

        $files = Storage::disk('remote-backup')->allFiles('database-backups');

        $this->assertCount(1, $files);
        $this->assertMatchesRegularExpression('/database-backups\/cleanflow-sqlite-.*\.(sqlite|sql)$/', $files[0]);
        $this->assertNotEmpty(Storage::disk('remote-backup')->get($files[0]));

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Database backup uploaded to private cloud storage: database-backups/', false);
    }

    public function test_admin_cannot_upload_database_backup_with_wrong_backup_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('admin-password'),
        ]);
        SiteSetting::current()->update([
            'database_backup_password_hash' => Hash::make('backup-password-123'),
        ]);

        config([
            'filesystems.disks.remote-backup' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/remote-backup-wrong-password'),
            ],
            'filesystems.database_backup_disk' => 'remote-backup',
            'filesystems.database_backup_prefix' => 'database-backups',
        ]);
        Storage::fake('remote-backup');

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.database-backup.cloud'), [
                'database_backup_password' => 'wrong-password',
            ]);

        $response->assertRedirect(route('admin.settings').'#database-backup')
            ->assertSessionHasErrors([
                'database_backup_password' => 'Database backup password is incorrect.',
            ]);

        $this->assertSame([], Storage::disk('remote-backup')->allFiles());
    }

    public function test_database_backup_cloud_command_uploads_to_private_disk(): void
    {
        config([
            'filesystems.disks.remote-backup' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/remote-backup-command'),
            ],
            'filesystems.database_backup_disk' => 'remote-backup',
            'filesystems.database_backup_prefix' => 'database-backups',
            'filesystems.database_backup_retention_count' => 30,
        ]);
        Storage::fake('remote-backup');

        $this->artisan('database:backup-cloud')
            ->assertExitCode(0)
            ->expectsOutputToContain('Database backup uploaded: database-backups/');

        $files = Storage::disk('remote-backup')->allFiles('database-backups');

        $this->assertCount(1, $files);
        $this->assertNotEmpty(Storage::disk('remote-backup')->get($files[0]));
    }

    public function test_database_backup_cloud_command_refuses_the_public_uploads_disk(): void
    {
        config([
            'filesystems.public_uploads_disk' => 'public',
            'filesystems.database_backup_disk' => 'public',
        ]);
        Storage::fake('public');

        $this->artisan('database:backup-cloud')
            ->assertExitCode(1)
            ->expectsOutputToContain('Database backups cannot use the public uploads disk.');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_database_backup_cloud_command_refuses_public_object_visibility(): void
    {
        config([
            'filesystems.disks.public-backup' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/public-backup'),
                'visibility' => 'public',
            ],
            'filesystems.database_backup_disk' => 'public-backup',
        ]);
        Storage::fake('public-backup');

        $this->artisan('database:backup-cloud')
            ->assertExitCode(1)
            ->expectsOutputToContain('Database backups require private object visibility.');

        $this->assertSame([], Storage::disk('public-backup')->allFiles());
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
                'admin_new_password' => 'CleanFlow!Admin123',
                'admin_new_password_confirmation' => 'CleanFlow!Admin123',
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
