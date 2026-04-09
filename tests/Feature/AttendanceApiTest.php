<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_record_a_late_time_in(): void
    {
        $staff = $this->createStaff('emp001');
        $device = $this->createDevice();

        $response = $this->withHeaders([
            'X-Device-Token' => $device->api_token,
        ])->postJson('/api/iot/attendance/punch', [
            'employee_code' => $staff->username,
            'punch_type' => 'in',
            'timestamp' => '2026-03-31 08:15:00',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'staff_name' => $staff->full_name,
                'punch_type' => 'in',
                'status' => 'late',
            ]);

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $staff->id,
            'device_id' => $device->id,
            'punch_type' => 'in',
            'status' => 'late',
            'source' => 'device',
        ]);

        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_auto_punch_switches_from_time_in_to_time_out_for_the_same_day(): void
    {
        $staff = $this->createStaff('emp002');
        $device = $this->createDevice('ESP32-02', str_repeat('b', 64));

        $first = $this->withHeaders([
            'X-Device-Token' => $device->api_token,
        ])->postJson('/api/iot/attendance/punch', [
            'employee_code' => $staff->username,
            'punch_type' => 'auto',
            'timestamp' => '2026-03-31 07:55:00',
        ]);

        $second = $this->withHeaders([
            'X-Device-Token' => $device->api_token,
        ])->postJson('/api/iot/attendance/punch', [
            'employee_code' => $staff->username,
            'punch_type' => 'auto',
            'timestamp' => '2026-03-31 17:10:00',
        ]);

        $first->assertOk()
            ->assertJson([
                'requested_punch_type' => 'auto',
                'punch_type' => 'in',
                'status' => 'present',
            ]);

        $second->assertOk()
            ->assertJson([
                'requested_punch_type' => 'auto',
                'punch_type' => 'out',
                'status' => 'present',
            ]);

        $this->assertSame(
            ['in', 'out'],
            AttendanceLog::where('user_id', $staff->id)
                ->orderBy('logged_at')
                ->pluck('punch_type')
                ->all()
        );
    }

    public function test_heartbeat_updates_last_seen_for_a_valid_device(): void
    {
        $device = $this->createDevice('ESP32-03', str_repeat('c', 64));

        $response = $this->withHeaders([
            'X-Device-Token' => $device->api_token,
        ])->postJson('/api/iot/device/heartbeat');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'device' => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'serial_number' => $device->serial_number,
                ],
            ]);

        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_punch_requires_a_device_token(): void
    {
        $staff = $this->createStaff('emp003');

        $response = $this->postJson('/api/iot/attendance/punch', [
            'employee_code' => $staff->username,
            'punch_type' => 'auto',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Missing device token.',
            ]);
    }

    private function createStaff(string $username): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'Staff',
            'email' => $username . '@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createDevice(string $serial = 'ESP32-01', string $token = ''): Device
    {
        return Device::create([
            'name' => 'Front Desk Device',
            'serial_number' => $serial,
            'api_token' => $token ?: str_repeat('a', 64),
            'location' => 'Front Desk',
            'is_active' => true,
        ]);
    }
}
