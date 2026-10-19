<?php

namespace Tests\Feature\Purposes;

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
use Tests\Fixtures\CorePurpose;
use Tests\TestCase;

/**
 * ESB Core Purpose, read-only: GET /purpose and GET /purpose/{purposeID}
 * ([ESB-015]).
 */
class PurposeTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_URL = 'https://stg7.esb.co.id/core-int/purpose';

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
        $this->get(route('purposes.index'))->assertRedirect(route('login'));
        $this->get(route('purposes.show', 7))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_purposes_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('purposes.index'))->assertForbidden();
            $this->actingAs($user)->get(route('purposes.show', 7))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // =================================================================== list

    #[Test]
    public function an_operator_can_list_purposes(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CorePurpose::listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('purposes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purposes/index')
                ->has('purposes', 1)
                ->where('purposes.0.id', 7)
                ->where('purposes.0.name', 'Entertainment')
                ->where('purposes.0.accountNo', '6105')
                ->where('purposes.0.appliesTo', ['Stock Usage', 'Petty Cash'])
                ->where('purposes.0.active', true)
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1, 'limit' => 20]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'?page=1&limit=20');

        $this->assertSame('purpose.list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function the_list_can_be_filtered_and_paged(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CorePurpose::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purposes.index', [
                'page' => 2,
                'purposeName' => 'Enter',
                'purposeCoaNo' => '61',
                'flagActive' => 0,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', [
                    'page' => 2,
                    'limit' => 20,
                    'purposeName' => 'Enter',
                    'purposeCoaNo' => '61',
                    'flagActive' => 0,
                ]));

        Http::assertSent(fn (Request $request) => $request->url()
            === self::LIST_URL.'?page=2&limit=20&purposeName=Enter&purposeCoaNo=61&flagActive=0');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('purposes.index', [
                'page' => 0,
                'limit' => 101,
                'purposeName' => str_repeat('x', 256),
                'flagActive' => 2,
            ]))
            ->assertSessionHasErrors(['page', 'limit', 'purposeName', 'flagActive']);

        Http::assertNothingSent();
    }

    // ================================================================= detail

    #[Test]
    public function the_detail_shows_the_purpose(): void
    {
        Http::fake([self::LIST_URL.'/7' => Http::response(CorePurpose::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purposes.show', 7))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purposes/show')
                ->where('purpose.id', 7)
                ->where('purpose.name', 'Entertainment')
                ->where('purpose.transactionTypeIds', [3, 5]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'/7');

        $this->assertSame('purpose.view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_purpose_is_a_404(): void
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
            ->get(route('purposes.show', 999))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_successful_response_without_a_purpose_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CorePurpose::detailResponse(['purposeID' => 0]))]);

        $this->actingAs($this->operator())
            ->get(route('purposes.show', 7))
            ->assertNotFound();
    }

    #[Test]
    public function an_id_that_is_not_a_positive_number_never_reaches_the_esb(): void
    {
        Http::fake();

        foreach (['abc', '0', '-1', rawurlencode('1/2'), str_repeat('9', 11)] as $id) {
            $this->actingAs($this->operator())->get('/purposes/'.$id)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
