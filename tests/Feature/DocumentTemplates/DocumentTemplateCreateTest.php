<?php

namespace Tests\Feature\DocumentTemplates;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CorePurpose;
use Tests\TestCase;

/**
 * Create a document template: POST /document-template ([ESB-016]).
 *
 * Not documented as idempotent, so it follows the purchase request rules:
 * one attempt, a definite refusal shown on the form, an ambiguous failure
 * reported as "check before retrying", and one create per person at a time.
 *
 * The screen's lookups: branches (GET /branch), active purposes
 * (GET /purpose), and products by name (GET /product/list, then each
 * product's units from GET /product/{productID}), which is what supplies the
 * productDetailID each line needs.
 */
class DocumentTemplateCreateTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://stg7.esb.co.id/core-int/document-template';

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
     * Only Super Admin holds document-templates.manage until the business
     * says which role maintains templates.
     */
    private function manager(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    /**
     * @return array<string, mixed>
     */
    private function envelope(
        string $code = 'EC03100000',
        string $message = 'OK',
        mixed $result = null,
        mixed $errors = null,
    ): array {
        return [
            'path' => self::BASE,
            'timestamp' => '2026-01-15 10:30:00',
            'status' => $code === 'EC03100000' ? 'ok' : 'fail',
            'code' => $code,
            'message' => $message,
            'result' => $result,
            'errors' => $errors,
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
            '*/core-int/branch*' => Http::response($this->envelope(result: [
                ['branchCode' => 'SNP', 'branchID' => 3, 'branchName' => 'Outlet Senopati'],
                ['branchCode' => 'KMG', 'branchID' => 4, 'branchName' => 'Outlet Kemang'],
            ])),
            '*/core-int/purpose*' => Http::response(CorePurpose::listResponse()),
            // /product-detail/browse demands productDetailID(s) on the real
            // ESB, and /product/detail/browse leaves out inventory products,
            // so products are found on the master list and their units read
            // from GET /product/{productID} (2026-10-15, staging).
            '*/core-int/product/list*' => Http::response($this->envelope(result: [
                'count' => 1,
                'data' => [
                    ['productID' => 900, 'productCode' => 'RM-0001', 'productName' => 'Beras Premium', 'flagActive' => 1],
                ],
                'limit' => 10,
                'next' => '',
                'page' => 1,
                'prev' => '',
            ])),
            '*/core-int/product/900' => Http::response($this->envelope(result: [
                'productCode' => 'RM-0001',
                'productName' => 'Beras Premium',
                'flagActive' => true,
                'productDetails' => [
                    ['productDetailID' => 5000, 'uomName' => 'GR', 'flagActive' => false],
                    ['productDetailID' => 5001, 'uomName' => 'KG', 'flagActive' => true],
                ],
            ])),
        ]);
    }

    /**
     * The form payload, in the gateway's own field names.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Weekly Kitchen Order',
            'type' => 1,
            'active' => true,
            'branchIds' => [3, 4],
            'lines' => [
                ['productDetailId' => 5001, 'name' => 'Beras', 'purposeId' => 7],
                ['productDetailId' => 5002, 'name' => 'Minyak'],
            ],
            ...$overrides,
        ];
    }

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('document-templates.create'))->assertRedirect(route('login'));
        $this->post(route('document-templates.store'), $this->payload())->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_data_is_not_enough_to_create_a_template(): void
    {
        Http::fake();

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)->get(route('document-templates.create'))->assertForbidden();
        $this->actingAs($operator)->post(route('document-templates.store'), $this->payload())->assertForbidden();

        Http::assertNothingSent();
    }

    // ================================================================= screen

    #[Test]
    public function the_create_screen_has_branches_purposes_and_types_but_no_products_yet(): void
    {
        $this->fakeLookups();

        $this->actingAs($this->manager())
            ->get(route('document-templates.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('document-templates/create')
                ->where('branches.0.id', 3)
                ->where('branches.0.name', 'Outlet Senopati')
                ->has('branches', 2)
                ->where('purposes.0.id', 7)
                ->where('purposes.0.name', 'Entertainment')
                ->has('types', 3)
                ->where('products', [])
                ->where('productSearch', null));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/core-int/purpose')
            && $this->queryOf($request) === ['page' => '1', 'limit' => '100', 'flagActive' => '1']);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/product/'));
    }

    #[Test]
    public function searching_for_a_product_loads_matching_product_details(): void
    {
        $this->fakeLookups();

        $this->actingAs($this->manager())
            ->get(route('document-templates.create', ['productSearch' => 'Beras']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('productSearch', 'Beras')
                ->has('products', 1)
                ->where('products.0.productDetailId', 5001)
                ->where('products.0.productCode', 'RM-0001')
                ->where('products.0.productName', 'Beras Premium')
                ->where('products.0.uomName', 'KG'));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/core-int/product/list')
            && $this->queryOf($request) === ['page' => '1', 'limit' => '10', 'productName' => 'Beras', 'flagActive' => '1']);
        Http::assertSent(fn (Request $request): bool => str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/core-int/product/900'));

        $this->assertTrue(IntegrationLog::query()->where('operation', 'product.list')->exists());
        $this->assertTrue(IntegrationLog::query()->where('operation', 'product.view')->exists());
    }

    #[Test]
    public function the_product_search_is_validated_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route('document-templates.create', ['productSearch' => str_repeat('x', 101)]))
            ->assertSessionHasErrors('productSearch');

        Http::assertNothingSent();
    }

    // ================================================================= create

    #[Test]
    public function a_manager_can_create_a_template(): void
    {
        Http::fake(['*' => Http::response($this->envelope(result: ['requestTemplateID' => 55]), 201)]);

        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('document-templates.store'), $this->payload())
            ->assertRedirect(route('document-templates.show', 55))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::BASE
            && $request->data() === [
                'requestTemplateName' => 'Weekly Kitchen Order',
                'requestTemplateTypeID' => 1,
                'flagActive' => 1,
                'branchIDs' => [3, 4],
                'requestTemplateDetails' => [
                    ['productDetailID' => 5001, 'requestTemplateDetailName' => 'Beras', 'purposeID' => 7],
                    ['productDetailID' => 5002, 'requestTemplateDetailName' => 'Minyak'],
                ],
            ]);

        $audit = AuditLog::query()->sole();
        $this->assertSame('document-template.created', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(55, $audit->metadata['template_id']);
        $this->assertSame(2, $audit->metadata['lines']);
        $this->assertSame([3, 4], $audit->metadata['branch_ids']);

        $this->assertSame('document-template.create', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_inactive_template_is_sent_as_flag_active_zero(): void
    {
        Http::fake(['*' => Http::response($this->envelope(result: ['requestTemplateID' => 55]), 201)]);

        $this->actingAs($this->manager())
            ->post(route('document-templates.store'), $this->payload(['active' => false]))
            ->assertRedirect();

        Http::assertSent(fn (Request $request): bool => $request->data()['flagActive'] === 0);
    }

    #[Test]
    public function a_create_without_a_usable_id_in_the_answer_lands_on_the_list(): void
    {
        // The vendor's example result is {}: the ESB said OK, so the template
        // exists, but no id is promised back. Never link to a guessed one.
        Http::fake(['*' => Http::response($this->envelope(result: []), 201)]);

        $this->actingAs($this->manager())
            ->post(route('document-templates.store'), $this->payload())
            ->assertRedirect(route('document-templates.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame('document-template.created', AuditLog::query()->sole()->action);
        $this->assertNull(AuditLog::query()->sole()->metadata['template_id']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'no name' => [['name' => ''], 'name'],
            'name too long' => [['name' => str_repeat('x', 256)], 'name'],
            'emoji in name' => [['name' => 'Kitchen 🍚'], 'name'],
            'no type' => [['type' => null], 'type'],
            'undocumented type' => [['type' => 4], 'type'],
            'no branches' => [['branchIds' => []], 'branchIds'],
            'branch not an id' => [['branchIds' => [0]], 'branchIds.0'],
            'duplicate branch' => [['branchIds' => [3, 3]], 'branchIds.1'],
            'no lines' => [['lines' => []], 'lines'],
            'line without product' => [['lines' => [['name' => 'Beras']]], 'lines.0.productDetailId'],
            'line without name' => [['lines' => [['productDetailId' => 5001, 'name' => '']]], 'lines.0.name'],
            'emoji in line name' => [['lines' => [['productDetailId' => 5001, 'name' => 'Beras 🍚']]], 'lines.0.name'],
            'purpose not an id' => [['lines' => [['productDetailId' => 5001, 'name' => 'Beras', 'purposeId' => 0]]], 'lines.0.purposeId'],
            // The vendor requires productDetailID to be unique across lines.
            'duplicate product' => [['lines' => [
                ['productDetailId' => 5001, 'name' => 'Beras'],
                ['productDetailId' => 5001, 'name' => 'Beras lagi'],
            ]], 'lines.1.productDetailId'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[Test]
    #[DataProvider('invalidPayloads')]
    public function invalid_input_never_reaches_the_esb(array $overrides, string $errorKey): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->post(route('document-templates.store'), $this->payload($overrides))
            ->assertSessionHasErrors($errorKey);

        Http::assertNothingSent();
    }

    #[Test]
    public function the_esbs_field_errors_come_back_as_form_errors(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100003', 'Validation Error', errors: [
            ['attribute' => 'requestTemplateName', 'field' => 'requestTemplateName', 'code' => 'EC03100003', 'message' => 'name already exists'],
            ['attribute' => 'requestTemplateDetails[0].productDetailID', 'field' => 'requestTemplateDetails[0].productDetailID', 'code' => 'EC03100003', 'message' => 'product detail not found'],
            ['attribute' => 'somethingElse', 'field' => 'somethingElse', 'code' => 'EC03100003', 'message' => 'branch is inactive'],
        ]), 400)]);

        $this->actingAs($this->manager())
            ->from(route('document-templates.create'))
            ->post(route('document-templates.store'), $this->payload())
            ->assertRedirect(route('document-templates.create'))
            ->assertSessionHasErrors([
                'name' => 'name already exists',
                'lines' => 'product detail not found',
                'form' => 'branch is inactive',
            ]);

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_validation_refusal_without_detail_still_says_so(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100003', 'Validation Error'), 400)]);

        $this->actingAs($this->manager())
            ->from(route('document-templates.create'))
            ->post(route('document-templates.store'), $this->payload())
            ->assertSessionHasErrors('form');
    }

    #[Test]
    public function a_timed_out_create_is_not_retried_and_is_reported_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->manager())
            ->post(route('document-templates.store'), $this->payload())
            ->assertRedirect(route('document-templates.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('document-template.write-unconfirmed', $audit->action);
        $this->assertSame('create', $audit->metadata['operation']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function a_second_create_from_the_same_person_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        $manager = $this->manager();

        $lock = Cache::lock('document-template-create:'.$manager->id, 60);
        $lock->get();

        try {
            $this->actingAs($manager)
                ->post(route('document-templates.store'), $this->payload())
                ->assertRedirect(route('document-templates.index'))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }
}
