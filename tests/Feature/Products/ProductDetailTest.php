<?php

namespace Tests\Feature\Products;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreProductDetail;
use Tests\TestCase;

/**
 * GET /products/{productId} ([ESB-026]).
 */
class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/core-int/product';

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
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $this->get(route('products.show', 50))->assertRedirect(route('login'));
        $this->actingAs($viewer)->get(route('products.show', 50))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_detail_shows_the_product_and_its_units(): void
    {
        Http::fake([self::URL.'/50' => Http::response(CoreProductDetail::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('products.show', 50))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('products/show')
                ->where('product.id', 50)
                ->where('product.name', 'Beras Premium')
                ->where('product.categoryName', 'Bahan Baku')
                ->has('product.units', 2)
                ->where('product.units.1.uomName', 'SAK'));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET' && $request->url() === self::URL.'/50');
        $this->assertSame('product.view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_product_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreProductDetail::notFoundResponse('/product/999'), 400)]);

        $this->actingAs($this->operator())->get(route('products.show', 999))->assertNotFound();
        Http::assertSentCount(1);
    }

    #[Test]
    public function a_successful_response_without_a_product_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreProductDetail::detailResponse(['productID' => 0]))]);

        $this->actingAs($this->operator())->get(route('products.show', 50))->assertNotFound();
    }

    #[Test]
    public function an_id_that_is_not_a_positive_number_never_reaches_the_esb(): void
    {
        Http::fake();

        // "list" is a literal vendor path under /product.
        foreach (['list', 'abc', '0', '-1', rawurlencode('1/2'), str_repeat('9', 11)] as $id) {
            $this->actingAs($this->operator())->get('/products/'.$id)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
