<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $services = Service::where('is_active', true)
            ->orderBy('price')
            ->get();

        $serviceAreas = config('cleanflow.service_areas', []);

        $serviceBookingCounts = Booking::selectRaw('service_type, COUNT(*) as total')
            ->groupBy('service_type')
            ->pluck('total', 'service_type');

        $stats = [
            'barangays' => count($serviceAreas),
            'customers' => User::where('role', 'client')->count(),
            'staff' => User::where('role', 'staff')->count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
        ];

        $topServiceSlug = $serviceBookingCounts->sortDesc()->keys()->first();

        return view('home.index', compact(
            'serviceBookingCounts',
            'services',
            'stats',
            'topServiceSlug',
        ));
    }
}
