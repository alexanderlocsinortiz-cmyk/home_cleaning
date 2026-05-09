<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceEnrollmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceEnrollmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_fingerprint_enrollment_request_from_the_attendance_page(): void
    {
        $admin = $this->createUser('admin', 'admin-enroll@example.com', 'adminenroll');
        $staff = $this->createUser('staff', 'staff-enroll@example.com', 'staffenroll');
        $device = $this->createDevice();

        $response = $this->actingAs($admin)->post(route('admin.attendance.enrollments.store'), [
            'device_id' => $device->id,
            'user_id' => $staff->id,
            'template_id' => 7,
        ]);

        $response->assertRedirect(route('admin.attendance'));
        $this->assertDatabaseHas('device_enrollment_requests', [
            'device_id' => $device->id,
            'user_id' => $staff->id,
            'requested_by' => $admin->id,
            'template_id' => 7,
            'status' => 'pending',
        ]);
    }

    public function test_device_can_fetch_the_next_pending_enrollment_request(): void
    {
        $staff = $this->createUser('staff', 'staff-fetch@example.com', 'stafffetch');
        $device = $this->createDevice('ESP32-ENROLL-02', str_repeat('d', 64));

        $request = DeviceEnrollmentRequest::create([
            'device_id' => $device->id,
            'user_id' => $staff->id,
            'template_id' => 9,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            ...$this->signedDeviceHeaders($device, 'GET', '/api/iot/device/enrollment/next'),
        ])->getJson('/api/iot/device/enrollment/next');

        $response->assertOk()
            ->assertJson([
                'has_request' => true,
                'request_id' => $request->id,
                'template_id' => 9,
                'staff_name' => $staff->full_name,
                'employee_code' => $staff->username,
            ]);
    }

    public function test_device_can_complete_an_enrollment_and_assign_the_template_to_staff(): void
    {
        $staff = $this->createUser('staff', 'staff-complete-enroll@example.com', 'staffcompleteenroll');
        $device = $this->createDevice('ESP32-ENROLL-03', str_repeat('e', 64));

        $request = DeviceEnrollmentRequest::create([
            'device_id' => $device->id,
            'user_id' => $staff->id,
            'template_id' => 11,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            ...$this->signedDeviceHeaders($device, 'POST', '/api/iot/device/enrollment/status', [
                'request_id' => $request->id,
                'status' => 'completed',
            ]),
        ])->postJson('/api/iot/device/enrollment/status', [
            'request_id' => $request->id,
            'status' => 'completed',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame(11, $staff->fresh()->fingerprint_template_id);
        $this->assertSame('completed', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->completed_at);
    }

    public function test_device_can_punch_attendance_using_a_fingerprint_template_id(): void
    {
        $staff = $this->createUser('staff', 'staff-template@example.com', 'stafftemplate', 14);
        $device = $this->createDevice('ESP32-ENROLL-04', str_repeat('f', 64));

        $response = $this->withHeaders([
            ...$this->signedDeviceHeaders($device, 'POST', '/api/iot/attendance/punch', [
                'template_id' => 14,
                'punch_type' => 'auto',
                'timestamp' => '2026-03-31 07:45:00',
            ]),
        ])->postJson('/api/iot/attendance/punch', [
            'template_id' => 14,
            'punch_type' => 'auto',
            'timestamp' => '2026-03-31 07:45:00',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'punch_type' => 'in',
                'staff_name' => $staff->full_name,
            ]);

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $staff->id,
            'device_id' => $device->id,
            'punch_type' => 'in',
        ]);

        $this->assertSame(
            'in',
            AttendanceLog::where('user_id', $staff->id)->value('punch_type')
        );
    }

    private function createUser(string $role, string $email, string $username, ?int $fingerprintTemplateId = null): User
    {
        $user = User::create([
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => $role,
            'fingerprint_template_id' => $fingerprintTemplateId,
            'password' => Hash::make('password123'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function createDevice(string $serial = 'ESP32-ENROLL-01', string $token = ''): Device
    {
        return Device::create([
            'name' => 'Enrollment Device',
            'serial_number' => $serial,
            'api_token' => $token ?: str_repeat('c', 64),
            'location' => 'Front Desk',
            'is_active' => true,
        ]);
    }

    private function signedDeviceHeaders(Device $device, string $method, string $path, array $payload = []): array
    {
        $timestamp = (string) now()->timestamp;
        $nonce = Str::lower(Str::random(24));
        $requestBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $bodyHash = hash('sha256', $requestBody ?: '');
        $canonicalString = implode("\n", [
            strtoupper($method),
            ltrim($path, '/'),
            $timestamp,
            $nonce,
            $bodyHash,
        ]);

        return [
            'X-Device-Token' => $device->api_token,
            'X-IoT-Timestamp' => $timestamp,
            'X-IoT-Nonce' => $nonce,
            'X-IoT-Signature' => hash_hmac('sha256', $canonicalString, $device->api_token),
        ];
    }
}
