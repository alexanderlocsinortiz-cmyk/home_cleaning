<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Tests\TestCase;

class BookingTest extends TestCase
{
    private User $client;

    private Service $service;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create(['role' => 'client']);
        $this->service = Service::factory()->create();
        $this->staff = User::factory()->create(['role' => 'staff']);
    }

    public function test_booking_can_be_created()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->client->id,
            'service_id' => $this->service->id,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $this->client->id,
        ]);
    }

    public function test_booking_has_correct_initial_status()
    {
        $booking = Booking::factory()->create();
        $this->assertEquals('pending', $booking->status);
    }

    public function test_booking_status_transition_is_valid()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        // Valid transition
        $this->assertTrue(in_array('confirmed', Booking::STATUS_TRANSITIONS['pending']));

        // Invalid transition
        $this->assertFalse(in_array('completed', Booking::STATUS_TRANSITIONS['pending']));
    }

    public function test_booking_payment_methods_are_defined()
    {
        $this->assertArrayHasKey('on_site_cash', Booking::PAYMENT_METHOD_LABELS);
        $this->assertArrayHasKey('gcash', Booking::PAYMENT_METHOD_LABELS);
        $this->assertArrayHasKey('maya', Booking::PAYMENT_METHOD_LABELS);
    }

    public function test_booking_belongs_to_client()
    {
        $booking = Booking::factory()->create(['user_id' => $this->client->id]);
        $this->assertTrue($booking->user->is($this->client));
    }

    public function test_booking_belongs_to_service()
    {
        $booking = Booking::factory()->create(['service_id' => $this->service->id]);
        $this->assertTrue($booking->service->is($this->service));
    }

    public function test_booking_can_have_staff_assigned()
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'staff_id' => $this->staff->id,
        ]);

        $this->assertTrue($booking->staff->is($this->staff));
    }

    public function test_booking_suspicious_review_statuses()
    {
        $statuses = Booking::MANUAL_REVIEW_STATUSES;
        $this->assertContains('not_required', $statuses);
        $this->assertContains('pending', $statuses);
        $this->assertContains('approved', $statuses);
        $this->assertContains('blocked', $statuses);
    }

    public function test_booking_preferred_staff_status_lifecycle()
    {
        $statuses = Booking::PREFERRED_STAFF_STATUSES;
        $this->assertContains('none', $statuses);
        $this->assertContains('requested', $statuses);
        $this->assertContains('unavailable', $statuses);
        $this->assertContains('assigned', $statuses);
    }

    public function test_booking_stores_pricing_breakdown()
    {
        $booking = Booking::factory()->create([
            'base_price' => 570.00,
            'property_fee' => 50.00,
            'rooms_fee' => 60.00,
            'bathrooms_fee' => 40.00,
            'floor_area_fee' => 75.00,
            'add_ons_fee' => 30.00,
        ]);

        $this->assertEquals(570.00, $booking->base_price);
        $this->assertEquals(50.00, $booking->property_fee);
        $this->assertEquals(60.00, $booking->rooms_fee);
        $this->assertEquals(40.00, $booking->bathrooms_fee);
    }

    public function test_booking_total_price_calculation()
    {
        $booking = Booking::factory()->create([
            'base_price' => 570.00,
            'property_fee' => 50.00,
            'rooms_fee' => 60.00,
            'bathrooms_fee' => 40.00,
            'floor_area_fee' => 75.00,
            'add_ons_fee' => 30.00,
        ]);

        $expectedTotal = 570.00 + 50.00 + 60.00 + 40.00 + 75.00 + 30.00;
        $actualTotal = $booking->base_price + $booking->property_fee +
                      $booking->rooms_fee + $booking->bathrooms_fee + $booking->floor_area_fee + $booking->add_ons_fee;

        $this->assertEquals($expectedTotal, $actualTotal);
    }

    public function test_booking_can_be_cancelled()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);
        $booking->update(['status' => 'cancelled']);

        $this->assertEquals('cancelled', $booking->fresh()->status);
    }
}
