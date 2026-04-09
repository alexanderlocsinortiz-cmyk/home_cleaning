<?php

namespace Tests\Feature;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterAttendanceDeviceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_device_command_creates_a_new_device(): void
    {
        $this->artisan('attendance:register-device', [
            'serial' => 'ESP32-FRONT-01',
            'name' => 'Front Desk Device',
            '--location' => 'Main Office',
        ])->assertExitCode(0);

        $device = Device::where('serial_number', 'ESP32-FRONT-01')->first();

        $this->assertNotNull($device);
        $this->assertSame('Front Desk Device', $device->name);
        $this->assertSame('Main Office', $device->location);
        $this->assertTrue($device->is_active);
        $this->assertSame(64, strlen($device->api_token));
    }

    public function test_register_device_command_can_rotate_an_existing_token(): void
    {
        $device = Device::create([
            'name' => 'Front Desk Device',
            'serial_number' => 'ESP32-FRONT-02',
            'api_token' => str_repeat('x', 64),
            'location' => 'Lobby',
            'is_active' => true,
        ]);

        $oldToken = $device->api_token;

        $this->artisan('attendance:register-device', [
            'serial' => 'ESP32-FRONT-02',
            'name' => 'Front Desk Device',
            '--rotate-token' => true,
        ])->assertExitCode(0);

        $this->assertNotSame($oldToken, $device->fresh()->api_token);
        $this->assertSame(64, strlen($device->fresh()->api_token));
    }
}
