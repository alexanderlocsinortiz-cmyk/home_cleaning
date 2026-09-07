<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingServiceProof;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageMigrationCommandTest extends TestCase
{
    public function test_storage_migration_copies_private_and_public_files_without_deleting_sources(): void
    {
        Config::set('filesystems.disks.migration-private', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/migration-private'),
        ]);
        Config::set('filesystems.disks.migration-public', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/migration-public'),
        ]);
        Config::set('filesystems.private_uploads_disk', 'migration-private');
        Config::set('filesystems.public_uploads_disk', 'migration-public');

        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('migration-private');
        Storage::fake('migration-public');

        Storage::disk('local')->put('cleaner-applications/1/id.png', 'private file');
        Storage::disk('public')->put('booking-proofs/before/proof.png', 'public file');

        $this->artisan('storage:migrate-local')
            ->assertExitCode(0)
            ->expectsOutputToContain('Copied cleaner-applications/1/id.png')
            ->expectsOutputToContain('Copied booking-proofs/before/proof.png');

        Storage::disk('migration-private')->assertExists('cleaner-applications/1/id.png');
        Storage::disk('migration-public')->assertExists('booking-proofs/before/proof.png');
        Storage::disk('local')->assertExists('cleaner-applications/1/id.png');
        Storage::disk('public')->assertExists('booking-proofs/before/proof.png');
    }

    public function test_proof_migration_copies_legacy_public_files_and_can_remove_the_verified_source(): void
    {
        Config::set('filesystems.public_uploads_disk', 'legacy-public');
        Config::set('filesystems.proof_uploads_disk', 'proof-destination');
        Storage::fake('legacy-public');
        Storage::fake('proof-destination');

        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create(['staff_id' => $staff->id]);
        $proof = BookingServiceProof::create([
            'booking_id' => $booking->id,
            'uploaded_by' => $booking->user_id,
            'stage' => 'before',
            'media_type' => 'image',
            'file_path' => 'booking-proofs/before/legacy.jpg',
            'original_name' => 'legacy.jpg',
        ]);
        Storage::disk('legacy-public')->put($proof->file_path, 'legacy proof');
        $rating = Rating::create([
            'booking_id' => $booking->id,
            'client_id' => $booking->user_id,
            'staff_id' => $booking->staff_id,
            'stars' => 5,
            'comment' => 'Good',
            'photo' => 'ratings/legacy.jpg',
        ]);
        Storage::disk('legacy-public')->put($rating->photo, 'legacy rating');

        $this->artisan('storage:migrate-proof-media --include-ratings --dry-run')
            ->assertExitCode(0)
            ->expectsOutputToContain('Would copy booking-proofs/before/legacy.jpg');

        Storage::disk('proof-destination')->assertMissing($proof->file_path);
        Storage::disk('proof-destination')->assertMissing($rating->photo);
        Storage::disk('legacy-public')->assertExists($proof->file_path);

        $this->artisan('storage:migrate-proof-media --include-ratings --delete-source')
            ->assertExitCode(0)
            ->expectsOutputToContain('Copied booking-proofs/before/legacy.jpg')
            ->expectsOutputToContain('Copied ratings/legacy.jpg')
            ->expectsOutputToContain('Deleted legacy source booking-proofs/before/legacy.jpg');

        Storage::disk('proof-destination')->assertExists($proof->file_path);
        Storage::disk('proof-destination')->assertExists($rating->photo);
        Storage::disk('legacy-public')->assertMissing($proof->file_path);
        Storage::disk('legacy-public')->assertMissing($rating->photo);
    }

    public function test_proof_migration_refuses_to_delete_source_when_existing_destination_is_corrupt(): void
    {
        Config::set('filesystems.public_uploads_disk', 'legacy-public');
        Config::set('filesystems.proof_uploads_disk', 'proof-destination');
        Storage::fake('legacy-public');
        Storage::fake('proof-destination');

        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create(['staff_id' => $staff->id]);
        $proof = BookingServiceProof::create([
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'before',
            'media_type' => 'image',
            'file_path' => 'booking-proofs/before/corrupt.jpg',
            'original_name' => 'corrupt.jpg',
        ]);
        Storage::disk('legacy-public')->put($proof->file_path, 'good source');
        Storage::disk('proof-destination')->put($proof->file_path, 'corrupt destination');

        $this->artisan('storage:migrate-proof-media --delete-source')
            ->assertExitCode(1)
            ->expectsOutputToContain('Existing destination content does not match source');

        Storage::disk('legacy-public')->assertExists($proof->file_path);
        Storage::disk('proof-destination')->assertExists($proof->file_path);
    }
}
