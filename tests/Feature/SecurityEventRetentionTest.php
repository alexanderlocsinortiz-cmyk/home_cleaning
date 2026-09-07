<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class SecurityEventRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_event_prune_command_supports_dry_run_and_deletes_expired_events(): void
    {
        $oldEvent = SecurityEvent::create(['event' => 'old_event']);
        DB::table('security_events')->where('id', $oldEvent->id)->update([
            'created_at' => now()->subDays(366),
            'updated_at' => now()->subDays(366),
        ]);
        SecurityEvent::create([
            'event' => 'recent_event',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $this->artisan('security:prune-events', ['--dry-run' => true])
            ->assertExitCode(0);
        $this->assertDatabaseCount('security_events', 2);

        $this->artisan('security:prune-events')
            ->assertExitCode(0);

        $this->assertDatabaseCount('security_events', 1);
        $this->assertDatabaseHas('security_events', ['event' => 'recent_event']);
    }

    public function test_security_event_prune_command_rejects_an_unsafe_retention_period(): void
    {
        $this->artisan('security:prune-events', ['--retention-days' => 29])
            ->assertExitCode(1);
    }

    public function test_security_events_are_append_only(): void
    {
        $event = SecurityEvent::create(['event' => 'immutable_event']);

        try {
            $event->update(['event' => 'changed_event']);
            $this->fail('Security events should not be mutable.');
        } catch (LogicException $exception) {
            $this->assertSame('Security events are append-only.', $exception->getMessage());
        }

        $this->assertDatabaseHas('security_events', ['event' => 'immutable_event']);
    }
}
