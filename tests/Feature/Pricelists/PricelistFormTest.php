<?php

namespace Tests\Feature\Pricelists;

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
use Tests\Fixtures\CorePricelist;
use Tests\TestCase;

/**
 * Proposing a pricelist and editing a proposed (temporary) one ([ESB-031],
 * S3): POST /pricelist and PUT /pricelist/temp/{num}, pricelists.manage.
 */
class PricelistFormTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://stg7.esb.co.id/core-int';

    private const NUMBER = CorePricelist::TEMP_NUMBER;

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
     * @return array<string, mixed>
     */
    private function input(array $overrides = []): array
    {
        return [
            'supplierId' => 10,
            'supplierName' => 'CV Maju Jaya',
            'currencyId' => 1,
            'priceDate' => '2026-04-01',
            'expireDate' => '2026-06-30',
            'branchIds' => [1, 2],
            'lines' => [
                ['productDetailId' => 501, 'price' => 15000],
                ['productDetailId' => 502, 'price' => 370000.25],
            ],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expectedBody(): array
    {
        return [
            'supplierID' => 10,
            'currencyID' => 1,
            'priceDate' => '2026-04-01',
            'expireDate' => '2026-06-30',
            'branchIDs' => [1, 2],
            'pricelistDetails' => [
                ['productDetailID' => 501, 'price' => 15000],
                ['productDetailID' => 502, 'price' => 370000.25],
            ],
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
    public function proposing_and_editing_need_pricelists_manage(): void
    {
        Http::fake();
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $this->actingAs($operator);

        $this->get(route('pricelists.create'))->assertForbidden();
        $this->post(route('pricelists.store'), $this->input())->assertForbidden();
        $this->get(route('pricelists.temp.edit', self::NUMBER))->assertForbidden();
        $this->put(route('pricelists.temp.update', self::NUMBER), $this->input())->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_create_page_is_an_empty_form(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route('pricelists.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('pricelists/form')
                ->where('pricelist', null));
    }

    #[Test]
    public function the_edit_page_carries_what_the_update_must_send_back(): void
    {
        Http::fake(['*' => Http::response(CorePricelist::tempDetailResponse())]);

        $this->actingAs($this->manager())
            ->get(route('pricelists.temp.edit', self::NUMBER))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('pricelists/form')
                ->where('pricelist.number', self::NUMBER)
                ->where('pricelist.currencyId', 1)
                ->where('pricelist.supplierId', 10)
                ->has('pricelist.branches.ids'));
    }

    #[Test]
    public function a_proposal_is_sent_once_with_the_vendors_fields_and_lands_on_the_proposals(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = [$request->method().' '.$request->url(), $request->data()];

            return Http::response($this->ok([]));
        });

        $this->actingAs($this->manager())
            ->post(route('pricelists.store'), $this->input())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pricelists.temp.index'))
            ->assertSessionHas('inertia.flash_data.toast', [
                'type' => 'success',
                'message' => 'Pricelist for CV Maju Jaya was created.',
            ]);

        $this->assertSame([['POST '.self::BASE_URL.'/pricelist', $this->expectedBody()]], $sent);
        $audit = AuditLog::query()->sole();
        $this->assertSame('pricelist.created', $audit->action);
        $this->assertSame(['provider' => 'esb', 'name' => 'for CV Maju Jaya', 'supplier_id' => 10, 'lines' => 2], $audit->metadata);
    }

    #[Test]
    public function every_branch_is_an_empty_list(): void
    {
        $sent = null;
        Http::fake(function (Request $request) use (&$sent) {
            $sent = $request->data();

            return Http::response($this->ok([]));
        });

        $this->actingAs($this->manager())
            ->post(route('pricelists.store'), $this->input(['branchIds' => []]))
            ->assertSessionHasNoErrors();

        $this->assertSame([], $sent['branchIDs']);
    }

    #[Test]
    public function an_edit_replaces_the_proposal_under_its_document_lock(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = [$request->method().' '.$request->url(), $request->data()];

            return Http::response($this->ok());
        });

        $this->actingAs($this->manager())
            ->put(route('pricelists.temp.update', self::NUMBER), $this->input())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pricelists.temp.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Pricelist '.self::NUMBER.' was updated.');

        $this->assertSame([['PUT '.self::BASE_URL.'/pricelist/temp/'.self::NUMBER, $this->expectedBody()]], $sent);
        $this->assertSame('pricelist.updated', AuditLog::query()->sole()->action);

        // The same lock as authorize / reject / delete of this proposal.
        $lock = Cache::lock('pricelist-write:'.self::NUMBER, 60);
        $lock->get();

        try {
            $this->put(route('pricelists.temp.update', self::NUMBER), $this->input())
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        $this->assertCount(1, $sent);
    }

    #[Test]
    public function an_invalid_form_never_reaches_the_esb(): void
    {
        Http::fake();
        $this->actingAs($this->manager());

        foreach ([
            [['supplierId' => null], 'supplierId'],
            [['currencyId' => 'x'], 'currencyId'],
            [['priceDate' => '01/04/2026'], 'priceDate'],
            [['expireDate' => '2026-03-01'], 'expireDate'],
            [['lines' => []], 'lines'],
            [['lines' => [['productDetailId' => 501, 'price' => 1], ['productDetailId' => 501, 'price' => 2]]], 'lines.1.productDetailId'],
            [['lines' => [['productDetailId' => 501, 'price' => -1]]], 'lines.0.price'],
            [['lines' => [['productDetailId' => 501, 'price' => 1.12345]]], 'lines.0.price'],
            [['branchIds' => ['a']], 'branchIds.0'],
        ] as [$overrides, $field]) {
            $this->from(route('pricelists.create'))
                ->post(route('pricelists.store'), $this->input($overrides))
                ->assertSessionHasErrors($field);
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_number_the_route_does_not_accept_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->put('/pricelists/temp/export', $this->input())
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_erps_field_errors_land_on_the_form(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'fail', 'code' => 'EC03100003', 'message' => 'Validation Error', 'result' => null,
            'errors' => [['attribute' => 'expireDate', 'code' => 'EC03100003', 'message' => 'Expire date must be after price date']],
        ], 400)]);

        $this->actingAs($this->manager())
            ->from(route('pricelists.create'))
            ->post(route('pricelists.store'), $this->input())
            ->assertRedirect(route('pricelists.create'))
            ->assertSessionHasErrors(['expireDate' => 'Expire date must be after price date']);
    }

    #[Test]
    public function a_timed_out_proposal_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->manager())
            ->post(route('pricelists.store'), $this->input())
            ->assertRedirect(route('pricelists.temp.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $audit = AuditLog::query()->sole();
        $this->assertSame('pricelist.write-unconfirmed', $audit->action);
        $this->assertSame('create', $audit->metadata['operation']);
    }
}
