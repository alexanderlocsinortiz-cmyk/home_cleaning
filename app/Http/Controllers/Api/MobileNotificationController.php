<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Notification $notification): array => $this->payload($notification))
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->where('read_at', null)->count(),
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorizeNotification($request, $notification);
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => $this->payload($notification->fresh()),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notifications marked as read.',
            'updated_count' => $updated,
        ]);
    }

    private function authorizeNotification(Request $request, Notification $notification): void
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(response()->json([
                'message' => 'This notification does not belong to your account.',
            ], 403));
        }
    }

    private function payload(Notification $notification): array
    {
        return [
            'booking_id' => $notification->booking_id,
            'created_at' => $notification->created_at?->toISOString(),
            'id' => $notification->id,
            'link' => $notification->link,
            'message' => $notification->message,
            'read_at' => $notification->read_at?->toISOString(),
            'title' => $notification->title ?: $notification->subject ?: 'CleanFlow update',
            'type' => in_array($notification->type, ['success', 'warning', 'info'], true)
                ? $notification->type
                : 'info',
        ];
    }
}
