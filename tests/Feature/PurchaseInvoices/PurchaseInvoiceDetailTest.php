<?php

namespace Tests\Feature\PurchaseInvoices;

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
use Tests\TestCase;

/**
 * GET /purchase/purchase-invoices/{purchaseInvoiceNum}, end to end.
 */
class PurchaseInvoiceDetailTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = 'PI202603260005';

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

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function operator(): User
    {
        return $this->userWithRole(Role::IntegrationOperator);
    }

    /**
     * @return array<string, mixed>
     */
    private function detailResponse(): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-invoices/'.self::NUMBER,
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'additionalInfo' => 'March supplies',
                'authorizedBy' => '',
                'authorizedDate' => '',
                'authorizer' => ['fullName' => '', 'username' => ''],
                'branchID' => 3,
                'branchName' => 'Outlet Senopati',
                'costTotal' => 50000,
                'costs' => [
                    ['ID' => 1, 'amount' => 50000, 'coaDescription' => 'Freight', 'coaNo' => '6100'],
                ],
                'createdBy' => 'andi',
                'createdDate' => '2026-03-26 09:15:00',
                'creator' => ['fullName' => 'Andi Wijaya', 'username' => 'andi'],
                'currencyID' => 1,
                'currencyName' => 'Rupiah',
                'currencySign' => 'Rp',
                'details' => [
                    [
                        'ID' => 1,
                        'branchName' => 'Outlet Senopati',
                        'costCenterID' => 0,
                        'costCenterName' => '',
                        'discount' => 0,
                        'discountPercent' => 0,
                        'dppValue' => 1000000,
                        'flagLuxuryItem' => 0,
                        'lastPrice' => 5000,
                        'poDiscount' => 0,
                        'poPrice' => 5000,
                        'price' => 5000,
                        'productCode' => 'Air_mineral',
                        'productDetailID' => 901,
                        'productName' => 'Air mineral',
                        'projectID' => 0,
                        'projectName' => '',
                        'purchaseNumber' => 'PO202603200001',
                        'qty' => 200,
                        'qtyGoods' => 200,
                        'refNum' => 'GR202603250002',
                        'taxID' => 2,
                        'taxName' => 'PPN 11%',
                        'taxRate' => 11,
                        'total' => 1000000,
                        'transDate' => '2026-03-25',
                        'uomID' => 4,
                        'uomName' => 'Bottle',
                        'vat' => true,
                        'vatName' => 'PPN',
                        'vatTotal' => 110000,
                        'vatValue' => 110000,
                    ],
                ],
                'dueDay' => 30,
                'editedBy' => '',
                'editedDate' => '',
                'editor' => ['fullName' => '', 'username' => ''],
                'imageAttachments' => ['https://storage.esb.co.id/attachments/pi/1.jpg'],
                'invoiceTotal' => 1160000,
                'isJournalExist' => false,
                'isStockCardExist' => false,
                'linkInvoiceNumEsbGoods' => '',
                'linkProductSalesInvoiceCompany' => '',
                'linkProductSalesInvoiceNum' => '',
                'printData' => [
                    'companyAddress' => 'Jakarta',
                    'companyLogo' => 'https://storage.esb.co.id/logo.png',
                    'companyName' => 'Maharasa',
                    'signatureSettings' => ['flagAlignment' => false, 'horizontalAlignment' => '', 'signatures' => []],
                    'statusName' => 'Open',
                ],
                'purchaseInvoiceDate' => '2026-03-26',
                'purchaseInvoiceDueDate' => '2026-04-25',
                'purchaseInvoiceNum' => self::NUMBER,
                'rate' => 1,
                'refDate' => '2026-03-25',
                'referenceTotal' => 1110000,
                'statusID' => 1,
                'supplierDueDate' => 30,
                'supplierID' => 14,
                'supplierInvoiceNum' => 'INV/SUP/0331',
                'supplierName' => 'PT Sumber Air',
                'unverifiedDetails' => [],
                'vatInvoiceNum' => '010.000-26.00000001',
            ],
            'errors' => null,
        ];
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('purchase-invoices.show', self::NUMBER))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('purchase-invoices.show', self::NUMBER))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_view_a_purchase_invoice(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.show', self::NUMBER))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-invoices/show')
                ->where('purchaseInvoice.number', self::NUMBER)
                ->where('purchaseInvoice.supplierName', 'PT Sumber Air')
                ->where('purchaseInvoice.statusName', 'Open')
                ->where('purchaseInvoice.createdByName', 'Andi Wijaya')
                ->where('purchaseInvoice.authorizedByName', null)
                ->where('purchaseInvoice.total', 1160000)
                ->has('purchaseInvoice.lines', 1)
                ->where('purchaseInvoice.lines.0.productName', 'Air mineral')
                ->where('purchaseInvoice.lines.0.referenceNumber', 'GR202603250002')
                ->where('purchaseInvoice.lines.0.costCenterName', null)
                ->has('purchaseInvoice.costs', 1)
                ->where('purchaseInvoice.costs.0.accountNumber', '6100'),
            );
    }

    #[Test]
    public function it_calls_the_documented_path_for_that_document_number(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.show', self::NUMBER))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/purchase-invoices/'.self::NUMBER);
    }

    #[Test]
    public function the_page_never_receives_vendor_storage_urls(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.show', self::NUMBER))
            ->assertDontSee('esb.co.id');
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get('/purchase-invoices/PI1%2F..%2Fauthorize')
            ->assertNotFound();

        $this->actingAs($this->operator())
            ->get('/purchase-invoices/PI1%3Fx%3D1')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unknown_document_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-invoices/PI404',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'result' => null,
            'errors' => null,
        ], 400)]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.show', 'PI404'))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function the_call_is_recorded_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.show', self::NUMBER))
            ->assertOk();

        $this->assertSame('purchase-invoice.view', IntegrationLog::query()->sole()->operation);
    }
}
