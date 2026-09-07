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
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
        $pricingConfig = Booking::pricingConfiguration();
        $servicePackages = Service::packageCatalog();

        $serviceAreas = config('cleanflow.service_areas', []);

        $serviceBookingCounts = Booking::query()
            ->join('services', 'services.id', '=', 'bookings.service_id')
            ->selectRaw('services.slug as service_slug, COUNT(bookings.id) as total')
            ->groupBy('services.slug')
            ->pluck('total', 'service_slug');

        $serviceRatingStats = Rating::query()
            ->join('bookings', 'bookings.id', '=', 'ratings.booking_id')
            ->where('bookings.status', 'completed')
            ->whereNotNull('bookings.service_id')
            ->selectRaw('bookings.service_id, COUNT(ratings.id) as total, AVG(ratings.stars) as average')
            ->groupBy('bookings.service_id')
            ->get()
            ->keyBy('service_id');

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
            'serviceRatingStats',
            'services',
            'stats',
            'topServiceSlug',
        ));
    }
}
