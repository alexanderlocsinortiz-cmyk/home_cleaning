<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePagePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_instant_quote_pricing_component(): void
    {
        Service::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Basic Clean',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'deep'], [
            'name' => 'Deep Clean',
            'description' => 'Detailed cleaning',
            'price' => 1200,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'moveinout'], [
            'name' => 'Move-in/Move-out Clean',
            'description' => 'Full property cleaning',
            'price' => 2000,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'postconstruction'], [
            'name' => 'Post Construction Cleaning',
            'description' => 'Detailed post-construction cleanup',
            'price' => 1800,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'commercial'], [
            'name' => 'Office and Commercial Cleaning',
            'description' => 'Commercial workspace cleaning',
            'price' => 1600,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'weeklymaintenance'], [
            'name' => 'Weekly Maintenance Plan',
            'description' => 'Weekly recurring cleaning plan',
            'price' => 900,
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Choose the clean that fits your home', false);
        $response->assertSee('Post Construction Cleaning', false);
        $response->assertSee('Office and Commercial Cleaning', false);
        $response->assertSee('Weekly Maintenance Plan', false);
        $response->assertSee('Instant Quote', false);
        $response->assertSee('Estimate your cleaning total in seconds', false);
        $response->assertSee('Continue with Estimate of', false);
        $response->assertSee('Calculation:', false);
        $response->assertSee('Post-Con', false);
        $response->assertSee('Move-in', false);
        $response->assertSee('30 sqm', false);
        $response->assertSee('max="200"', false);
        $response->assertSee('quote_source', false);
        $response->assertSee('Get your instant quote', false);
        $response->assertSee('Trusted home cleaning for Valencia City.', false);
    }
}
