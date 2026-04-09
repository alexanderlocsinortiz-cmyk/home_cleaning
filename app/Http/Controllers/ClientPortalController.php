<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class ClientPortalController extends Controller
{
    public function dashboard()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with(['staff', 'service'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.dashboard', compact('bookings'));
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

        $stats = [
            'barangays' => count($barangays),
            'customers' => \App\Models\User::where('role', 'client')->count(),
            'staff' => \App\Models\User::where('role', 'staff')->count(),
            'satisfaction' => (function () {
                $avg = \App\Models\Rating::avg('stars');

                return $avg ? round(($avg / 5) * 100) : 98;
            })(),
        ];

        return view('client.service-areas', compact('barangays', 'stats'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'phone'      => 'required|string|max:15',
            'street'     => 'required|string|max:100',
            'barangay'   => 'required|string',
            'zip_code'   => 'required|string|max:10',
        ]);

        $user->update($request->only(['first_name','last_name','phone','street','barangay','zip_code']));

        return redirect()->route('client.profile')->with('success', 'Profile updated successfully.');
    }
}
