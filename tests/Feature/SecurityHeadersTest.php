<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_application_responses_include_security_headers(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self "https://*.daily.co"), geolocation=(self), microphone=(self "https://*.daily.co")');
    }

    public function test_https_responses_include_hsts(): void
    {
        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->get('/up')
                ->assertOk()
                ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }
}
