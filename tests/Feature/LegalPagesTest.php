<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_page_is_accessible(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertOk();
        $response->assertSee('Terms of Service');
    }

    public function test_privacy_page_is_accessible(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertOk();
        $response->assertSee('Privacy Policy');
    }
}
