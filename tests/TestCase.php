<?php

namespace Tests;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function canonicalService(array $attributes): Service
    {
        $metadata = Service::packageMetadataFor($attributes['slug'] ?? null) ?? [];
        $defaults = [
            'name' => $metadata['name'] ?? Service::displayNameForSlug($attributes['slug'] ?? null),
            'description' => $metadata['default_description'] ?? null,
            'price' => $metadata['recommended_price'] ?? 0,
            'duration_minutes' => $metadata['recommended_duration_minutes'] ?? Service::DEFAULT_DURATION_MINUTES,
            'is_active' => true,
        ];

        return Service::updateOrCreate(
            ['slug' => $attributes['slug']],
            array_merge($defaults, $attributes)
        );
    }
}
