<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAttendanceDeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_attendance_device_from_the_attendance_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.attendance.devices.store'), [
            'name' => 'Front Desk Device',
            'serial_number' => 'ESP32-FRONT-01',
            'location' => 'Main Office',
        ]);

        $device = Device::where('serial_number', 'ESP32-FRONT-01')->first();

        $this->assertNotNull($device);
        $this->assertSame('Front Desk Device', $device->name);
        $this->assertSame('Main Office', $device->location);
        $this->assertTrue($device->is_active);
        $this->assertSame(64, strlen($device->api_token));

        $response->assertRedirect(route('admin.attendance'));
        $response->assertSessionHas('generated_device_token', $device->api_token);
        $response->assertSessionHas('generated_device_name', 'Front Desk Device');
        $response->assertSessionHas('generated_device_serial', 'ESP32-FRONT-01');
    }

    public function test_admin_can_rotate_an_attendance_device_token_from_the_attendance_page(): void
    {
        $admin = $this->createAdmin();
        $device = Device::create([
            'name' => 'Lobby Device',
            'serial_number' => 'ESP32-LOBBY-01',
            'api_token' => str_repeat('x', 64),
            'location' => 'Lobby',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.attendance.devices.rotate-token', $device));

        $response->assertRedirect(route('admin.attendance'));
        $response->assertSessionHas('generated_device_token', $device->fresh()->api_token);
        $this->assertNotSame(str_repeat('x', 64), $device->fresh()->api_token);
        $this->assertSame(64, strlen($device->fresh()->api_token));
    }

    private function createAdmin(): User
    {
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin-attendance@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'adminattendance',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }
}
