<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_read_and_update_own_notifications(): void
    {
        $staff = User::factory()->create([
            'email' => 'mobile-notifications-staff@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'staff',
        ]);
        $otherStaff = User::factory()->create([
            'email' => 'other-mobile-notifications-staff@example.com',
            'role' => 'staff',
        ]);
        $notification = Notification::create([
            'user_id' => $staff->id,
            'title' => 'Booking assigned',
            'message' => 'A new booking was assigned to you.',
            'type' => 'info',
            'link' => '/staff/bookings/10',
        ]);
        Notification::create([
            'user_id' => $otherStaff->id,
            'title' => 'Private update',
            'message' => 'This must not be visible.',
            'type' => 'warning',
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => $staff->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.id', $notification->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('notification.id', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('updated_count', 0);
    }

    public function test_mobile_user_cannot_mark_another_users_notification_as_read(): void
    {
        $staff = User::factory()->create([
            'email' => 'notification-owner@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'staff',
        ]);
        $otherStaff = User::factory()->create(['role' => 'staff']);
        $notification = Notification::create([
            'user_id' => $otherStaff->id,
            'title' => 'Private update',
            'message' => 'Private notification.',
            'type' => 'info',
        ]);
        $token = $this->postJson('/api/mobile/login', [
            'email' => $staff->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/mobile/notifications/{$notification->id}/read")
            ->assertForbidden();
    }
}
