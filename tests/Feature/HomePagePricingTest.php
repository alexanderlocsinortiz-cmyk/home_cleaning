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
        Service::updateOrCreate(['slug' => 'basic-clean'], [
            'name' => 'Basic Clean',
            'description' => 'Routine cleaning',
            'price' => 35,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'deep'], [
            'name' => 'Deep Clean',
            'description' => 'Detailed cleaning',
            'price' => 95,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'moveinout'], [
            'name' => 'Move-in/Move-out Clean',
            'description' => 'Full property cleaning',
            'price' => 80,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'postconstruction'], [
            'name' => 'Post Construction Cleaning',
            'description' => 'Detailed post-construction cleanup',
            'price' => 105,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'commercial'], [
            'name' => 'Office and Commercial Cleaning',
            'description' => 'Commercial workspace cleaning',
            'price' => 1600,
            'is_active' => true,
        ]);

        Service::updateOrCreate(['slug' => 'weeklymaintenance'], [
            'name' => 'General/Regular Cleaning',
            'description' => 'General or regular cleaning session',
            'price' => 500,
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Choose the clean that fits your home', false);
        $response->assertSee('Post Construction Cleaning', false);
        $response->assertSee('Instant Quote', false);
        $response->assertSee('Estimate your cleaning total in seconds', false);
        $response->assertSee('Build the price live while you compare packages, home size, and add-ons.', false);
        $response->assertSee('aria-label="Toggle website navigation"', false);
        $response->assertSee('Continue with Estimate of', false);
        $response->assertSee('Calculation:', false);
        $response->assertSee('Basic', false);
        $response->assertSee('Post-Con', false);
        $response->assertSee('Move-in', false);
        $response->assertSee('&#8369;35/sqm', false);
        $response->assertSee('&#8369;80 per sqm', false);
        $response->assertSee('30 sqm', false);
        $response->assertSee('&#8369;95 per sqm', false);
        $response->assertSee('&#8369;105 per sqm', false);
        $response->assertSee('max="200"', false);
        $response->assertSee('quote_source', false);
        $response->assertSee('Get your instant quote', false);
        $response->assertSee('Trusted home cleaning for Valencia City.', false);
        $response->assertSee('Unit-priced extras use one unit in this preview', false);
    }

    public function test_home_page_instant_quote_uses_the_active_database_rate_and_canonical_service_slug(): void
    {
        Service::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Basic Clean',
            'description' => 'Routine cleaning',
            'price' => 47.25,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $html = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('"slug":"basic"', $html);
        $this->assertStringContainsString('"area_rate":47.25', $html);
        $this->assertStringContainsString('&#8369;1,417.50', $html);
        $this->assertStringContainsString('minimumFractionDigits: 2', $html);
        $this->assertStringNotContainsString('"slug":"basic-clean"', $html);
    }
}
