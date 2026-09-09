<?php

namespace Tests\Feature\Dashboard;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Viewer->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function log(array $attributes = []): IntegrationLog
    {
        return IntegrationLog::query()->create([
            'correlation_id' => (string) Str::uuid(),
            'operation' => $attributes['operation'] ?? 'product.list',
            'family' => $attributes['family'] ?? 'core',
            'environment' => 'staging_int',
            'method' => 'GET',
            'path' => '/product/list',
            'outcome' => $attributes['outcome'] ?? 'success',
            'esb_code' => $attributes['esb_code'] ?? 'EC03100000',
            'safe_error_message' => $attributes['safe_error_message'] ?? null,
            'upstream_status' => 200,
            'duration_ms' => $attributes['duration_ms'] ?? 100,
            'retry_count' => 0,
            'user_id' => null,
            'started_at' => $attributes['created_at'] ?? now(),
            'created_at' => $attributes['created_at'] ?? now(),
        ]);
    }

    #[Test]
    public function it_counts_calls_by_outcome(): void
    {
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'timeout']);
        $this->log(['outcome' => 'validation']);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard')
                ->where('metrics.total', 4)
                ->where('metrics.successful', 2)
                ->where('metrics.failed', 2),
            );
    }

    #[Test]
    public function the_success_rate_is_a_percentage(): void
    {
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'timeout']);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                // JSON cannot distinguish 75.0 from 75, so a whole percentage
                // arrives as an integer. The fractional case below is what
                // actually proves the rounding.
                ->where('metrics.successRate', 75),
            );
    }

    #[Test]
    public function a_fractional_success_rate_is_rounded_to_one_decimal(): void
    {
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'timeout']);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('metrics.successRate', 66.7),
            );
    }

    #[Test]
    public function a_window_with_no_calls_reports_a_null_success_rate(): void
    {
        // Not 0 and not 100. With nothing to measure, either number would be a
        // claim the data does not support -- and a red 0% dashboard on a quiet
        // morning would send someone chasing an outage that is not happening.
        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('metrics.total', 0)
                ->where('metrics.successRate', null),
            );
    }

    #[Test]
    public function it_reports_average_duration(): void
    {
        $this->log(['duration_ms' => 100]);
        $this->log(['duration_ms' => 300]);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('metrics.averageDurationMs', 200),
            );
    }

    #[Test]
    public function only_calls_inside_the_window_are_counted(): void
    {
        $this->log(['created_at' => now()->subHours(2)]);
        $this->log(['created_at' => now()->subDays(3)]);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('metrics.total', 1)
                ->where('metrics.windowHours', 24),
            );
    }

    #[Test]
    public function it_breaks_activity_down_by_outcome(): void
    {
        $this->log(['outcome' => 'timeout']);
        $this->log(['outcome' => 'timeout']);
        $this->log(['outcome' => 'validation']);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('byOutcome.timeout', 2)
                ->where('byOutcome.validation', 1),
            );
    }

    #[Test]
    public function it_breaks_activity_down_by_family(): void
    {
        // Availability is per family, not global -- the families are different
        // hosts and can fail independently (ADR-010).
        $this->log(['family' => 'core', 'outcome' => 'success']);
        $this->log(['family' => 'core', 'outcome' => 'timeout']);
        $this->log(['family' => 'fnb_backend', 'outcome' => 'success']);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('byFamily.core.total', 2)
                ->where('byFamily.core.failed', 1)
                ->where('byFamily.fnb_backend.total', 1)
                ->where('byFamily.fnb_backend.failed', 0),
            );
    }

    #[Test]
    public function it_lists_the_most_recent_failures(): void
    {
        $this->log(['outcome' => 'success']);
        $this->log([
            'outcome' => 'timeout',
            'operation' => 'purchase-order.create',
            'safe_error_message' => 'The ERP service did not respond in time. (reference: abc)',
        ]);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('recentFailures', 1)
                ->where('recentFailures.0.operation', 'purchase-order.create')
                ->where('recentFailures.0.outcome', 'timeout'),
            );
    }

    #[Test]
    public function the_dashboard_never_exposes_the_esb_host(): void
    {
        $this->log(['outcome' => 'unknown', 'safe_error_message' => 'The ERP service could not complete this request. (reference: abc)']);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertDontSee('esb.co.id');
    }

    #[Test]
    public function a_user_without_a_role_still_cannot_see_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function it_reports_the_configured_esb_environment(): void
    {
        config()->set('esb.environment', 'staging_int');

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('esb.environment', 'staging_int'),
            );
    }

    #[Test]
    public function it_never_exposes_the_esb_credential(): void
    {
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'super-secret-api-key');

        $response = $this->actingAs($this->viewer())->get(route('dashboard'));

        // Naming the mechanism is useful for operators; the key itself must
        // never leave the server (docs/10-security.md).
        $response->assertDontSee('super-secret-api-key');
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('esb.authMechanism', 'static_token'),
        );
    }
}
