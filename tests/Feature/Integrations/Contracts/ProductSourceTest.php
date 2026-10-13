<?php

namespace Tests\Feature\Integrations\Contracts;

use App\Domain\Canonical\Product;
use App\Domain\Canonical\ProductQuery;
use App\Integrations\Contracts\OutboundAdapter;
use App\Integrations\Contracts\ProductSource;
use App\Integrations\ESB\Clients\ProductClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The ESB product adapter seen through the provider-agnostic contract.
 *
 * ADR-017 requires the contracts to be extracted from the working ESB
 * implementation rather than designed in the abstract, so this test describes
 * the seam a second provider plugs into: canonical query in, canonical page
 * out, with nothing ESB-shaped crossing the boundary.
 */
class ProductSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function productListResponse(): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'page' => 2,
                'limit' => 20,
                'count' => 92,
                'data' => [
                    [
                        'productID' => 41,
                        'productName' => 'Es Teh Manis',
                        'productCode' => 'BVG-001',
                        'categoryID' => 3,
                        'categoryName' => 'Beverage',
                        'flagActive' => 1,
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function the_esb_product_client_is_the_reference_implementation(): void
    {
        $client = app(ProductClient::class);

        $this->assertInstanceOf(ProductSource::class, $client);
        $this->assertInstanceOf(OutboundAdapter::class, $client);
    }

    #[Test]
    public function the_contract_resolves_to_the_esb_adapter(): void
    {
        // Callers depend on the contract, not on ProductClient. Without this
        // binding the seam exists on paper only.
        $this->assertInstanceOf(ProductClient::class, app(ProductSource::class));
    }

    #[Test]
    public function it_reports_which_provider_answered(): void
    {
        $this->assertSame('esb', app(ProductSource::class)->provider());
    }

    #[Test]
    public function it_returns_canonical_products_and_pagination(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        $page = app(ProductSource::class)->listProducts(new ProductQuery(page: 2));

        $this->assertCount(1, $page->products);

        $product = $page->products[0];
        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame(41, $product->id);
        $this->assertSame('Es Teh Manis', $product->name);
        $this->assertSame('BVG-001', $product->code);
        $this->assertTrue($product->isActive);

        // A canonical record carries its origin: with more than one provider,
        // two products may share an id and mean different things.
        $this->assertSame('esb', $product->provider);

        $this->assertNotNull($page->pagination);
        $this->assertSame(2, $page->pagination->page);
        $this->assertSame(5, $page->pagination->lastPage());
    }

    #[Test]
    public function it_translates_canonical_filters_into_the_vendors_parameter_names(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        app(ProductSource::class)->listProducts(new ProductQuery(
            page: 3,
            limit: 50,
            name: 'Teh',
            categoryId: 3,
            activeOnly: true,
        ));

        // The vendor's spelling stays behind the adapter. This assertion is the
        // point of the contract: a caller never writes `categoryID`.
        Http::assertSent(function (Request $request): bool {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['page'] ?? null) === '3'
                && ($query['limit'] ?? null) === '50'
                && ($query['productName'] ?? null) === 'Teh'
                && ($query['categoryID'] ?? null) === '3'
                && ($query['flagActive'] ?? null) === '1';
        });
    }

    #[Test]
    public function unset_filters_are_omitted_entirely(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        app(ProductSource::class)->listProducts(new ProductQuery);

        // Sending an empty filter makes the ESB narrow the result set to rows
        // matching an empty string, which is why null means "omit", not "".
        Http::assertSent(function (Request $request): bool {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ! array_key_exists('productName', $query)
                && ! array_key_exists('flagActive', $query);
        });
    }
}
