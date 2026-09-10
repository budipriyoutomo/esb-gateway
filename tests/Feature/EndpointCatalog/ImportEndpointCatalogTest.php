<?php

namespace Tests\Feature\EndpointCatalog;

use App\Models\IntegrationEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The catalog is imported from the generated files in docs/esb/, which are
 * themselves a mechanical transcription of the vendor documentation
 * (docs/04-functional-requirements.md keeps the catalog as
 * documentation/configuration rather than a hand-maintained registry).
 */
class ImportEndpointCatalogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_every_operation_from_both_catalogs(): void
    {
        $this->artisan('esb:import-catalog')->assertSuccessful();

        // 363 ESB Core plus 38 ESB OMS, matching the generated catalogs.
        $this->assertSame(401, IntegrationEndpoint::query()->count());
        $this->assertSame(363, IntegrationEndpoint::query()->where('product', 'core')->count());
        $this->assertSame(38, IntegrationEndpoint::query()->where('product', 'oms')->count());
    }

    #[Test]
    public function it_captures_the_details_of_a_known_operation(): void
    {
        $this->artisan('esb:import-catalog');

        $endpoint = IntegrationEndpoint::query()
            ->where('code', 'core:Master_Product:product-list@2.0.0')
            ->sole();

        $this->assertSame('Product List', $endpoint->name);
        $this->assertSame('2.0.0', $endpoint->version);
        $this->assertSame('Master_Product', $endpoint->group);
        $this->assertSame('GET', $endpoint->method);
        $this->assertSame('/product/list', $endpoint->path);
        $this->assertSame('core', $endpoint->family);
        $this->assertStringContainsString('#api-Master_Product-product-list', (string) $endpoint->docs_url);
    }

    #[Test]
    public function it_maps_a_group_to_its_base_url_family(): void
    {
        $this->artisan('esb:import-catalog');

        // Online_Voucher is the one Core group the vendor puts on the voucher
        // host rather than the core host (ADR-010).
        $this->assertSame(
            'voucher',
            IntegrationEndpoint::query()->where('group', 'Online_Voucher')->value('family'),
        );

        $this->assertSame(
            'fnb_backend',
            IntegrationEndpoint::query()->where('group', 'Master_Menu')->value('family'),
        );
    }

    #[Test]
    public function documentation_pages_without_an_operation_are_kept_but_have_no_method(): void
    {
        $this->artisan('esb:import-catalog');

        // Four Core "Flow" entries are narrative pages, not callable
        // operations. They belong in the catalog an operator browses, but they
        // must not look like something you can invoke.
        $flow = IntegrationEndpoint::query()
            ->where('code', 'core:Goods_Delivery:flow-goods-delivery@1.0.0')
            ->sole();

        $this->assertNull($flow->method);
        $this->assertNull($flow->path);
        $this->assertFalse($flow->is_operation);
    }

    #[Test]
    public function it_is_idempotent(): void
    {
        $this->artisan('esb:import-catalog');
        $this->artisan('esb:import-catalog');

        $this->assertSame(401, IntegrationEndpoint::query()->count());
    }

    #[Test]
    public function it_removes_endpoints_that_are_no_longer_in_the_catalog(): void
    {
        IntegrationEndpoint::query()->create([
            'code' => 'core:Retired_Group:gone@1.0.0',
            'version' => '1.0.0',
            'product' => 'core',
            'group' => 'Retired_Group',
            'name' => 'Removed operation',
            'method' => 'GET',
            'path' => '/gone',
            'family' => 'core',
            'docs_url' => 'https://example.test',
            'is_operation' => true,
        ]);

        $this->artisan('esb:import-catalog');

        // An operation the vendor withdrew must not linger in a catalog people
        // read to decide what they can call.
        $this->assertSame(
            0,
            IntegrationEndpoint::query()->where('code', 'core:Retired_Group:gone@1.0.0')->count(),
        );
    }

    #[Test]
    public function it_reports_what_it_did(): void
    {
        $this->artisan('esb:import-catalog')
            ->expectsOutputToContain('401')
            ->assertSuccessful();
    }
}
