<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffPerformancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_performance_page_renders_scorecard(): void
    {
        $staff = User::create([
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => 'staff-performance@example.com',
            'phone' => '09170000001',
            'date_of_birth' => '1998-01-01',
            'gender' => 'male',
            'street' => '456 Mabini Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'staffperformance',
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($staff)->get(route('staff.performance'));

        $response->assertOk();
        $response->assertSee('Service quality scorecard');
        $response->assertSee('Performance snapshot');
        $response->assertSee('Customer reviews');
    }
}
