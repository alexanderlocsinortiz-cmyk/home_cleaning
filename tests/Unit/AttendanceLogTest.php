<?php

namespace Tests\Unit;

use App\Models\AttendanceLog;
use App\Models\User;
use Tests\TestCase;

class AttendanceLogTest extends TestCase
{
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staffUser = User::factory()->create(['role' => 'staff']);
    }

    public function test_attendance_log_can_be_created()
    {
        $log = AttendanceLog::factory()->create([
            'user_id' => $this->staffUser->id,
            'punch_type' => 'in',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'id' => $log->id,
            'user_id' => $this->staffUser->id,
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
        $this->assertNotNull($log->logged_at);
    }

    public function test_attendance_log_belongs_to_user()
    {
        $log = AttendanceLog::factory()->create(['user_id' => $this->staffUser->id]);
        $this->assertTrue($log->user->is($this->staffUser));
    }

    public function test_daily_attendance_calculation()
    {
        $today = now()->toDateString();

        AttendanceLog::factory()->create([
            'user_id' => $this->staffUser->id,
            'punch_type' => 'in',
            'logged_at' => now()->setTime(8, 0),
        ]);

        AttendanceLog::factory()->create([
            'user_id' => $this->staffUser->id,
            'punch_type' => 'out',
            'logged_at' => now()->setTime(17, 0),
        ]);

        $logs = AttendanceLog::where('user_id', $this->staffUser->id)
            ->whereDate('logged_at', $today)
            ->get();

        $this->assertCount(2, $logs);
    }
}
