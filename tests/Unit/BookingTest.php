<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Tests\TestCase;

class BookingTest extends TestCase
{
    private User $client;

    private Service $service;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create(['role' => 'client']);
        $this->service = Service::factory()->create();
        $this->staff = Staff::factory()->create();
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
            'staff_id' => $this->staff->user_id,
        ]);

        $this->assertTrue($booking->staff->is($this->staff->user));
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
            'property_adjustment' => 50.00,
            'room_bathroom_fees' => 100.00,
            'floor_area_fees' => 75.00,
            'add_on_fees' => 30.00,
        ]);

        $this->assertEquals(570.00, $booking->base_price);
        $this->assertEquals(50.00, $booking->property_adjustment);
        $this->assertEquals(100.00, $booking->room_bathroom_fees);
    }

    public function test_booking_total_price_calculation()
    {
        $booking = Booking::factory()->create([
            'base_price' => 570.00,
            'property_adjustment' => 50.00,
            'room_bathroom_fees' => 100.00,
            'floor_area_fees' => 75.00,
            'add_on_fees' => 30.00,
        ]);

        $expectedTotal = 570.00 + 50.00 + 100.00 + 75.00 + 30.00;
        $actualTotal = $booking->base_price + $booking->property_adjustment +
                      $booking->room_bathroom_fees + $booking->floor_area_fees + $booking->add_on_fees;

        $this->assertEquals($expectedTotal, $actualTotal);
    }

    public function test_booking_can_be_cancelled()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);
        $booking->update(['status' => 'cancelled']);

        $this->assertEquals('cancelled', $booking->fresh()->status);
    }
}
