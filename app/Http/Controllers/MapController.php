<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\User;

class MapController extends Controller
{
    public function index()
    {
        $barangays = config('cleanflow.service_areas', []);

        $stats = [
            'barangays' => count($barangays),
            'customers' => User::where('role', 'client')->count(),
            'staff' => User::where('role', 'staff')->count(),
            'satisfaction' => (function () {
                $avg = Rating::avg('stars');

                return $avg ? round(($avg / 5) * 100) : 98;
            })(),
        ];

        return view('map.index', compact('stats', 'barangays'));
    }
}
