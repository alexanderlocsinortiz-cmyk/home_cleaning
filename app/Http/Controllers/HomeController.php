<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $services = Service::where('is_active', true)
            ->orderBy('price')
            ->get();
        $pricingConfig = Booking::pricingConfiguration();
        $servicePackages = Service::packageCatalog();

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
        $landingReviews = Rating::with(['client', 'booking.service'])
            ->where('stars', '>=', 4)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->latest()
            ->take(3)
            ->get();
        $reviewStats = [
            'count' => Rating::count(),
            'average' => Rating::avg('stars'),
        ];

        return view('home.index', compact(
            'landingReviews',
            'pricingConfig',
            'reviewStats',
            'servicePackages',
            'serviceBookingCounts',
            'services',
            'stats',
            'topServiceSlug',
        ));
    }
}
