<?php

namespace Tests\Feature\Promotions;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsPromotion;
use Tests\TestCase;

/**
 * POS promotions, ESB OMS ([ESB-049], S1): esb.invoke, the vendor's filter
 * names with empty ones dropped, a bare JSON array read (ADR-027) and paged
 * by the gateway.
 */
class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/api-fnb-backend-int/web/extv1/promotion';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    private function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        return $user;
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        $this->get(route('promotions.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('promotions.index'))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_list_sends_the_filters_and_pages_the_whole_answer(): void
    {
        Http::fake(['*' => Http::response(array_map(
            static fn (int $id): array => OmsPromotion::row(['promotionID' => $id, 'promotionCode' => 'P'.$id]),
            range(1, 25),
        ))]);

        $this->actingAs($this->operator())
            ->get(route('promotions.index', ['branchCode' => 'OUT1', 'promotionCode' => '', 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('promotions/index')
                ->has('promotions', 5)
                ->where('promotions.0.id', 21)
                ->where('pagination.count', 25)
                ->where('pagination.page', 2)
                ->where('filters', ['page' => 2, 'branchCode' => 'OUT1']));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::URL.'?branchCode=OUT1');
    }

    #[Test]
    public function bad_filters_never_reach_the_esb(): void
    {
        Http::fake();

        foreach ([['branchCode' => str_repeat('x', 21)], ['promotionCode' => str_repeat('x', 21)], ['page' => 0]] as $query) {
            $this->actingAs($this->operator())->get(route('promotions.index', $query))->assertSessionHasErrors();
        }

        Http::assertNothingSent();
    }
}
