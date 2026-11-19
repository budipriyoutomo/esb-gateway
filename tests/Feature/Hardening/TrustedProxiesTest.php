<?php

namespace Tests\Feature\Hardening;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Production runs behind a reverse proxy that terminates TLS (Nginx Proxy
 * Manager in front of the app's own nginx, docs/13-deployment.md). The app
 * must believe the proxy's X-Forwarded-* headers, or it sees every request as
 * plain http from the proxy's address: HSTS is never sent, and per-IP rate
 * limits and audit rows all carry the proxy's IP instead of the client's.
 */
class TrustedProxiesTest extends TestCase
{
    private const PROXY_ADDRESS = '172.20.0.5';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__trusted-proxy-probe', fn (Request $request): array => [
            'ip' => $request->ip(),
            'secure' => $request->isSecure(),
        ]);
    }

    #[Test]
    public function tls_terminated_at_the_proxy_is_seen_as_https(): void
    {
        $this->fromProxy(['X-Forwarded-Proto' => 'https'])
            ->getJson('http://gateway.test/__trusted-proxy-probe')
            ->assertJsonPath('secure', true);
    }

    #[Test]
    public function hsts_is_sent_when_tls_terminates_at_the_proxy(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->fromProxy(['X-Forwarded-Proto' => 'https'])
            ->get('http://gateway.test/')
            ->assertHeader('Strict-Transport-Security');
    }

    #[Test]
    public function the_client_ip_comes_from_the_forwarded_header(): void
    {
        $this->fromProxy(['X-Forwarded-For' => '203.0.113.9'])
            ->getJson('http://gateway.test/__trusted-proxy-probe')
            ->assertJsonPath('ip', '203.0.113.9');
    }

    #[Test]
    public function a_forwarded_host_is_not_trusted(): void
    {
        // Nginx Proxy Manager passes a client-sent X-Forwarded-Host through
        // untouched. Trusting it would let a client choose the host that
        // absolute URLs -- password reset links among them -- are built on.
        //
        // X-Forwarded-For has the same weakness (the proxy appends to what the
        // client sent, and Symfony reads the left-most entry); that one is
        // reduced to the right-most entry by docker/nginx/default.conf.
        $this->fromProxy(['X-Forwarded-Host' => 'attacker.test'])
            ->get('http://gateway.test/__trusted-proxy-probe');

        $this->assertSame('gateway.test', request()->getHost());
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function fromProxy(array $headers): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => self::PROXY_ADDRESS])
            ->withHeaders($headers);
    }
}
