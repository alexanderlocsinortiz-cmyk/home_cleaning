<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Tests\TestCase;

class OfflineCommunicationTest extends TestCase
{
    public function test_public_footer_exposes_configured_call_sms_and_in_person_fallbacks(): void
    {
        SiteSetting::current()->update([
            'contact_phone' => '+63 917 123 4567',
            'contact_address' => 'Valencia City Office',
            'office_hours' => 'Monday - Saturday, 8:00 AM - 5:00 PM',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('tel:+639171234567', false);
        $response->assertSee('Call or SMS during office hours', false);
        $response->assertSee('Visit the office during the hours listed above', false);
        $response->assertSee('Keep your booking reference when contacting us', false);
    }

    public function test_public_footer_does_not_show_a_fake_phone_number_when_unconfigured(): void
    {
        SiteSetting::current()->update(['contact_phone' => null]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Call/SMS fallback is not configured yet', false);
        $response->assertDontSee('tel:', false);
    }
}
