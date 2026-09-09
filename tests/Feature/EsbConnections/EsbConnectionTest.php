<?php

namespace Tests\Feature\EsbConnections;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ESB connections are configuration, not database records (ADR-013).
 *
 * This screen shows what is configured and lets an operator probe it. It never
 * exposes a credential, and it never offers to edit one -- secrets belong in
 * environment/secret management, not in a table
 * (docs/10-security.md).
 */
class EsbConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'super-secret-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('esb-connections.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_user_without_the_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('esb-connections.index'))
            ->assertForbidden();
    }

    #[Test]
    public function a_viewer_can_see_the_configured_connections(): void
    {
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('esb-connections.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('esb-connections/index')
                ->has('connections', 4)
                ->where('environment', 'staging_int'),
            );
    }

    // ------------------------------------------------------------- disclosure

    #[Test]
    public function it_never_exposes_the_credential(): void
    {
        $response = $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('esb-connections.index'));

        // Naming the mechanism helps an operator; the key itself must never
        // leave the server.
        $response->assertDontSee('super-secret-api-key');
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('authMechanism', 'static_token'),
        );
    }

    #[Test]
    public function it_never_exposes_the_login_password(): void
    {
        config()->set('esb.auth.mechanism', 'login');
        config()->set('esb.auth.username', 'gateway-integration');
        config()->set('esb.auth.password', 'not-a-real-password');

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('esb-connections.index'))
            ->assertDontSee('not-a-real-password');
    }

    #[Test]
    public function it_offers_no_way_to_edit_a_connection(): void
    {
        // Deliberate: there is no update route. Base URLs and credentials are
        // configuration (ADR-010, ADR-013), so changing one is a deployment
        // action, not a form submission.
        $this->assertFalse(
            app('router')->has('esb-connections.update'),
            'ESB connections are configuration and must not be editable through the UI.',
        );
    }

    // ------------------------------------------------------------ base URLs

    #[Test]
    public function it_reports_the_base_url_for_each_family(): void
    {
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('esb-connections.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('connections.0.family', 'core')
                ->where('connections.0.baseUrl', 'https://stg7.esb.co.id/core-int')
                ->where('connections.0.configured', true),
            );
    }

    #[Test]
    public function a_family_with_no_documented_host_is_reported_as_unconfigured(): void
    {
        // The vendor documents no staging-int host for the POS family. Saying
        // so is better than showing a plausible-looking URL nobody confirmed.
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('esb-connections.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('connections.3.family', 'pos')
                ->where('connections.3.configured', false)
                ->where('connections.3.baseUrl', null),
            );
    }

    // ------------------------------------------------------------- probing

    #[Test]
    public function a_viewer_may_not_probe_a_connection(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->post(route('esb-connections.test', ['family' => 'core']))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_probe_a_connection(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'EC03100000',
            'message' => 'OK',
            'status' => 'ok',
            'result' => ['page' => 1, 'limit' => 1, 'count' => 0, 'data' => []],
        ])]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('esb-connections.test', ['family' => 'core']))
            ->assertRedirect()
            ->assertSessionHas('probe.reachable', true);
    }

    #[Test]
    public function a_connection_failure_is_reported_as_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('esb-connections.test', ['family' => 'core']))
            ->assertSessionHas('probe.reachable', false)
            ->assertSessionHas('probe.outcome', 'connection');
    }

    #[Test]
    public function an_authorization_failure_still_counts_as_reachable(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'EC03100002',
            'message' => 'you did not have access to this resource',
            'status' => 'fail',
            'result' => null,
        ])]);

        // The ESB answered, so the host is up and the network is fine. What
        // failed is the gateway credential's privileges. Reporting this as
        // "unreachable" would send someone to check firewalls instead of ESB
        // user permissions.
        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('esb-connections.test', ['family' => 'core']))
            ->assertSessionHas('probe.reachable', true)
            ->assertSessionHas('probe.outcome', 'authorization');
    }

    #[Test]
    public function probing_a_family_with_no_documented_probe_is_refused(): void
    {
        Http::fake();

        // The vendor documents no health endpoint. Rather than invent one, the
        // families without a known-safe read say so.
        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('esb-connections.test', ['family' => 'pos']))
            ->assertSessionHas('probe');

        // assertSessionHas() cannot express "present and null" -- Laravel's
        // session has() reports false for a null value -- so read it directly.
        $probe = session('probe');

        $this->assertIsArray($probe);
        $this->assertArrayHasKey('reachable', $probe);
        $this->assertNull($probe['reachable'], 'Unknown is not the same as unreachable.');

        Http::assertNothingSent();
    }

    #[Test]
    public function probing_an_unknown_family_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('esb-connections.test', ['family' => 'nonsense']))
            ->assertNotFound();
    }

    #[Test]
    public function a_probe_is_recorded_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'EC03100000',
            'message' => 'OK',
            'status' => 'ok',
            'result' => [],
        ])]);

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)->post(route('esb-connections.test', ['family' => 'core']));

        $log = IntegrationLog::query()->sole();

        $this->assertSame('connection.probe', $log->operation);
        $this->assertSame('core', $log->family);
        $this->assertSame($operator->id, $log->user_id);
    }

    #[Test]
    public function a_probe_uses_the_smallest_possible_read(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'EC03100000',
            'message' => 'OK',
            'status' => 'ok',
            'result' => [],
        ])]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('esb-connections.test', ['family' => 'core']));

        // A probe must never be a write, and should cost the ESB as little as
        // possible.
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), 'limit=1'));
    }
}
