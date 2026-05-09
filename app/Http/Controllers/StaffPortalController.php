<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffPortalController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        // ✅ Eager load to avoid N+1 queries
        $assignedBookings = Booking::with(['user', 'rating', 'service'])
            ->select([
                'id',
                'user_id',
                'staff_id',
                'service_id',
                'status',
                'scheduled_date',
                'scheduled_time',
                'price',
            ])
            ->where('staff_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // ✅ Use aggregates instead of multiple queries
        $stats = Booking::where('staff_id', $user->id)
            ->selectRaw("
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
                SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as total_earnings
            ")
            ->first();

        $totalBookings = $stats->total_bookings ?? 0;
        $completedBookings = $stats->completed_bookings ?? 0;
        $inProgress = $stats->in_progress ?? 0;
        $confirmedBookings = $stats->confirmed_bookings ?? 0;
        $totalEarnings = $stats->total_earnings ?? 0;

        // ✅ Use withAvg to get rating in one query
        $ratingStats = Booking::where('staff_id', $user->id)
            ->withAvg('rating', 'stars')
            ->withCount('rating')
            ->first();

        $avgRating = $ratingStats?->rating_avg_stars 
            ? round($ratingStats->rating_avg_stars, 1) 
            : null;
        $totalRatings = $ratingStats?->rating_count ?? 0;

        return view('staff.welcome', compact(
            'user', 'assignedBookings', 'totalBookings',
            'completedBookings', 'inProgress', 'confirmedBookings',
            'avgRating', 'totalRatings', 'totalEarnings'
        ));
    }

    public function serviceAreas()
    {
        $barangays = config('cleanflow.service_areas', []);
        $stats = $this->serviceAreaStats();

        return view('staff.service-areas', compact('barangays', 'stats'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,completed'],
        ]);

        $booking = Booking::where('id', $id)
            ->where('staff_id', Auth::id())
            ->firstOrFail();

        if (! $booking->canBeUpdatedByStaffTo($validated['status'])) {
            return back()->withErrors([
                'status' => $validated['status'] === 'completed'
                    ? 'Only bookings that are already in progress can be marked as completed.'
                    : 'Only confirmed bookings can be started.',
            ]);
        }

        $status = $validated['status'];

        if ($status === 'in_progress') {
            $request->validate([
                'before_photos' => ['required', 'array', 'min:1', 'max:4'],
                'before_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
        }

        if ($status === 'completed') {
            $request->validate([
                'after_photos' => ['required', 'array', 'min:1', 'max:4'],
                'after_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'completion_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-msvideo', 'max:20480'],
            ]);
        }

        if ($status === 'completed' && ! $booking->hasBeforeServiceProof()) {
            return back()->withErrors([
                'status' => 'Upload at least one before-service photo before completing this booking.',
            ]);
        }

        $actor = Auth::user();

        DB::transaction(function () use ($booking, $request, $actor, $status) {
            if ($status === 'in_progress') {
                $beforePhotoCount = $this->storeProofBatch(
                    $booking,
                    $request->file('before_photos', []),
                    'before',
                    'image',
                    $actor->id
                );

                $booking->update(['status' => 'in_progress']);

                $booking->logActivity(
                    $actor,
                    'proof_uploaded',
                    'Uploaded '.$beforePhotoCount.' before-service photo'.($beforePhotoCount === 1 ? '' : 's').'.',
                    [
                        'stage' => 'before',
                        'media_type' => 'image',
                        'count' => $beforePhotoCount,
                    ]
                );

                $booking->logActivity(
                    $actor,
                    'status_updated',
                    'Marked the booking as in progress.',
                    [
                        'from_status' => 'confirmed',
                        'to_status' => 'in_progress',
                    ]
                );

                $this->createClientProofNotification($booking, 'service_started', [
                    'before_photo_count' => $beforePhotoCount,
                ]);

                return;
            }

            $afterPhotoCount = $this->storeProofBatch(
                $booking,
                $request->file('after_photos', []),
                'after',
                'image',
                $actor->id
            );

            $videoUploaded = false;
            if ($request->hasFile('completion_video')) {
                $videoPath = $request->file('completion_video')->store('booking-proofs/after', 'public');

                $booking->serviceProofs()->create([
                    'uploaded_by' => $actor->id,
                    'stage' => 'after',
                    'media_type' => 'video',
                    'file_path' => $videoPath,
                    'original_name' => $request->file('completion_video')->getClientOriginalName(),
                ]);

                $videoUploaded = true;
            }

            $updates = ['status' => 'completed'];

            if ($booking->payment_method === 'on_site_cash' && $booking->payment_status !== 'paid') {
                $updates['payment_status'] = 'paid';
                $updates['payment_reference'] = $booking->payment_reference ?: Booking::generatePaymentReference('on_site_cash');
                $updates['paid_at'] = now();
            }

            $booking->update($updates);

            $booking->logActivity(
                $actor,
                'proof_uploaded',
                'Uploaded '.$afterPhotoCount.' after-service photo'.($afterPhotoCount === 1 ? '' : 's').'.',
                [
                    'stage' => 'after',
                    'media_type' => 'image',
                    'count' => $afterPhotoCount,
                ]
            );

            if ($videoUploaded) {
                $booking->logActivity(
                    $actor,
                    'proof_uploaded',
                    'Uploaded a completion video.',
                    [
                        'stage' => 'after',
                        'media_type' => 'video',
                        'count' => 1,
                    ]
                );
            }

            $booking->logActivity(
                $actor,
                'status_updated',
                'Marked the booking as completed.',
                [
                    'from_status' => 'in_progress',
                    'to_status' => 'completed',
                    'payment_status' => $booking->payment_status,
                ]
            );

            $this->createClientProofNotification($booking, 'service_completed', [
                'after_photo_count' => $afterPhotoCount,
                'video_uploaded' => $videoUploaded,
            ]);
        });

        return back()->with('success', $validated['status'] === 'completed'
            ? 'Service marked as completed and proof of service has been uploaded.'
            : 'Service marked as in progress and before-service proof has been uploaded.');
    }

    public function profile()
    {
        $user = Auth::user();
        $barangays = config('cleanflow.barangays');

        return view('staff.profile', compact('user', 'barangays'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'barangay' => ['required', 'in:'.implode(',', array_keys(config('cleanflow.barangays')))],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function bookings(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');

        $query = Booking::with(['user', 'rating', 'service'])
            ->withCount([
                'serviceProofs as before_photo_count' => fn ($proofs) => $proofs
                    ->where('stage', 'before')
                    ->where('media_type', 'image'),
                'serviceProofs as after_photo_count' => fn ($proofs) => $proofs
                    ->where('stage', 'after')
                    ->where('media_type', 'image'),
                'serviceProofs as completion_video_count' => fn ($proofs) => $proofs
                    ->where('stage', 'after')
                    ->where('media_type', 'video'),
            ])
            ->where('staff_id', $user->id)
            ->orderBy('scheduled_date', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(10);

        $counts = [
            'all' => Booking::where('staff_id', $user->id)->count(),
            'confirmed' => Booking::where('staff_id', $user->id)->where('status', 'confirmed')->count(),
            'in_progress' => Booking::where('staff_id', $user->id)->where('status', 'in_progress')->count(),
            'completed' => Booking::where('staff_id', $user->id)->where('status', 'completed')->count(),
            'cancelled' => Booking::where('staff_id', $user->id)->where('status', 'cancelled')->count(),
        ];

        return view('staff.bookings', compact('bookings', 'status', 'counts', 'user'));
    }

    public function performance()
    {
        $user = Auth::user();

        // All completed bookings with ratings
        $completedBookings = Booking::with(['rating', 'service', 'user'])
            ->where('staff_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Rating stats
        $ratings = $completedBookings->pluck('rating')->filter();
        $avgRating = $ratings->count() > 0 ? round($ratings->avg('stars'), 1) : null;
        $totalRatings = $ratings->count();

        // Star breakdown
        $starBreakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $starBreakdown[$i] = $ratings->where('stars', $i)->count();
        }

        // Overall stats
        $totalBookings = Booking::where('staff_id', $user->id)->count();
        $completedCount = $completedBookings->count();
        $completionRate = $totalBookings > 0 ? round(($completedCount / $totalBookings) * 100, 1) : 0;
        $totalEarnings = $completedBookings->sum('price');

        // Ranking among all staff
        $allStaff = User::where('role', 'staff')
            ->with(['assignedBookings.rating'])
            ->get()
            ->map(function ($staff) {
                $completed = $staff->assignedBookings->where('status', 'completed');
                $ratings = $staff->assignedBookings->pluck('rating')->filter();
                $staff->avg_rating = $ratings->count() > 0 ? $ratings->avg('stars') : 0;
                $staff->completed_count = $completed->count();

                return $staff;
            })
            ->sortByDesc('avg_rating')
            ->values();

        $myRank = $allStaff->search(fn ($s) => $s->id === $user->id) + 1;
        $totalStaff = $allStaff->count();

        return view('staff.performance', compact(
            'user', 'completedBookings', 'ratings', 'avgRating',
            'totalRatings', 'starBreakdown', 'totalBookings',
            'completedCount', 'completionRate', 'totalEarnings',
            'myRank', 'totalStaff'
        ));
    }

    public function schedule()
    {
        $user = Auth::user();

        $bookings = Booking::with(['user', 'service'])
            ->where('staff_id', $user->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->whereDate('scheduled_date', '>=', now()->startOfMonth())
            ->whereDate('scheduled_date', '<=', now()->endOfMonth()->addMonth())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // Group bookings by date
        $bookingsByDate = $bookings->groupBy(function ($booking) {
            return \Carbon\Carbon::parse($booking->scheduled_date)->format('Y-m-d');
        });

        $currentMonth = now()->format('Y-m');

        return view('staff.schedule', compact('bookings', 'bookingsByDate', 'currentMonth', 'user'));
    }

    public function notifications()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return view('staff.notifications', compact('notifications', 'unreadCount', 'user'));
    }

    public function markAsRead($id)
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All booking updates have been marked as read.');
    }

    private function storeProofBatch(
        Booking $booking,
        array $files,
        string $stage,
        string $mediaType,
        int $uploadedBy
    ): int {
        foreach ($files as $file) {
            $path = $file->store('booking-proofs/'.$stage, 'public');

            $booking->serviceProofs()->create([
                'uploaded_by' => $uploadedBy,
                'stage' => $stage,
                'media_type' => $mediaType,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return count($files);
    }

    private function createClientProofNotification(Booking $booking, string $event, array $payload = []): void
    {
        $bookingCode = 'CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT);

        if ($event === 'service_started') {
            $this->createNotification([
                'user_id' => $booking->user_id,
                'title' => 'Service started with proof',
                'message' => 'Your cleaner has started booking '.$bookingCode.' and uploaded '.($payload['before_photo_count'] ?? 0).' before-service photo'.(($payload['before_photo_count'] ?? 0) === 1 ? '' : 's').'. You can review them from the booking details page.',
                'type' => 'info',
                'link' => '/bookings/'.$booking->id,
            ]);

            return;
        }

        $message = 'Your cleaner completed booking '.$bookingCode.' and uploaded '.($payload['after_photo_count'] ?? 0).' after-service photo'.(($payload['after_photo_count'] ?? 0) === 1 ? '' : 's');

        if (! empty($payload['video_uploaded'])) {
            $message .= ' plus a completion video';
        }

        $this->createNotification([
            'user_id' => $booking->user_id,
            'title' => 'Service completed with proof',
            'message' => $message.'. You can now review the proof of service and leave feedback whenever you are ready.',
            'type' => 'success',
            'link' => '/bookings/'.$booking->id,
        ]);
    }
}
