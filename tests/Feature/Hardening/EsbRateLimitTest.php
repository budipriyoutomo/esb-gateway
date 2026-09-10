<?php

namespace Tests\Feature\Hardening;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Routes that reach the ESB are rate limited.
 *
 * The vendor does not document its rate limits (docs/05-api-integration.md
 * marks them TBD), so the gateway must not let its own users discover them the
 * hard way. This limit is ours, not the vendor's.
 */
class EsbRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        RateLimiter::clear('esb');

        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.rate_limit.per_minute', 3);
    }

    private function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        return $user;
    }

    private function fakeSuccess(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'EC03100000',
            'message' => 'OK',
            'status' => 'ok',
            'result' => ['page' => 1, 'limit' => 20, 'count' => 0, 'data' => []],
        ])]);
    }

    #[Test]
    public function requests_within_the_limit_are_allowed(): void
    {
        $this->fakeSuccess();
        $operator = $this->operator();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($operator)->get(route('products.index'))->assertOk();
        }
    }

    #[Test]
    public function exceeding_the_limit_is_refused_before_the_esb_is_called(): void
    {
        $this->fakeSuccess();
        $operator = $this->operator();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($operator)->get(route('products.index'));
        }

        $this->actingAs($operator)
            ->get(route('products.index'))
            ->assertStatus(429);

        // The point of the limit is to protect the ESB, so the refused request
        // must not reach it.
        Http::assertSentCount(3);
    }

    #[Test]
    public function the_limit_is_per_user(): void
    {
        $this->fakeSuccess();

        $noisy = $this->operator();
        $quiet = $this->operator();

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($noisy)->get(route('products.index'));
        }

        // One user burning through their budget must not lock out everyone
        // else -- a shared counter would turn one runaway script into an
        // outage for the whole team.
        $this->actingAs($quiet)->get(route('products.index'))->assertOk();
    }

    #[Test]
    public function the_connectivity_probe_is_limited_too(): void
    {
        $this->fakeSuccess();
        $operator = $this->operator();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($operator)->post(route('esb-connections.test', ['family' => 'core']));
        }

        $this->actingAs($operator)
            ->post(route('esb-connections.test', ['family' => 'core']))
            ->assertStatus(429);
    }

    #[Test]
    public function gateway_owned_screens_are_not_limited_by_the_esb_budget(): void
    {
        $this->fakeSuccess();
        $operator = $this->operator();

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($operator)->get(route('products.index'));
        }

        // Reading our own integration log does not touch the ESB, so being
        // over the ESB budget must not stop someone diagnosing why.
        $this->actingAs($operator)->get(route('integration-logs.index'))->assertOk();
        $this->actingAs($operator)->get(route('dashboard'))->assertOk();
    }

    #[Test]
    public function the_limit_is_configurable(): void
    {
        config()->set('esb.rate_limit.per_minute', 1);

        $this->fakeSuccess();
        $operator = $this->operator();

        $this->actingAs($operator)->get(route('products.index'))->assertOk();
        $this->actingAs($operator)->get(route('products.index'))->assertStatus(429);
    }
}
