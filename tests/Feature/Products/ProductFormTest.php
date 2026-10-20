<?php

namespace Tests\Feature\Products;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreProductDetail;
use Tests\TestCase;

/**
 * Creating and editing products with their units ([ESB-031], S3):
 * POST /product, PUT /product/{productID}, products.manage.
 */
class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://stg7.esb.co.id/core-int';

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

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function input(array $overrides = []): array
    {
        return [
            'name' => 'Beras Premium',
            'code' => 'BB-001',
            'categoryId' => 3,
            'subCategoryId' => 7,
            'notes' => '',
            'purchasable' => true,
            'saleable' => false,
            'requestable' => true,
            'vat' => true,
            'luxury' => false,
            'receiptTolerance' => 2.5,
            'units' => [
                ['productDetailId' => null, 'uomId' => 4, 'qty' => 1, 'isBase' => true, 'purchasable' => true, 'saleable' => false, 'stocked' => true, 'transferable' => true, 'sku' => 'BRS-KG', 'basePrice' => 15000, 'weight' => 1, 'active' => true],
            ],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ok(mixed $result = null): array
    {
        return ['status' => 'ok', 'code' => 'EC03100000', 'message' => 'OK', 'result' => $result, 'errors' => null];
    }

    #[Test]
    public function the_form_and_its_writes_need_products_manage(): void
    {
        Http::fake();
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $this->actingAs($operator);

        $this->get(route('products.create'))->assertForbidden();
        $this->post(route('products.store'), $this->input())->assertForbidden();
        $this->get(route('products.edit', 50))->assertForbidden();
        $this->put(route('products.update', 50), $this->input())->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_create_and_edit_pages(): void
    {
        Http::fake(['*' => Http::response(CoreProductDetail::detailResponse())]);
        $this->actingAs($this->manager());

        $this->get(route('products.create'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('products/form')->where('product', null));

        $this->get(route('products.edit', 50))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('products/form')
                ->where('product.id', 50)
                ->where('product.units.1.uomId', 9));
    }

    #[Test]
    public function a_create_is_sent_once_and_opens_the_new_product(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = [$request->method().' '.$request->url(), $request->data()];

            return Http::response($this->ok(['isTemp' => false, 'productID' => 51]));
        });

        $this->actingAs($this->manager())
            ->post(route('products.store'), $this->input())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.show', 51))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Product Beras Premium was created.');

        $this->assertCount(1, $sent);
        $this->assertSame('POST '.self::BASE_URL.'/product', $sent[0][0]);
        $this->assertSame('Beras Premium', $sent[0][1]['productName']);
        $this->assertSame(2.5, $sent[0][1]['receiptTolerance']);
        $this->assertSame([['uomID' => 4, 'qty' => 1, 'isBase' => true, 'isPurchase' => true, 'isSales' => false, 'isStock' => true, 'isTransfer' => true, 'sku' => 'BRS-KG', 'basePrice' => 15000, 'weight' => 1, 'flagActive' => true]], $sent[0][1]['productDetails']);

        $audit = AuditLog::query()->sole();
        $this->assertSame('product.created', $audit->action);
        $this->assertSame(['provider' => 'esb', 'id' => 51, 'name' => 'Beras Premium', 'units' => 1], $audit->metadata);
    }

    #[Test]
    public function a_product_the_erp_holds_for_approval_says_so(): void
    {
        Http::fake(['*' => Http::response($this->ok(['isTemp' => true, 'productID' => 0]))]);

        $this->actingAs($this->manager())
            ->post(route('products.store'), $this->input())
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Product Beras Premium was created and is waiting for approval in the ERP.');

        $this->assertTrue(AuditLog::query()->sole()->metadata['pending_approval']);
    }

    #[Test]
    public function an_update_reads_the_product_then_replaces_it_once_keeping_what_the_form_does_not_edit(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = [$request->method().' '.$request->url(), $request->data()];

            return $request->method() === 'GET'
                ? Http::response(CoreProductDetail::detailResponse(['coretaxProductCodeID' => 77]))
                : Http::response($this->ok());
        });

        $this->actingAs($this->manager())
            ->put(route('products.update', 50), $this->input([
                'units' => [[...$this->input()['units'][0], 'productDetailId' => 501]],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('products.show', 50));

        $this->assertSame(['GET '.self::BASE_URL.'/product/50', 'PUT '.self::BASE_URL.'/product/50'], array_column($sent, 0));
        $this->assertSame(77, $sent[1][1]['coretaxProductCodeID']);
        $this->assertSame(501, $sent[1][1]['productDetails'][0]['productDetailID']);
        $this->assertSame('product.updated', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function an_update_may_not_claim_another_products_unit(): void
    {
        Http::fake(['*' => Http::response(CoreProductDetail::detailResponse())]);

        $this->actingAs($this->manager())
            ->put(route('products.update', 50), $this->input([
                'units' => [[...$this->input()['units'][0], 'productDetailId' => 999]],
            ]))
            ->assertSessionHasErrors('units.0.productDetailId');

        Http::assertSentCount(1);
    }

    #[Test]
    public function an_invalid_form_never_reaches_the_esb(): void
    {
        Http::fake();
        $this->actingAs($this->manager());
        $unit = $this->input()['units'][0];

        foreach ([
            [['name' => ''], 'name'],
            [['name' => str_repeat('a', 101)], 'name'],
            [['categoryId' => null], 'categoryId'],
            [['subCategoryId' => 'x'], 'subCategoryId'],
            [['units' => []], 'units'],
            [['units' => [[...$unit, 'qty' => 0]]], 'units.0.qty'],
            [['units' => [[...$unit, 'uomId' => null]]], 'units.0.uomId'],
            [['units' => [$unit, [...$unit, 'isBase' => false]]], 'units.1.uomId'],
            [['units' => [[...$unit, 'isBase' => false]]], 'units'],
            [['units' => [[...$unit, 'sku' => str_repeat('a', 51)]]], 'units.0.sku'],
        ] as [$overrides, $field]) {
            $this->from(route('products.create'))
                ->post(route('products.store'), $this->input($overrides))
                ->assertSessionHasErrors($field);
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_erps_field_errors_land_on_the_form(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'fail', 'code' => 'EC03100003', 'message' => 'Validation Error', 'result' => null,
            'errors' => [['attribute' => 'productName', 'code' => 'EC03100003', 'message' => 'Product name already used']],
        ], 400)]);

        $this->actingAs($this->manager())
            ->from(route('products.create'))
            ->post(route('products.store'), $this->input())
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['name' => 'Product name already used']);
    }

    #[Test]
    public function a_timed_out_create_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->manager())
            ->post(route('products.store'), $this->input())
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $this->assertSame('create', AuditLog::query()->sole()->metadata['operation']);
    }
}
