<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffSchedulePageTest extends TestCase
{
    public function test_staff_schedule_uses_the_business_timezone_for_today_and_month_boundaries(): void
    {
        $this->travelTo(Carbon::parse('2026-05-31 18:00:00', 'UTC'));

        $staff = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);
        $client = User::factory()->create(['role' => 'client']);

        Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'confirmed',
            'scheduled_date' => '2026-06-01',
            'scheduled_time' => '09:00',
        ]);

        $response = $this->actingAs($staff)->get(route('staff.schedule'));

        $response->assertOk();
        $response->assertSee('June 2026');
        $response->assertSee('1 job today');
        $response->assertSee('CF-');
    }
}
