<?php

namespace Tests\Feature\PurchaseRequests;

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
 * GET /purchase/purchase-request/{purchaseRequestNum}, end to end.
 */
class PurchaseRequestDetailTest extends TestCase
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
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-request/PR202603260005',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'additionalInfo' => 'Restock bar',
                'authorizedBy' => '',
                'authorizedByName' => '',
                'authorizedDate' => '',
                'branchID' => 3,
                'branchName' => 'Outlet Senopati',
                'costCenterID' => 0,
                'costCenterName' => '',
                'createdBy' => '12',
                'createdByName' => 'Andi',
                'createdDate' => '2026-03-26 09:15:00',
                'editedBy' => '',
                'editedByName' => '',
                'editedDate' => '',
                'imageAttachments' => ['https://storage.esb.co.id/attachments/pr/1.jpg'],
                'isTemplate' => false,
                'print' => ['companyLogoUrl' => 'https://storage.esb.co.id/logo.png'],
                'projectID' => 0,
                'projectName' => '',
                'purchaseNums' => [],
                'purchaseRequestDate' => '2026-03-26',
                'purchaseRequestDetails' => [
                    [
                        'maxBranchProductQty' => 50,
                        'minBranchProductQty' => 10,
                        'notes' => '',
                        'productCode' => 'Air_mineral',
                        'productDetailID' => 901,
                        'productID' => 75,
                        'productName' => 'Air mineral',
                        'qty' => 24,
                        'requestProcessID' => 1,
                        'requestTemplateDetailName' => 'Drinks',
                        'requestTemplateID' => '7',
                        'stock' => 6,
                        'suggestionQty' => 20,
                        'uomName' => 'Bottle',
                    ],
                ],
                'purchaseRequestNum' => 'PR202603260005',
                'requestTemplateID' => 7,
                'requiredDate' => '2026-03-28',
                'statusID' => 1,
                'statusName' => 'Open',
                'transferNums' => [],
            ],
            'errors' => null,
        ];
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('purchase-requests.show', 'PR202603260005'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('purchase-requests.show', 'PR202603260005'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_view_a_purchase_request(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.show', 'PR202603260005'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-requests/show')
                ->where('purchaseRequest.number', 'PR202603260005')
                ->where('purchaseRequest.statusName', 'Open')
                ->where('purchaseRequest.createdByName', 'Andi')
                ->where('purchaseRequest.costCenterId', null)
                ->has('purchaseRequest.lines', 1)
                ->where('purchaseRequest.lines.0.productName', 'Air mineral')
                ->where('purchaseRequest.lines.0.qty', 24),
            );
    }

    #[Test]
    public function it_calls_the_documented_path_for_that_document_number(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.show', 'PR202603260005'))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/purchase-request/PR202603260005');
    }

    #[Test]
    public function the_page_never_receives_vendor_storage_urls(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.show', 'PR202603260005'))
            ->assertDontSee('esb.co.id');
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        // The number is interpolated into the upstream path, so anything
        // beyond a plain document number is refused at the route.
        $this->actingAs($this->operator())
            ->get('/purchase-requests/PR1%2F..%2Fauthorize')
            ->assertNotFound();

        $this->actingAs($this->operator())
            ->get('/purchase-requests/PR1%3Fx%3D1')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unknown_document_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-request/PR404',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'result' => null,
            'errors' => null,
        ], 400)]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.show', 'PR404'))
            ->assertNotFound();

        // A not-found is a business answer, not a transport failure: one call.
        Http::assertSentCount(1);
    }

    #[Test]
    public function the_call_is_recorded_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.show', 'PR202603260005'))
            ->assertOk();

        $this->assertSame('purchase-request.view', IntegrationLog::query()->sole()->operation);
    }
}
