<?php

namespace Tests\Feature\Pricelists;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Prices that help fill a pricelist ([ESB-032], S4): the active price of
 * each product (GET /pricelist/last-price) and recent purchase invoice
 * prices of one (GET /pricelist/history/purchase-invoice). JSON reads.
 */
class PricelistPriceReadTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @return array<string, mixed>
     */
    private function ok(mixed $result): array
    {
        return ['status' => 'ok', 'code' => 'EC03100000', 'message' => 'OK', 'result' => $result, 'errors' => null];
    }

    /**
     * @return array<string, mixed>
     */
    private static function sentQuery(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    #[Test]
    public function reading_prices_requires_esb_invoke(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson(route('pricelists.last-prices', ['supplierId' => 10, 'currencyId' => 1, 'priceDate' => '2026-04-01', 'productDetailIds' => [501]]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function active_prices_are_keyed_by_product_detail_and_zero_is_no_price(): void
    {
        $sent = null;
        Http::fake(function (Request $request) use (&$sent) {
            $sent = [parse_url($request->url(), PHP_URL_PATH), self::sentQuery($request)];

            return Http::response($this->ok([
                ['productDetailID' => 501, 'price' => 14500],
                ['productDetailID' => 502, 'price' => 0],
            ]));
        });

        $this->actingAs($this->operator())
            ->getJson(route('pricelists.last-prices', ['supplierId' => 10, 'currencyId' => 1, 'priceDate' => '2026-04-01', 'productDetailIds' => [501, 502]]))
            ->assertOk()
            ->assertExactJson(['prices' => ['501' => 14500, '502' => null]]);

        $this->assertSame(['/core-int/pricelist/last-price', [
            'supplierID' => '10',
            'currencyID' => '1',
            'productDetailIDs' => '501,502',
            'priceDate' => '2026-04-01',
        ]], $sent);
    }

    #[Test]
    public function the_last_price_query_is_validated(): void
    {
        Http::fake();
        $this->actingAs($this->operator());

        foreach ([
            ['supplierId' => 10, 'currencyId' => 1, 'priceDate' => '2026-04-01', 'productDetailIds' => []],
            ['supplierId' => 10, 'currencyId' => 1, 'priceDate' => '01/04/2026', 'productDetailIds' => [501]],
            ['currencyId' => 1, 'priceDate' => '2026-04-01', 'productDetailIds' => [501]],
            ['supplierId' => 10, 'currencyId' => 1, 'priceDate' => '2026-04-01', 'productDetailIds' => range(1, 501)],
        ] as $query) {
            $this->getJson(route('pricelists.last-prices', $query))->assertUnprocessable();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function purchase_history_lists_recent_invoice_prices(): void
    {
        $sent = null;
        Http::fake(function (Request $request) use (&$sent) {
            $sent = self::sentQuery($request);

            return Http::response($this->ok([
                ['price' => 14800, 'purchaseInvoiceDate' => '2026-03-10', 'purchaseInvoiceNum' => 'PI-0001', 'supplierID' => '10', 'supplierName' => 'CV Maju Jaya'],
                ['price' => 0, 'purchaseInvoiceDate' => '', 'purchaseInvoiceNum' => '', 'supplierID' => '', 'supplierName' => ''],
            ]));
        });

        $this->actingAs($this->operator())
            ->getJson(route('pricelists.purchase-history', ['supplierId' => 10, 'currencyId' => 1, 'productDetailId' => 501, 'otherSuppliers' => 1]))
            ->assertOk()
            ->assertExactJson(['invoices' => [[
                'invoiceNumber' => 'PI-0001',
                'invoiceDate' => '2026-03-10',
                'price' => 14800,
                'supplierId' => 10,
                'supplierName' => 'CV Maju Jaya',
            ]]]);

        $this->assertSame(['supplierID' => '10', 'currencyID' => '1', 'productDetailID' => '501', 'flagShowAll' => '1'], $sent);
    }
}
