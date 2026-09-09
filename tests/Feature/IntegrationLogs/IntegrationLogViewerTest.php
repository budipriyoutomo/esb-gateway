<?php

namespace Tests\Feature\IntegrationLogs;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntegrationLogViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function log(array $attributes = []): IntegrationLog
    {
        return IntegrationLog::query()->create([
            'correlation_id' => $attributes['correlation_id'] ?? (string) Str::uuid(),
            'operation' => $attributes['operation'] ?? 'product.list',
            'family' => $attributes['family'] ?? 'core',
            'environment' => $attributes['environment'] ?? 'staging_int',
            'method' => $attributes['method'] ?? 'GET',
            'path' => $attributes['path'] ?? '/product/list',
            'outcome' => $attributes['outcome'] ?? 'success',
            'esb_code' => $attributes['esb_code'] ?? 'EC03100000',
            'safe_error_message' => $attributes['safe_error_message'] ?? null,
            'upstream_status' => $attributes['upstream_status'] ?? 200,
            'duration_ms' => $attributes['duration_ms'] ?? 120,
            'retry_count' => $attributes['retry_count'] ?? 0,
            'user_id' => $attributes['user_id'] ?? null,
            'started_at' => $attributes['started_at'] ?? now(),
            'created_at' => $attributes['created_at'] ?? now(),
        ]);
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('integration-logs.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_user_without_the_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('integration-logs.index'))
            ->assertForbidden();
    }

    #[Test]
    public function a_viewer_can_read_the_integration_log(): void
    {
        // Troubleshooting integration failures is a read-only activity, so
        // Viewer holds integration-logs.view.
        $this->log();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('integration-logs/index')
                ->has('logs.data', 1),
            );
    }

    // --------------------------------------------------------------- listing

    #[Test]
    public function the_newest_entries_come_first(): void
    {
        $this->log(['operation' => 'older', 'created_at' => now()->subHour()]);
        $this->log(['operation' => 'newer', 'created_at' => now()]);

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('logs.data.0.operation', 'newer'),
            );
    }

    #[Test]
    public function results_are_paginated(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->log();
        }

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('logs.data', 25)
                ->where('logs.total', 30),
            );
    }

    // --------------------------------------------------------------- filters

    #[Test]
    public function it_filters_by_correlation_id(): void
    {
        $wanted = $this->log(['correlation_id' => '11111111-1111-1111-1111-111111111111']);
        $this->log();

        // The correlation ID is the whole point of the log: a user reports a
        // reference from an error page and this is how it gets found.
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index', ['correlationId' => $wanted->correlation_id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.correlationId', $wanted->correlation_id),
            );
    }

    #[Test]
    public function it_filters_by_outcome(): void
    {
        $this->log(['outcome' => 'success']);
        $this->log(['outcome' => 'validation']);
        $this->log(['outcome' => 'timeout']);

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index', ['outcome' => 'timeout']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.outcome', 'timeout'),
            );
    }

    #[Test]
    public function it_filters_by_operation_substring(): void
    {
        $this->log(['operation' => 'product.list']);
        $this->log(['operation' => 'supplier.list']);

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index', ['operation' => 'product']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('logs.data', 1));
    }

    #[Test]
    public function it_filters_by_date_range(): void
    {
        $this->log(['operation' => 'old', 'created_at' => now()->subDays(10)]);
        $this->log(['operation' => 'recent', 'created_at' => now()->subDay()]);

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index', [
                'dateFrom' => now()->subDays(3)->toDateString(),
            ]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.operation', 'recent'),
            );
    }

    #[Test]
    public function an_unknown_outcome_filter_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index', ['outcome' => 'banana']))
            ->assertSessionHasErrors('outcome');
    }

    #[Test]
    public function a_malformed_date_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index', ['dateFrom' => 'yesterday']))
            ->assertSessionHasErrors('dateFrom');
    }

    // ------------------------------------------------------------- disclosure

    #[Test]
    public function the_page_never_exposes_the_esb_host(): void
    {
        $this->log(['path' => '/product/list']);

        // Paths are stored without the host precisely so the viewer cannot
        // become a way to discover the ESB (ADR-006).
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index'))
            ->assertDontSee('esb.co.id');
    }

    #[Test]
    public function it_shows_who_triggered_the_call(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);
        $this->log(['user_id' => $operator->id]);

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('logs.data.0.user', $operator->name),
            );
    }

    #[Test]
    public function a_call_with_no_user_shows_as_system(): void
    {
        $this->log(['user_id' => null]);

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('integration-logs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('logs.data.0.user', null),
            );
    }
}
