<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MobileStaffPerformanceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $staff = $request->user();

        if ($staff->role !== 'staff') {
            return response()->json([
                'message' => 'Only staff accounts can view mobile performance.',
            ], 403);
        }

        $bookings = Booking::with(['rating', 'staffAssignments:id,booking_id,staff_id'])
            ->assignedToStaff($staff->id)
            ->get();

        $completedBookings = $bookings->where('status', 'completed');
        $ratings = $this->ratingsForStaff($completedBookings, (int) $staff->id);
        $allBookings = Booking::with(['rating', 'staffAssignments:id,booking_id,staff_id'])->get();
        $leaderboard = User::query()
            ->where('role', 'staff')
            ->get()
            ->map(fn (User $staffMember): array => $this->staffSummary(
                $staffMember,
                $allBookings->filter(fn (Booking $booking): bool => $booking->isAssignedToStaff((int) $staffMember->id))
            ))
            ->sort(function (array $left, array $right): int {
                return [$right['average_rating'] ?? 0, $right['completed_count'], $right['total_bookings'], $left['name']]
                    <=> [$left['average_rating'] ?? 0, $left['completed_count'], $left['total_bookings'], $right['name']];
            })
            ->values();

        $rank = $leaderboard->search(fn (array $row): bool => $row['staff_id'] === $staff->id) + 1;
        $summary = $this->staffSummary($staff, $bookings);

        return response()->json([
            'performance' => [
                'staff_id' => $staff->id,
                'total_bookings' => $summary['total_bookings'],
                'completed_count' => $summary['completed_count'],
                'completion_rate' => $summary['completion_rate'],
                'total_earnings' => $summary['total_earnings'],
                'average_rating' => $ratings->isNotEmpty() ? round((float) $ratings->avg('stars'), 1) : null,
                'total_reviews' => $ratings->count(),
                'star_breakdown' => collect(range(5, 1))
                    ->mapWithKeys(fn (int $stars): array => [$stars => $ratings->where('stars', $stars)->count()])
                    ->all(),
                'rank' => $rank,
                'total_staff' => $leaderboard->count(),
                'leaderboard' => $leaderboard->take(5)->map(fn (array $row): array => [
                    'rank' => $leaderboard->search(fn (array $entry): bool => $entry['staff_id'] === $row['staff_id']) + 1,
                    'staff_id' => $row['staff_id'],
                    'name' => $row['name'],
                    'completed_count' => $row['completed_count'],
                    'average_rating' => $row['average_rating'],
                ])->values(),
            ],
        ]);
    }

    private function staffSummary(User $staff, $bookings = null): array
    {
        $bookings ??= $staff->assignedBookings;
        $completed = $bookings->where('status', 'completed');
        $ratings = $this->ratingsForStaff($completed, (int) $staff->id);

        return [
            'staff_id' => $staff->id,
            'name' => $staff->full_name ?: ($staff->username ?: $staff->email),
            'total_bookings' => $bookings->count(),
            'completed_count' => $completed->count(),
            'completion_rate' => $bookings->count() > 0
                ? round(($completed->count() / $bookings->count()) * 100, 1)
                : 0.0,
            // Booking price belongs to the legacy primary cleaner until a split policy is defined.
            'total_earnings' => round((float) $completed
                ->where('staff_id', $staff->id)
                ->sum('price'), 2),
            'average_rating' => $ratings->isNotEmpty() ? round((float) $ratings->avg('stars'), 1) : null,
        ];
    }

    private function ratingsForStaff(Collection $bookings, int $staffId): Collection
    {
        return $bookings
            ->filter(fn (Booking $booking): bool => (int) $booking->staff_id === $staffId)
            ->pluck('rating')
            ->filter()
            ->values();
    }
}
