<?php

namespace Tests\Feature\BillsOfMaterial;

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
use Tests\Fixtures\CoreBom;
use Tests\TestCase;

/**
 * ESB Core Master Bill of Material, read-only: GET /product/bom and
 * GET /product/bom/{bomID} ([ESB-014]).
 */
class BillOfMaterialTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_URL = 'https://stg7.esb.co.id/core-int/product/bom';

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

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('bills-of-material.index'))->assertRedirect(route('login'));
        $this->get(route('bills-of-material.show', 412))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_boms_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('bills-of-material.index'))->assertForbidden();
            $this->actingAs($user)->get(route('bills-of-material.show', 412))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // =================================================================== list

    #[Test]
    public function an_operator_can_list_boms(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CoreBom::listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('bills-of-material.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('bills-of-material/index')
                ->has('boms', 1)
                ->where('boms.0.id', 412)
                ->where('boms.0.code', 'BOM-NG01')
                ->where('boms.0.name', 'Nasi Goreng Spesial')
                ->where('boms.0.typeName', 'Menu')
                ->where('boms.0.active', true)
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1, 'limit' => 20]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'?page=1&limit=20');

        $this->assertSame('bom.list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function the_list_can_be_filtered_and_paged(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CoreBom::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('bills-of-material.index', [
                'page' => 2,
                'bomName' => 'Nasi',
                'bomCode' => 'BOM-NG',
                'productName' => 'Goreng',
                'flagActive' => 0,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', [
                    'page' => 2,
                    'limit' => 20,
                    'bomName' => 'Nasi',
                    'bomCode' => 'BOM-NG',
                    'productName' => 'Goreng',
                    'flagActive' => 0,
                ]));

        Http::assertSent(fn (Request $request) => $request->url()
            === self::LIST_URL.'?page=2&limit=20&bomName=Nasi&bomCode=BOM-NG&productName=Goreng&flagActive=0');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('bills-of-material.index', [
                'page' => 0,
                'limit' => 101,
                'bomName' => str_repeat('x', 256),
                'flagActive' => 2,
            ]))
            ->assertSessionHasErrors(['page', 'limit', 'bomName', 'flagActive']);

        Http::assertNothingSent();
    }

    // ================================================================= detail

    #[Test]
    public function the_detail_shows_the_bom_with_its_materials(): void
    {
        Http::fake([self::LIST_URL.'/412' => Http::response(CoreBom::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('bills-of-material.show', 412))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('bills-of-material/show')
                ->where('bom.id', 412)
                ->where('bom.code', 'BOM-NG01')
                ->has('bom.lines', 2)
                ->where('bom.lines.0.productName', 'Beras')
                ->has('bom.lines.0.substitutes', 1)
                ->has('bom.costs', 1)
                ->has('bom.replacements', 1));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'/412');

        $this->assertSame('bom.view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_bom_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'path' => self::LIST_URL.'/999',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'result' => null,
            'errors' => null,
        ], 400)]);

        $this->actingAs($this->operator())
            ->get(route('bills-of-material.show', 999))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_successful_response_without_a_bom_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreBom::detailResponse(['bomID' => 0]))]);

        $this->actingAs($this->operator())
            ->get(route('bills-of-material.show', 412))
            ->assertNotFound();
    }

    #[Test]
    public function an_id_that_is_not_a_positive_number_never_reaches_the_esb(): void
    {
        Http::fake();

        foreach (['abc', '0', '-1', rawurlencode('1/2'), str_repeat('9', 11)] as $id) {
            $this->actingAs($this->operator())->get('/bills-of-material/'.$id)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
