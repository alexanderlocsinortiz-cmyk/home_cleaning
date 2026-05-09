<?php

namespace Tests\Unit;

use App\Models\AttendanceLog;
use App\Models\Staff;
use App\Models\User;
use Tests\TestCase;

class AttendanceLogTest extends TestCase
{
    private Staff $staff;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staffUser = User::factory()->create(['role' => 'staff']);
        $this->staff = Staff::factory()->create(['user_id' => $this->staffUser->id]);
    }

    public function test_attendance_log_can_be_created()
    {
        $log = AttendanceLog::factory()->create([
            'staff_id' => $this->staff->id,
            'punch_type' => 'in',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'id' => $log->id,
            'staff_id' => $this->staff->id,
        ]);
    }

    public function test_attendance_log_has_punch_type()
    {
        $log = AttendanceLog::factory()->create(['punch_type' => 'in']);
        $this->assertEquals('in', $log->punch_type);
    }

    public function test_attendance_log_stores_timestamp()
    {
        $log = AttendanceLog::factory()->create();
        $this->assertNotNull($log->punched_at);
    }

    public function test_attendance_log_belongs_to_staff()
    {
        $log = AttendanceLog::factory()->create(['staff_id' => $this->staff->id]);
        $this->assertTrue($log->staff->is($this->staff));
    }

    public function test_attendance_log_can_record_fingerprint_template()
    {
        $log = AttendanceLog::factory()->create([
            'fingerprint_template_id' => 'device_template_123',
        ]);

        $this->assertEquals('device_template_123', $log->fingerprint_template_id);
    }

    public function test_daily_attendance_calculation()
    {
        $today = now()->toDateString();

        AttendanceLog::factory()->create([
            'staff_id' => $this->staff->id,
            'punch_type' => 'in',
            'punched_at' => now()->setTime(8, 0),
        ]);

        AttendanceLog::factory()->create([
            'staff_id' => $this->staff->id,
            'punch_type' => 'out',
            'punched_at' => now()->setTime(17, 0),
        ]);

        $logs = AttendanceLog::where('staff_id', $this->staff->id)
            ->whereDate('punched_at', $today)
            ->get();

        $this->assertCount(2, $logs);
    }
}
