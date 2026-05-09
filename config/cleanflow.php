<?php

// Service areas are fixed by project scope.
// Do not add, remove, or rename barangays here unless the approved project coverage changes.
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
    'barangays' => $barangays,
    'barangay_centers' => $barangayCenters,
    'service_areas' => $serviceAreas,
    'map' => [
        'center' => ['lat' => 7.9047, 'lng' => 125.0940],
        'zoom' => 12,
        'minZoom' => 11,
        'maxZoom' => 17,
        'maxBounds' => [
            [7.75, 124.95],
            [8.05, 125.25],
        ],
    ],
];
