<?php

namespace Tests\Feature\PurchaseRequests;

use App\Authorization\Role;
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
 * The create and edit screens, and the lookups that feed them.
 *
 * Create needs a productDetailID and a requestProcessID on every line. The
 * gateway has no product master of its own, so lines come from the ESB's
 * request templates: pick a branch (GET /branch), pick one of its templates
 * (GET /document-template/list), and the template's lines arrive with both
 * ids (GET /purchase/purchase-request/template-details).
 */
class PurchaseRequestFormTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = 'PR202603260005';

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

    /**
     * Only Super Admin holds purchase-requests.manage until the business
     * says which role raises purchase requests.
     */
    private function manager(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    /**
     * @return array<string, mixed>
     */
    private function ok(mixed $result): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/whatever',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $result,
            'errors' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queryOf(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    private function fakeLookups(): void
    {
        Http::fake([
            '*/core-int/branch*' => Http::response($this->ok([
                ['branchCode' => 'SNP', 'branchID' => 3, 'branchName' => 'Outlet Senopati'],
                ['branchCode' => 'KMG', 'branchID' => 4, 'branchName' => 'Outlet Kemang'],
            ])),
            '*/core-int/document-template/list*' => Http::response($this->ok([
                ['requestTemplateID' => 7, 'requestTemplateName' => 'Weekly bar', 'requestTemplateTypeID' => 1],
            ])),
            '*/core-int/purchase/purchase-request/template-details*' => Http::response($this->ok([
                [
                    'productCode' => 'Air_mineral',
                    'productDetailID' => 901,
                    'productID' => 75,
                    'productName' => 'Air mineral',
                    'qty' => 0,
                    'requestProcessID' => 1,
                    'requestProcessName' => 'Purchase',
                    'requestTemplateDetailName' => 'Drinks',
                    'requestTemplateID' => 7,
                    'stock' => 6,
                    'suggestionQty' => 20,
                    'minBranchProductQty' => 10,
                    'maxBranchProductQty' => 50,
                    'uomName' => 'Bottle',
                ],
            ])),
            '*/core-int/purchase/purchase-request/'.self::NUMBER => Http::response($this->ok([
                'additionalInfo' => 'Restock bar',
                'branchID' => 3,
                'branchName' => 'Outlet Senopati',
                'costCenterID' => 5,
                'costCenterName' => 'Bar',
                'isTemplate' => false,
                'projectID' => 0,
                'projectName' => '',
                'purchaseRequestDate' => '2026-03-26',
                'purchaseRequestDetails' => [
                    [
                        'notes' => 'cold',
                        'productCode' => 'Air_mineral',
                        'productDetailID' => 901,
                        'productID' => 75,
                        'productName' => 'Air mineral',
                        'qty' => 24,
                        'requestProcessID' => 1,
                        'uomName' => 'Bottle',
                    ],
                ],
                'purchaseRequestNum' => self::NUMBER,
                'requestTemplateID' => 7,
                'requiredDate' => '2026-03-28',
                'statusID' => 1,
                'statusName' => 'Open',
            ])),
        ]);
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('purchase-requests.create'))->assertRedirect(route('login'));
        $this->get(route('purchase-requests.edit', self::NUMBER))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_data_is_not_enough_to_raise_a_purchase_request(): void
    {
        // esb.invoke lets an operator read through the gateway. Raising and
        // changing purchasing documents is a business action with its own
        // permission, like approving one.
        Http::fake();

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)->get(route('purchase-requests.create'))->assertForbidden();
        $this->actingAs($operator)->get(route('purchase-requests.edit', self::NUMBER))->assertForbidden();

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- create

    #[Test]
    public function the_create_screen_starts_with_the_branch_list_only(): void
    {
        $this->fakeLookups();

        $this->actingAs($this->manager())
            ->get(route('purchase-requests.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-requests/form')
                ->where('purchaseRequest', null)
                ->has('branches', 2)
                ->where('branches.0.id', 3)
                ->where('branches.0.name', 'Outlet Senopati')
                ->where('templates', [])
                ->where('templateLines', [])
                ->where('selection.branchId', null));

        // Templates depend on the branch, so nothing else is asked for yet.
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://stg7.esb.co.id/core-int/branch?')
            && $this->queryOf($request) === ['flagActive' => '1']);
    }

    #[Test]
    public function choosing_a_branch_loads_its_templates(): void
    {
        $this->fakeLookups();

        $this->actingAs($this->manager())
            ->get(route('purchase-requests.create', ['branchId' => 3]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('templates', 1)
                ->where('templates.0.id', 7)
                ->where('templates.0.name', 'Weekly bar')
                ->where('templateLines', [])
                ->where('selection.branchId', 3));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/document-template/list')
            && $this->queryOf($request) === ['branchID' => '3', 'flagActive' => 'true']);
    }

    #[Test]
    public function choosing_a_template_loads_its_lines_with_the_ids_create_needs(): void
    {
        $this->fakeLookups();

        $this->actingAs($this->manager())
            ->get(route('purchase-requests.create', ['branchId' => 3, 'templateId' => 7]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('templateLines', 1)
                ->where('templateLines.0.productDetailId', 901)
                ->where('templateLines.0.requestProcessId', 1)
                ->where('templateLines.0.productName', 'Air mineral')
                ->where('templateLines.0.suggestionQty', 20)
                ->where('selection.templateId', 7));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/purchase/purchase-request/template-details')
            && $this->queryOf($request) === ['branchID' => '3', 'requestTemplateID' => '7']);
    }

    #[Test]
    public function lookup_parameters_are_validated_before_reaching_the_esb(): void
    {
        Http::fake();

        $manager = $this->manager();

        $this->actingAs($manager)
            ->get(route('purchase-requests.create', ['branchId' => 'abc']))
            ->assertSessionHasErrors('branchId');

        // A template belongs to a branch; asking for one alone means nothing.
        $this->actingAs($manager)
            ->get(route('purchase-requests.create', ['templateId' => 7]))
            ->assertSessionHasErrors('branchId');

        Http::assertNothingSent();
    }

    // ------------------------------------------------------------------ edit

    #[Test]
    public function the_edit_screen_is_prefilled_from_the_erp(): void
    {
        $this->fakeLookups();

        $this->actingAs($this->manager())
            ->get(route('purchase-requests.edit', self::NUMBER))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-requests/form')
                ->where('purchaseRequest.number', self::NUMBER)
                ->where('purchaseRequest.costCenterId', 5)
                ->where('purchaseRequest.lines.0.requestProcessId', 1)
                ->where('selection.branchId', 3)
                ->has('templates', 1)
                ->where('templateLines', []));
    }

    #[Test]
    public function the_edit_screen_can_load_more_lines_from_a_template(): void
    {
        $this->fakeLookups();

        $this->actingAs($this->manager())
            ->get(route('purchase-requests.edit', ['number' => self::NUMBER, 'templateId' => 7]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('templateLines', 1)
                ->where('selection.templateId', 7));
    }

    #[Test]
    public function editing_an_unknown_document_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'status' => 'fail',
            'result' => null,
            'errors' => null,
        ], 400)]);

        $this->actingAs($this->manager())
            ->get(route('purchase-requests.edit', 'PR404'))
            ->assertNotFound();
    }
}
