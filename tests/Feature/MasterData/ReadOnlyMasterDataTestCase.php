<?php

namespace Tests\Feature\MasterData;

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
 * The behaviour every read-only master data list + detail shares
 * ([ESB-020]–[ESB-023]): esb.invoke, filters sent upstream with empty ones
 * dropped, bounded input, paging, a 404 for an unknown id, and an id that
 * cannot escape the upstream path.
 *
 * Each module's test supplies its routes, paths and fixtures, and asserts
 * its own fields.
 */
abstract class ReadOnlyMasterDataTestCase extends TestCase
{
    use RefreshDatabase;

    protected const BASE_URL = 'https://stg7.esb.co.id/core-int';

    /** Route name prefix and Inertia page directory, e.g. "units". */
    abstract protected function resource(): string;

    /** Upstream list path, e.g. "/units". */
    abstract protected function listPath(): string;

    /** Upstream detail path prefix, e.g. "/units". */
    abstract protected function detailPath(): string;

    /** The id the detail fixture carries. */
    abstract protected function sampleId(): int;

    /** The vendor's id field in the detail, e.g. "uomID". */
    abstract protected function idField(): string;

    /** Integration log operation prefix, e.g. "unit". */
    abstract protected function operation(): string;

    /** Inertia prop of the list rows, e.g. "units". */
    abstract protected function listProp(): string;

    /** Inertia prop of the detail, e.g. "unit". */
    abstract protected function detailProp(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function listResponse(int $count = 1, int $page = 1): array;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    abstract protected function detailResponse(array $overrides = []): array;

    /**
     * A filtered request: [gateway query, echoed filters, upstream query string
     * after "?page=…&limit=…&"].
     *
     * @return array{array<string, mixed>, array<string, mixed>, string}
     */
    abstract protected function filterCase(): array;

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

    protected function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        return $user;
    }

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route($this->resource().'.index'))->assertRedirect(route('login'));
        $this->get(route($this->resource().'.show', $this->sampleId()))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role->value);

            $this->actingAs($user)->get(route($this->resource().'.index'))->assertForbidden();
            $this->actingAs($user)->get(route($this->resource().'.show', $this->sampleId()))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // =================================================================== list

    #[Test]
    public function an_operator_can_list(): void
    {
        Http::fake([self::BASE_URL.$this->listPath().'*' => Http::response($this->listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route($this->resource().'.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($this->resource().'/index')
                ->has($this->listProp(), 1)
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1, 'limit' => 20]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::BASE_URL.$this->listPath().'?page=1&limit=20');

        $this->assertSame($this->operation().'.list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function the_list_can_be_filtered_and_paged(): void
    {
        Http::fake([self::BASE_URL.$this->listPath().'*' => Http::response($this->listResponse(page: 2))]);
        [$query, $echoed, $upstream] = $this->filterCase();

        $this->actingAs($this->operator())
            ->get(route($this->resource().'.index', ['page' => 2, ...$query]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['page' => 2, 'limit' => 20, ...$echoed]));

        Http::assertSent(fn (Request $request) => $request->url()
            === self::BASE_URL.$this->listPath().'?page=2&limit=20&'.$upstream);
    }

    #[Test]
    public function empty_filters_are_not_sent(): void
    {
        Http::fake([self::BASE_URL.$this->listPath().'*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())
            ->get(route($this->resource().'.index', ['search' => '', 'flagActive' => '']))
            ->assertOk();

        Http::assertSent(fn (Request $request) => $request->url()
            === self::BASE_URL.$this->listPath().'?page=1&limit=20');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route($this->resource().'.index', [
                'page' => 0,
                'limit' => 101,
                'search' => str_repeat('x', 256),
                'flagActive' => 2,
            ]))
            ->assertSessionHasErrors(['page', 'limit', 'search', 'flagActive']);

        Http::assertNothingSent();
    }

    // ================================================================= detail

    #[Test]
    public function the_detail_is_read_by_id(): void
    {
        $url = self::BASE_URL.$this->detailPath().'/'.$this->sampleId();
        Http::fake([$url => Http::response($this->detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route($this->resource().'.show', $this->sampleId()))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($this->resource().'/show')
                ->where($this->detailProp().'.id', $this->sampleId()));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET' && $request->url() === $url);

        $this->assertSame($this->operation().'.view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_id_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'path' => self::BASE_URL.$this->detailPath().'/999',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'result' => null,
            'errors' => null,
        ], 400)]);

        $this->actingAs($this->operator())
            ->get(route($this->resource().'.show', 999))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_successful_response_without_a_record_is_a_404(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse([$this->idField() => 0]))]);

        $this->actingAs($this->operator())
            ->get(route($this->resource().'.show', $this->sampleId()))
            ->assertNotFound();
    }

    #[Test]
    public function an_id_that_is_not_a_positive_number_never_reaches_the_esb(): void
    {
        Http::fake();
        $base = parse_url(route($this->resource().'.index'), PHP_URL_PATH);

        foreach (['abc', '0', '-1', rawurlencode('1/2'), str_repeat('9', 11)] as $id) {
            $this->actingAs($this->operator())->get($base.'/'.$id)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
