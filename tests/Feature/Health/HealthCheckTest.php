<?php

namespace Tests\Feature\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Health checks for load balancers and deployment verification
 * (docs/13-deployment.md).
 */
class HealthCheckTest extends TestCase
{
    #[Test]
    public function it_is_reachable_without_authentication(): void
    {
        // A load balancer cannot log in.
        $this->getJson('/health')->assertOk();
    }

    #[Test]
    public function a_healthy_application_reports_ok(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.application', 'ok')
            ->assertJsonPath('checks.database', 'ok');
    }

    #[Test]
    public function a_failing_dependency_makes_the_whole_check_fail(): void
    {
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new RuntimeException('could not connect'));

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.database', 'failed');
    }

    #[Test]
    public function it_never_leaks_why_a_dependency_failed(): void
    {
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new RuntimeException(
                'SQLSTATE[08006] connection to server at "10.0.4.11", port 5432 failed: password authentication failed for user "erp"',
            ));

        $response = $this->getJson('/health');

        // The endpoint is unauthenticated, so the body is public. It says what
        // is broken, never where or why.
        $response->assertDontSee('10.0.4.11');
        $response->assertDontSee('password');
        $response->assertDontSee('SQLSTATE');
        $response->assertDontSee('erp');
    }

    #[Test]
    public function it_does_not_call_the_esb(): void
    {
        Http::fake();

        // Deliberate. A load balancer polling every few seconds would hammer
        // the ESB, and a third party being down would pull our own instances
        // out of rotation. ESB reachability belongs on the connections screen,
        // where a person asks for it.
        $this->getJson('/health');

        Http::assertNothingSent();
    }

    #[Test]
    public function it_reports_the_queue_backend_separately_from_liveness(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonStructure(['status', 'checks' => ['application', 'database', 'cache', 'queue']]);
    }
}
