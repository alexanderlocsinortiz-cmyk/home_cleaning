<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientPortalController extends Controller
{
    public function dashboard()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with(['staff', 'service', 'preferredStaff', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();

        $dashboardTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');

        return view('client.dashboard', compact('bookings', 'notifications', 'dashboardTimezone'));
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
        $minimumBirthDate = now(config('cleanflow.attendance_timezone', config('app.timezone')))
            ->subYears(18)
            ->toDateString();

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'date_of_birth' => 'required|date_format:Y-m-d|before_or_equal:'.$minimumBirthDate,
            'street' => 'required|string|max:255',
            'barangay' => ['required', Rule::in(array_keys(config('cleanflow.barangays', [])))],
        ], [
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to book a cleaning service.',
            'date_of_birth.date_format' => 'Date of birth must use YYYY-MM-DD format.',
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
        ]);

        $user->update($request->only(['first_name', 'last_name', 'phone', 'date_of_birth', 'street', 'barangay']));

        return redirect()->route('client.profile')->with('success', 'Profile updated successfully.');
    }
}
