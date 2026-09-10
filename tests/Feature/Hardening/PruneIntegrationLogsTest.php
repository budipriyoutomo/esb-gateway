<?php

namespace Tests\Feature\Hardening;

use App\Audit\AuditAction;
use App\Models\AuditLog;
use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration logs grow with every ESB call, so they need a retention policy
 * (docs/11-logging-monitoring.md).
 *
 * The retention period itself is still a business decision; this makes the
 * mechanism exist and configurable so the decision is a config change.
 */
class PruneIntegrationLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('esb.retention.integration_logs_days', 30);
    }

    private function log(\DateTimeInterface $createdAt): IntegrationLog
    {
        return IntegrationLog::query()->create([
            'correlation_id' => (string) Str::uuid(),
            'operation' => 'product.list',
            'family' => 'core',
            'environment' => 'staging_int',
            'method' => 'GET',
            'path' => '/product/list',
            'outcome' => 'success',
            'esb_code' => 'EC03100000',
            'upstream_status' => 200,
            'duration_ms' => 100,
            'retry_count' => 0,
            'started_at' => $createdAt,
            'created_at' => $createdAt,
        ]);
    }

    #[Test]
    public function it_deletes_entries_older_than_the_retention_window(): void
    {
        $this->log(now()->subDays(31));
        $this->log(now()->subDays(60));

        $this->artisan('integration-logs:prune')->assertSuccessful();

        $this->assertSame(0, IntegrationLog::query()->count());
    }

    #[Test]
    public function it_keeps_entries_inside_the_window(): void
    {
        $this->log(now()->subDays(29));
        $this->log(now());

        $this->artisan('integration-logs:prune');

        $this->assertSame(2, IntegrationLog::query()->count());
    }

    #[Test]
    public function it_reports_how_many_it_removed(): void
    {
        $this->log(now()->subDays(40));
        $this->log(now()->subDays(40));

        $this->artisan('integration-logs:prune')
            ->expectsOutputToContain('2')
            ->assertSuccessful();
    }

    #[Test]
    public function the_retention_window_is_configurable(): void
    {
        config()->set('esb.retention.integration_logs_days', 7);

        $this->log(now()->subDays(10));

        $this->artisan('integration-logs:prune');

        $this->assertSame(0, IntegrationLog::query()->count());
    }

    #[Test]
    public function a_zero_or_missing_retention_disables_pruning(): void
    {
        config()->set('esb.retention.integration_logs_days', 0);

        $this->log(now()->subYears(5));

        // A misconfigured or unset retention must not be read as "delete
        // everything". Retention is still an open business decision, and the
        // safe reading of "unset" is "keep".
        $this->artisan('integration-logs:prune')->assertSuccessful();

        $this->assertSame(1, IntegrationLog::query()->count());
    }

    #[Test]
    public function a_dry_run_reports_without_deleting(): void
    {
        $this->log(now()->subDays(40));

        $this->artisan('integration-logs:prune --dry-run')
            ->expectsOutputToContain('1')
            ->assertSuccessful();

        $this->assertSame(1, IntegrationLog::query()->count());
    }

    #[Test]
    public function it_never_touches_the_audit_log(): void
    {
        AuditLog::query()->create([
            'actor_id' => null,
            'action' => AuditAction::RoleAssigned->value,
            'metadata' => [],
            'created_at' => now()->subYears(3),
        ]);

        $this->log(now()->subDays(40));

        $this->artisan('integration-logs:prune');

        // Audit rows record who changed what, are immutable by design
        // (ADR-012), and their retention is a compliance question rather than
        // a volume one. Pruning them needs its own explicit decision.
        $this->assertSame(1, AuditLog::query()->count());
        $this->assertSame(0, IntegrationLog::query()->count());
    }
}
