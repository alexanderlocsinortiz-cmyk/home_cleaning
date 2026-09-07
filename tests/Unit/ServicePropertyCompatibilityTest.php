<?php

namespace Tests\Unit;

use App\Models\Service;
use PHPUnit\Framework\TestCase;

class ServicePropertyCompatibilityTest extends TestCase
{
    public function test_office_services_only_match_office_properties(): void
    {
        $this->assertTrue(Service::supportsPropertyType('office-basic', 'office'));
        $this->assertTrue(Service::supportsPropertyType('commercial', 'office'));
        $this->assertTrue(Service::supportsPropertyType('office-deep', 'office'));
        $this->assertFalse(Service::supportsPropertyType('office-basic', 'house'));
    }

    public function test_residential_services_do_not_match_office_properties(): void
    {
        foreach (['basic', 'deep', 'moveinout', 'postconstruction', 'weeklymaintenance'] as $slug) {
            $this->assertTrue(Service::supportsPropertyType($slug, 'house'));
            $this->assertTrue(Service::supportsPropertyType($slug, 'apartment'));
            $this->assertTrue(Service::supportsPropertyType($slug, 'boarding_house'));
            $this->assertFalse(Service::supportsPropertyType($slug, 'office'));
        }
    }
}
