<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RelationshipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_booking_can_have_only_one_rating_at_database_level(): void
    {
        $booking = Booking::factory()->create();

        Rating::factory()->create(['booking_id' => $booking->id]);

        $this->expectException(QueryException::class);

        Rating::factory()->create(['booking_id' => $booking->id]);
    }

    public function test_attendance_uses_the_user_as_its_only_staff_owner(): void
    {
        $this->assertTrue(Schema::hasColumn('attendance_logs', 'user_id'));
        $this->assertFalse(Schema::hasColumn('attendance_logs', 'staff_id'));
        $this->assertFalse(Schema::hasColumn('attendance_logs', 'punched_at'));
        $this->assertFalse(Schema::hasColumn('attendance_logs', 'fingerprint_template_id'));
        $this->assertFalse(Schema::hasTable('staff'));
        $this->assertTrue(Schema::hasTable('legacy_staff_records'));
    }
}
