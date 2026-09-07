<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingServiceProof;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileStaffBookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_booking_list_requires_staff_account(): void
    {
        User::factory()->create([
            'email' => 'mobile-client-staff-list@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'client',
        ]);

        $token = $this->loginToken('mobile-client-staff-list@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/staff/bookings')
            ->assertForbidden();
    }

    public function test_staff_can_fetch_only_assigned_bookings(): void
    {
        $staff = $this->staff('assigned-staff@example.com');
        $otherStaff = $this->staff('other-staff@example.com');
        $service = $this->service();
        $assigned = $this->booking($staff, $service, 'confirmed', 'Assigned Street');
        $this->booking($otherStaff, $service, 'confirmed', 'Other Street');

        $token = $this->loginToken('assigned-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/staff/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.id', $assigned->id)
            ->assertJsonPath('bookings.0.street_address', 'Assigned Street')
            ->assertJsonPath('stats.confirmed', 1);
    }

    public function test_staff_can_fetch_backend_performance_and_rank(): void
    {
        $staff = $this->staff('performance-staff@example.com');
        $otherStaff = $this->staff('top-performance-staff@example.com');
        $service = $this->service();
        $completed = $this->booking($staff, $service, 'completed', 'Performance Street');
        $this->booking($staff, $service, 'confirmed', 'Upcoming Street');
        Rating::create([
            'booking_id' => $completed->id,
            'client_id' => $completed->user_id,
            'staff_id' => $staff->id,
            'stars' => 4,
            'comment' => 'Good service.',
        ]);

        $topCompleted = $this->booking($otherStaff, $service, 'completed', 'Top Performance Street');
        Rating::create([
            'booking_id' => $topCompleted->id,
            'client_id' => $topCompleted->user_id,
            'staff_id' => $otherStaff->id,
            'stars' => 5,
            'comment' => 'Excellent service.',
        ]);

        $token = $this->loginToken('performance-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/staff/performance')
            ->assertOk()
            ->assertJsonPath('performance.staff_id', $staff->id)
            ->assertJsonPath('performance.total_bookings', 2)
            ->assertJsonPath('performance.completed_count', 1)
            ->assertJsonPath('performance.completion_rate', 50)
            ->assertJsonPath('performance.total_earnings', 2850)
            ->assertJsonPath('performance.average_rating', 4)
            ->assertJsonPath('performance.total_reviews', 1)
            ->assertJsonPath('performance.star_breakdown.4', 1)
            ->assertJsonPath('performance.rank', 2)
            ->assertJsonPath('performance.total_staff', 2)
            ->assertJsonPath('performance.leaderboard.0.staff_id', $otherStaff->id)
            ->assertJsonPath('performance.leaderboard.0.rank', 1);
    }

    public function test_staff_can_start_booking_with_before_proof_from_mobile(): void
    {
        Storage::fake('public');

        $staff = $this->staff('proof-start-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'Proof Start Street');
        $token = $this->loginToken('proof-start-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/start", [
                'before_photos' => [
                    UploadedFile::fake()->create('before-proof.jpg', 128, 'image/jpeg'),
                ],
                'proof_captured_at' => '2026-08-23T10:15:00+08:00',
                'proof_latitude' => 7.9073,
                'proof_longitude' => 125.092,
                'proof_source' => 'camera',
            ])
            ->assertOk()
            ->assertJsonPath('booking.status', 'in_progress')
            ->assertJsonPath('booking.before_photo_count', 1);

        $this->assertSame('in_progress', $booking->fresh()->status);
        $this->assertDatabaseHas('booking_service_proofs', [
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'before',
            'media_type' => 'image',
        ]);

        $proof = BookingServiceProof::where('booking_id', $booking->id)
            ->where('stage', 'before')
            ->firstOrFail();

        $this->assertSame('2026-08-23 02:15:00', $proof->captured_at->format('Y-m-d H:i:s'));
        $this->assertSame('7.9073000', (string) $proof->latitude);
        $this->assertSame('125.0920000', (string) $proof->longitude);
        $this->assertSame('camera', $proof->capture_source);
    }

    public function test_staff_can_complete_booking_with_after_proof_from_mobile(): void
    {
        Storage::fake('public');

        $staff = $this->staff('proof-complete-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'Proof Complete Street');
        $token = $this->loginToken('proof-complete-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/start", [
                'before_photos' => [
                    UploadedFile::fake()->create('before-proof.jpg', 128, 'image/jpeg'),
                ],
                ...$this->cameraProofMetadata(),
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/complete", [
                'after_photos' => [
                    UploadedFile::fake()->create('after-proof.jpg', 128, 'image/jpeg'),
                ],
                'proof_captured_at' => '2026-08-23T11:45:00+08:00',
                'proof_latitude' => 7.9081,
                'proof_longitude' => 125.0934,
                'proof_source' => 'camera',
            ])
            ->assertOk()
            ->assertJsonPath('booking.status', 'completed')
            ->assertJsonPath('booking.after_photo_count', 1)
            ->assertJsonPath('booking.payment_status', 'pending');

        $completedBooking = $booking->fresh();
        $this->assertSame('completed', $completedBooking->status);
        $this->assertSame('pending', $completedBooking->payment_status);
        $this->assertNull($completedBooking->payment_reference);
        $this->assertNull($completedBooking->paid_at);
        $this->assertDatabaseHas('booking_service_proofs', [
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'after',
            'media_type' => 'image',
        ]);

        $proof = BookingServiceProof::where('booking_id', $booking->id)
            ->where('stage', 'after')
            ->where('media_type', 'image')
            ->firstOrFail();

        $this->assertSame('2026-08-23 03:45:00', $proof->captured_at->format('Y-m-d H:i:s'));
        $this->assertSame('7.9081000', (string) $proof->latitude);
        $this->assertSame('125.0934000', (string) $proof->longitude);
        $this->assertSame('camera', $proof->capture_source);
    }

    public function test_staff_cannot_start_booking_without_camera_gps_metadata(): void
    {
        Storage::fake('public');

        $staff = $this->staff('proof-gps-required-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'GPS Required Street');
        $token = $this->loginToken('proof-gps-required-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/start", [
                'before_photos' => [
                    UploadedFile::fake()->create('before-proof.jpg', 128, 'image/jpeg'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'proof_captured_at',
                'proof_latitude',
                'proof_longitude',
                'proof_source',
            ]);

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_staff_cannot_start_booking_with_non_camera_proof_source(): void
    {
        Storage::fake('public');

        $staff = $this->staff('proof-source-required-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'Source Required Street');
        $token = $this->loginToken('proof-source-required-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/start", [
                'before_photos' => [
                    UploadedFile::fake()->create('before-proof.jpg', 128, 'image/jpeg'),
                ],
                ...$this->cameraProofMetadata([
                    'proof_source' => 'library',
                ]),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('proof_source');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_staff_cannot_complete_booking_without_camera_gps_metadata(): void
    {
        Storage::fake('public');

        $staff = $this->staff('proof-complete-gps-required-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'Complete GPS Required Street');
        $token = $this->loginToken('proof-complete-gps-required-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/start", [
                'before_photos' => [
                    UploadedFile::fake()->create('before-proof.jpg', 128, 'image/jpeg'),
                ],
                ...$this->cameraProofMetadata(),
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/complete", [
                'after_photos' => [
                    UploadedFile::fake()->create('after-proof.jpg', 128, 'image/jpeg'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'proof_captured_at',
                'proof_latitude',
                'proof_longitude',
                'proof_source',
            ]);

        $this->assertSame('in_progress', $booking->fresh()->status);
    }

    public function test_staff_cannot_start_booking_without_before_proof_from_mobile(): void
    {
        $staff = $this->staff('proof-required-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'Proof Required Street');
        $token = $this->loginToken('proof-required-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/start", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('before_photos');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_staff_cannot_complete_confirmed_booking_with_after_proof_directly(): void
    {
        $staff = $this->staff('direct-complete-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'Direct Complete Street');
        $token = $this->loginToken('direct-complete-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/complete", [
                'after_photos' => [
                    UploadedFile::fake()->create('after-proof.jpg', 128, 'image/jpeg'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_staff_cannot_update_another_staff_booking(): void
    {
        $staff = $this->staff('owner-staff@example.com');
        $otherStaff = $this->staff('intruder-staff@example.com');
        $service = $this->service();
        $booking = $this->booking($staff, $service, 'confirmed', 'Protected Street');
        $token = $this->loginToken('intruder-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/staff/bookings/{$booking->id}/start", [
                'before_photos' => [
                    UploadedFile::fake()->create('before-proof.jpg', 128, 'image/jpeg'),
                ],
                ...$this->cameraProofMetadata(),
            ])
            ->assertForbidden();

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    private function staff(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make('Password123'),
            'role' => 'staff',
        ]);
    }

    private function service(): Service
    {
        return $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'price' => 95,
            'duration_minutes' => 120,
            'is_active' => true,
        ]);
    }

    private function booking(User $staff, Service $service, string $status, string $streetAddress): Booking
    {
        return Booking::factory()->create([
            'user_id' => User::factory()->create([
                'role' => 'client',
                'phone' => '09123456789',
            ])->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'service_type' => $service->slug,
            'status' => $status,
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '08:00',
            'street_address' => $streetAddress,
            'barangay' => 'Poblacion',
            'property_type' => 'house',
            'floor_area' => 30,
            'price' => 2850,
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
        ]);
    }

    private function loginToken(string $email): string
    {
        return $this->postJson('/api/mobile/login', [
            'email' => $email,
            'password' => 'Password123',
        ])->json('token');
    }

    private function cameraProofMetadata(array $overrides = []): array
    {
        return array_replace([
            'proof_captured_at' => '2026-08-23T10:15:00+08:00',
            'proof_latitude' => 7.9073,
            'proof_longitude' => 125.092,
            'proof_source' => 'camera',
        ], $overrides);
    }
}
