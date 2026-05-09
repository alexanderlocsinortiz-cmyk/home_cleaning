<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function dashboard()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with(['staff', 'service', 'preferredStaff'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        return view('client.dashboard', compact('bookings', 'notifications'));
    }

    public function profile()
    {
        $user = auth()->user();

        return view('client.profile', compact('user'));
    }

    public function editProfile()
    {
        $user = auth()->user();
        $barangays = array_keys(config('cleanflow.barangays'));

        return view('client.profile-edit', compact('user', 'barangays'));
    }

    public function serviceAreas()
    {
        $barangays = config('cleanflow.service_areas', []);
        $stats = $this->serviceAreaStats();

        return view('client.service-areas', compact('barangays', 'stats'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $minimumBirthDate = now()->subYears(18)->toDateString();

        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date|before_or_equal:'.$minimumBirthDate,
            'street' => 'required|string|max:100',
            'barangay' => 'required|string',
            'zip_code' => 'required|string|max:10',
        ], [
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to book a cleaning service.',
        ]);

        $user->update($request->only(['first_name', 'last_name', 'phone', 'date_of_birth', 'street', 'barangay', 'zip_code']));

        return redirect()->route('client.profile')->with('success', 'Profile updated successfully.');
    }
}
