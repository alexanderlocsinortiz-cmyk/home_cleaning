<?php

namespace App\Http\Controllers;

use App\Models\Service;

class PublicServiceController extends Controller
{
    public function show(Service $service)
    {
        abort_unless($service->is_active, 404);

        $servicePackage = Service::packageMetadataFor($service->slug) ?? [];
        $scopeDefinition = $service->scopeDefinition();
        $scope = $service->scopeSummary();
        $isOfficeService = in_array($service->slug, Service::OFFICE_SERVICE_SLUGS, true);

        return view('services.show', compact(
            'isOfficeService',
            'scope',
            'scopeDefinition',
            'service',
            'servicePackage',
        ));
    }
}
