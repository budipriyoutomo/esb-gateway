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
 *
 * The two files come from different vendor formats since 2026-09-30: ESB Core
 * from an OpenAPI description (docs links `#/paths/{slug}/{method}`), ESB OMS
 * still from apiDoc (docs links `#api-{Group}-{name}`). Both must import.
 */
class ImportEndpointCatalogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_every_operation_from_both_catalogs(): void
    {
        $this->artisan('esb:import-catalog')->assertSuccessful();

        // 1224 ESB Core plus 38 ESB OMS, matching the generated catalogs.
        $this->assertSame(1262, IntegrationEndpoint::query()->count());
        $this->assertSame(1224, IntegrationEndpoint::query()->where('product', 'core')->count());
        $this->assertSame(38, IntegrationEndpoint::query()->where('product', 'oms')->count());
    }

    #[Test]
    public function it_captures_the_details_of_an_openapi_operation(): void
    {
        $this->artisan('esb:import-catalog');

        // OpenAPI identifies an operation by method and path; the vendor sets
        // no operationId. The group (a tag) is display only, so a re-tagging
        // upstream does not turn one operation into a removed and a new one.
        $endpoint = IntegrationEndpoint::query()
            ->where('code', 'core:GET /product/list')
            ->sole();

        $this->assertSame('List all products', $endpoint->name);
        $this->assertSame('2.0.0', $endpoint->version);
        $this->assertSame('Master Product', $endpoint->group);
        $this->assertSame('GET', $endpoint->method);
        $this->assertSame('/product/list', $endpoint->path);
        $this->assertSame('core', $endpoint->family);
        $this->assertStringEndsWith('esb-core/#/paths/product-list/get', (string) $endpoint->docs_url);
    }

    #[Test]
    public function it_still_imports_apidoc_operations_by_their_apidoc_identity(): void
    {
        $this->artisan('esb:import-catalog');

        $endpoint = IntegrationEndpoint::query()
            ->where('code', 'oms:Master_Menu:create-menu@1.0.0')
            ->sole();

        // The group is the section heading, spelled the same way for both
        // products, so the group filter does not mix "Master_Menu" and
        // "Master Product" styles.
        $this->assertSame('Master Menu', $endpoint->group);
        $this->assertSame('POST', $endpoint->method);
        $this->assertSame('/corev1/master/create-menu', $endpoint->path);
    }

    #[Test]
    public function it_maps_a_group_to_its_base_url_family(): void
    {
        $this->artisan('esb:import-catalog');

        // The Core OpenAPI document declares only the core host, so every Core
        // operation is the core family. The OMS groups keep the hosts their
        // apiDoc pages declare (ADR-010).
        $this->assertSame(
            ['core'],
            IntegrationEndpoint::query()->where('product', 'core')->distinct()->pluck('family')->all(),
        );

        $this->assertSame(
            'fnb_backend',
            IntegrationEndpoint::query()->where('group', 'Master Menu')->value('family'),
        );

        $this->assertSame(
            'pos',
            IntegrationEndpoint::query()->where('group', 'Master POS')->value('family'),
        );
    }

    #[Test]
    public function documentation_pages_without_an_operation_are_kept_but_have_no_method(): void
    {
        $this->artisan('esb:import-catalog');

        // Some apiDoc entries are narrative "Flow" pages, not callable
        // operations. They belong in the catalog an operator browses, but they
        // must not look like something you can invoke.
        $flow = IntegrationEndpoint::query()
            ->where('code', 'oms:Push_Sales_Data:flow-push-sales-shift-data@1.0.0')
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

        $this->assertSame(1262, IntegrationEndpoint::query()->count());
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
            ->expectsOutputToContain('1262')
            ->assertSuccessful();
    }
}
