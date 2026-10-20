<?php

namespace Tests\Feature\Inventory;

use App\Authorization\Role;
use App\Domain\Canonical\InventoryKind;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;
use Tests\TestCase;

/**
 * List and detail of one inventory document ([ESB-033], S1): esb.invoke,
 * filters translated to the vendor's names and empty ones dropped, paging
 * without the ESB's own prev/next URLs, 404 for EC03100004 and for numbers
 * that would reach literal vendor paths. Each kind supplies its paths.
 */
abstract class InventoryDocumentTestCase extends TestCase
{
    use RefreshDatabase;

    abstract protected function kind(): InventoryKind;

    /** e.g. "/inventory/goods-delivery". */
    abstract protected function upstreamPath(): string;

    abstract protected function number(): string;

    /** The vendor's number filter, e.g. "goodsDeliveryNum". */
    abstract protected function numberParam(): string;

    /** The vendor's branch filter, or null when the list has none. */
    abstract protected function branchParam(): ?string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function row(): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function detailResponse(): array;

    /**
     * Answers for the detail page's extra reads, by path suffix.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function extraReads(): array
    {
        return [];
    }

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

    /**
     * @return array<string, mixed>
     */
    protected static function sentQuery(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * Fake the ESB: the list, the detail, and the extra reads.
     *
     * @param  list<string>  $sent  Filled with "PATH?QUERY" of every request.
     */
    protected function fakeUpstream(array &$sent = [], ?array $detail = null): void
    {
        Http::fake(function (Request $request) use (&$sent, $detail) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $sent[] = $path.'?'.http_build_query(self::sentQuery($request));

            foreach ($this->extraReads() as $suffix => $response) {
                if (str_contains($path, $suffix)) {
                    return Http::response($response);
                }
            }

            if (str_ends_with($path, '/'.$this->number())) {
                return Http::response($detail ?? $this->detailResponse());
            }

            return Http::response(CoreInventory::listResponse($this->upstreamPath(), [$this->row()], count: 45, page: 2));
        });
    }

    protected function index(array $query = [], ?User $user = null)
    {
        return $this->actingAs($user ?? $this->operator())
            ->get(route($this->kind()->value.'.index', $query));
    }

    protected function show(string $number, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->operator())
            ->get(route($this->kind()->value.'.show', $number));
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        $this->get(route($this->kind()->value.'.index'))->assertRedirect(route('login'));
        $this->index(user: User::factory()->create())->assertForbidden();
        $this->show($this->number(), User::factory()->create())->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_list_shows_the_rows_and_the_page_without_the_esbs_urls(): void
    {
        $this->fakeUpstream();

        $this->index(['page' => 2])
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('inventory/index')
                ->where('kind.value', $this->kind()->value)
                ->where('kind.statuses', array_map(
                    static fn (int $id, string $name): array => ['id' => $id, 'name' => $name],
                    array_keys($this->kind()->statuses()),
                    $this->kind()->statuses(),
                ))
                ->where('documents.0.number', $this->number())
                ->where('pagination.page', 2)
                ->where('pagination.count', 45)
                ->missing('pagination.next')
                ->missing('pagination.prev'));
    }

    #[Test]
    public function filters_reach_the_esb_under_its_names_and_empty_ones_are_dropped(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->index([
            'number' => 'X1',
            'dateFrom' => '2026-03-01',
            'dateTo' => '2026-03-31',
            'statusId' => 3,
            'branchId' => 7,
            'search' => '',
        ])->assertOk();

        parse_str((string) parse_url($sent[0], PHP_URL_QUERY), $query);
        $expected = [
            $this->numberParam() => 'X1',
            'dateFrom' => '2026-03-01',
            'dateTo' => '2026-03-31',
            'statusID' => '3',
            ...($this->branchParam() === null ? [] : [$this->branchParam() => '7']),
            'page' => '1',
            'limit' => '20',
        ];
        ksort($expected);
        ksort($query);

        $this->assertSame($expected, $query);
        $this->assertStringStartsWith('/core-int'.$this->upstreamPath().'?', $sent[0]);
    }

    #[Test]
    public function bad_filters_never_reach_the_esb(): void
    {
        Http::fake();

        foreach ([
            ['limit' => 101],
            ['dateFrom' => '01/03/2026'],
            ['dateFrom' => '2026-03-31', 'dateTo' => '2026-03-01'],
            ['statusId' => 'x'],
        ] as $query) {
            $this->index($query)->assertSessionHasErrors();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_detail_shows_the_document(): void
    {
        $this->fakeUpstream();

        $this->show($this->number())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('inventory/show')
                ->where('kind.value', $this->kind()->value)
                ->where('document.number', $this->number())
                ->has('document.lines', 1));
    }

    #[Test]
    public function an_unknown_document_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreInventory::notFoundResponse($this->upstreamPath().'/'.$this->number()), 400)]);

        $this->show($this->number())->assertNotFound();
    }

    #[Test]
    public function a_number_that_would_reach_a_literal_vendor_path_never_reaches_the_esb(): void
    {
        Http::fake();
        $base = parse_url(route($this->kind()->value.'.index'), PHP_URL_PATH);

        foreach (['initialize', 'create', 'detail', 'export', rawurlencode('1/2'), '..'] as $number) {
            $response = $this->actingAs($this->operator())->get($base.'/'.$number);

            // For the kinds created here, "create" is the gateway's own form
            // (S3), which needs <kind>.manage -- still never a document read.
            $number === 'create' && $this->kind()->editable()
                ? $response->assertForbidden()
                : $response->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
