<?php

namespace Tests\Feature\Hardening;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Secure headers, per the controls in docs/10-security.md.
 */
class SecurityHeadersTest extends TestCase
{
    #[Test]
    public function responses_carry_the_baseline_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    }

    #[Test]
    public function the_health_endpoint_carries_them_too(): void
    {
        // It is the one unauthenticated JSON endpoint, so it is the most
        // exposed surface in the application.
        $this->getJson('/health')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    #[Test]
    public function hsts_is_not_sent_over_plain_http(): void
    {
        // Sending HSTS over http is meaningless, and sending it from a local
        // dev server would pin the developer's browser to https for localhost
        // -- which then breaks every other local project on that host.
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    }

    #[Test]
    public function hsts_is_sent_over_https_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $response = $this->get('https://gateway.test/');

        $response->assertHeader('Strict-Transport-Security');
        $this->assertStringContainsString(
            'max-age=',
            (string) $response->headers->get('Strict-Transport-Security'),
        );
    }

    #[Test]
    public function it_does_not_advertise_the_server_stack(): void
    {
        $response = $this->get('/');

        // Naming the framework and version tells an attacker which CVEs to try
        // first.
        $response->assertHeaderMissing('X-Powered-By');
        $this->assertNull($response->headers->get('Server'));
    }
}
