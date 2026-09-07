<?php

// Customer booking service areas are fixed by project scope.
// Do not add, remove, or rename barangays here unless the approved booking coverage changes.
$serviceAreas = [
    ['name' => 'Poblacion', 'lat' => 7.9073, 'lng' => 125.0920, 'type' => 'service_center', 'services' => ['Deep Cleaning', 'Basic Cleaning']],
    ['name' => 'Bagontaas', 'lat' => 7.9477, 'lng' => 125.1009, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Banlag', 'lat' => 7.8340, 'lng' => 125.1684, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Barobo', 'lat' => 7.9061, 'lng' => 125.0325, 'type' => 'commercial', 'services' => ['Office Cleaning']],
    ['name' => 'Batangan', 'lat' => 7.9058, 'lng' => 125.1172, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Catumbalon', 'lat' => 7.8494, 'lng' => 125.1013, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Colonia', 'lat' => 7.9903, 'lng' => 125.1163, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Concepcion', 'lat' => 7.8832, 'lng' => 125.2193, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Dagat-Kidavao', 'lat' => 7.8265, 'lng' => 125.1305, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Guinoyuran', 'lat' => 7.8952, 'lng' => 125.0018, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Kahapunan', 'lat' => 7.9410, 'lng' => 125.1571, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Laligan', 'lat' => 7.8864, 'lng' => 125.1806, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Lilingayon', 'lat' => 7.9937, 'lng' => 124.9694, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Lourdes', 'lat' => 7.8961, 'lng' => 124.9502, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Lumbayao', 'lat' => 7.9464, 'lng' => 125.2458, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Lumbo', 'lat' => 7.8971, 'lng' => 125.0818, 'type' => 'commercial', 'services' => ['Office Cleaning']],
    ['name' => 'Lurogan', 'lat' => 7.9670, 'lng' => 125.0460, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Maapag', 'lat' => 7.8625, 'lng' => 125.1090, 'type' => 'commercial', 'services' => ['Office Cleaning']],
    ['name' => 'Mabuhay', 'lat' => 7.8440, 'lng' => 125.1354, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Mailag', 'lat' => 7.9722, 'lng' => 125.1358, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Mount Nebo', 'lat' => 7.9728, 'lng' => 124.9866, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Nabago', 'lat' => 7.9649, 'lng' => 125.1559, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Pinatilan', 'lat' => 7.8881, 'lng' => 125.1038, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'San Carlos', 'lat' => 7.9618, 'lng' => 125.0729, 'type' => 'commercial', 'services' => ['Office Cleaning']],
    ['name' => 'San Isidro', 'lat' => 7.9590, 'lng' => 125.1934, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Sinabuagan', 'lat' => 7.9444, 'lng' => 125.2151, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Sinayawan', 'lat' => 7.8717, 'lng' => 125.1419, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Sugod', 'lat' => 7.9432, 'lng' => 125.1189, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Tongantongan', 'lat' => 7.9100, 'lng' => 125.1635, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Tugaya', 'lat' => 7.9159, 'lng' => 125.0163, 'type' => 'residential', 'services' => ['Basic Cleaning']],
    ['name' => 'Vintar', 'lat' => 7.9463, 'lng' => 125.1747, 'type' => 'residential', 'services' => ['Basic Cleaning']],
];

$bukidnonCoverageAreas = [
    'Baungon' => 'Baungon',
    'Cabanglasan' => 'Cabanglasan',
    'Damulog' => 'Damulog',
    'Dangcagan' => 'Dangcagan',
    'Don Carlos' => 'Don Carlos',
    'Impasugong' => 'Impasugong',
    'Kadingilan' => 'Kadingilan',
    'Kalilangan' => 'Kalilangan',
    'Kibawe' => 'Kibawe',
    'Kitaotao' => 'Kitaotao',
    'Lantapan' => 'Lantapan',
    'Libona' => 'Libona',
    'Malaybalay City' => 'Malaybalay City',
    'Malitbog' => 'Malitbog',
    'Manolo Fortich' => 'Manolo Fortich',
    'Maramag' => 'Maramag',
    'Pangantucan' => 'Pangantucan',
    'Quezon' => 'Quezon',
    'San Fernando' => 'San Fernando',
    'Sumilao' => 'Sumilao',
    'Talakag' => 'Talakag',
    'Valencia City' => 'Valencia City',
];

$barangays = [];
$barangayCenters = [];

foreach ($serviceAreas as $serviceArea) {
    $barangays[$serviceArea['name']] = $serviceArea['name'];
    $barangayCenters[$serviceArea['name']] = [
        'lat' => $serviceArea['lat'],
        'lng' => $serviceArea['lng'],
    ];
}

return [
    'attendance_timezone' => env('ATTENDANCE_TIMEZONE', 'Asia/Manila'),
    'marketing' => [
        'show_early_launch_banner' => env('SHOW_EARLY_LAUNCH_BANNER', false),
        'business_start_year' => (int) env('BUSINESS_START_YEAR', 2024),
    ],
    'iot' => [
        'require_signed_requests' => env('IOT_REQUIRE_SIGNED_REQUESTS', true),
        'max_clock_skew_seconds' => (int) env('IOT_MAX_CLOCK_SKEW_SECONDS', 300),
    ],
    'proof_uploads' => [
        'max_request_kb' => (int) env('PROOF_UPLOAD_MAX_REQUEST_KB', 32768),
        'max_video_kb' => (int) env('PROOF_UPLOAD_MAX_VIDEO_KB', 10240),
    ],
    'privacy' => [
        // Rejected applications remain available for a limited review/dispute window,
        // then identity data is deleted while an anonymized audit record remains.
        'rejected_application_retention_days' => max(1, (int) env('CLEANER_REJECTED_RETENTION_DAYS', 180)),
        'security_event_retention_days' => max(30, (int) env('SECURITY_EVENT_RETENTION_DAYS', 365)),
    ],
    'marketplace' => [
        'default_commission_rate' => (float) env('MARKETPLACE_DEFAULT_COMMISSION_RATE', 0.15),
    ],
    // Planning capacities are conservative starting values for one cleaner
    // during one visit. They must be recalibrated with CleanFlow time studies.
    'staffing' => [
        'default_capacity_sqm_per_cleaner' => 40,
        'max_cleaners_per_booking' => 20,
        'capacity_sqm_per_cleaner' => [
            'basic' => 40,
            'deep' => 25,
            'moveinout' => 40,
            'postconstruction' => 25,
            'office-basic' => 60,
            'commercial' => 50,
            'office-deep' => 35,
            'weeklymaintenance' => 40,
        ],
    ],
    'barangays' => $barangays,
    'bukidnon_coverage_areas' => $bukidnonCoverageAreas,
    'barangay_centers' => $barangayCenters,
    'service_areas' => $serviceAreas,
    'map' => [
        'center' => ['lat' => 7.9047, 'lng' => 125.0940],
        'zoom' => 12,
        'minZoom' => 10,
        'maxZoom' => 17,
        'maxBounds' => [
            [7.6, 124.8],
            [8.2, 125.4],
        ],
    ],
];
