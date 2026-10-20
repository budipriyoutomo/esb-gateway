<?php

namespace Tests\Feature\MasterData;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Database\Seeders\RolePermissionSeeder;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Delete / deactivate / restore on a master data record (TODO-modules.md
 * §2, S2): esb.invoke + <module>.manage, one attempt at the write, a lock
 * per record, an audit row, and an unconfirmed outcome audited with its
 * correlation id. Each module supplies its routes and upstream paths.
 */
abstract class MasterDataWriteTestCase extends TestCase
{
    use RefreshDatabase;

    protected const BASE_URL = 'https://stg7.esb.co.id/core-int';

    /** Route name prefix, e.g. "units". */
    abstract protected function resource(): string;

    /** The upstream record path, e.g. "/units/4". */
    abstract protected function upstreamPath(): string;

    abstract protected function sampleId(): int|string;

    /** Audit action prefix and lock prefix, e.g. "unit". */
    abstract protected function prefix(): string;

    /**
     * Supported operations: gateway operation => [HTTP verb, upstream method,
     * upstream path suffix].
     *
     * @return array<string, array{string, string, string}>
     */
    abstract protected function operations(): array;

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

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function envelope(mixed $result = null): array
    {
        return [
            'path' => self::BASE_URL.$this->upstreamPath(),
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $result,
            'errors' => null,
        ];
    }

    /**
     * Fake the ESB: $write answers the write itself. A module with a
     * pre-check overrides this to answer the pre-check first.
     *
     * @param  Closure(Request): (PromiseInterface|never)  $write
     */
    protected function fakeUpstream(Closure $write): void
    {
        Http::fake(fn (Request $request) => $write($request));
    }

    protected function send(string $operation, ?User $user = null): TestResponse
    {
        [$verb] = $this->operations()[$operation];
        $routeName = $this->resource().'.'.($operation === 'delete' ? 'destroy' : $operation);

        return $this->actingAs($user ?? $this->manager())
            ->call($verb, route($routeName, $this->sampleId()), ['name' => 'Sample']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function operationNames(): iterable
    {
        foreach (['delete', 'deactivate', 'restore'] as $operation) {
            yield $operation => [$operation];
        }
    }

    private function skipUnsupported(string $operation): void
    {
        if (! isset($this->operations()[$operation])) {
            $this->markTestSkipped("{$operation} is not offered by this module.");
        }
    }

    #[Test]
    #[DataProvider('operationNames')]
    public function it_needs_the_manage_permission(string $operation): void
    {
        $this->skipUnsupported($operation);
        Http::fake();

        // Reading ERP data (esb.invoke) is not enough.
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);

        $this->send($operation, $operator)->assertForbidden();
        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('operationNames')]
    public function it_sends_the_write_once_and_audits_it(string $operation): void
    {
        $this->skipUnsupported($operation);
        [, $method, $suffix] = $this->operations()[$operation];
        $writes = [];
        $this->fakeUpstream(function (Request $request) use (&$writes) {
            $writes[] = $request->method().' '.$request->url();

            return Http::response($this->envelope());
        });

        $response = $this->send($operation)->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $operation === 'delete'
            ? $response->assertRedirect(route($this->resource().'.index'))
            : $response->assertRedirect(route($this->resource().'.show', $this->sampleId()));

        $this->assertSame([$method.' '.self::BASE_URL.$this->upstreamPath().$suffix], $writes);

        $past = ['delete' => 'deleted', 'deactivate' => 'deactivated', 'restore' => 'restored'][$operation];
        $audit = AuditLog::query()->sole();
        $this->assertSame($this->prefix().'.'.$past, $audit->action);
        $this->assertSame($this->sampleId(), $audit->metadata['id']);
    }

    #[Test]
    #[DataProvider('operationNames')]
    public function a_timeout_is_not_retried_and_is_audited_as_unconfirmed(string $operation): void
    {
        $this->skipUnsupported($operation);
        $attempts = 0;
        $this->fakeUpstream(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->send($operation)->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $audit = AuditLog::query()->sole();
        $this->assertSame($this->prefix().'.write-unconfirmed', $audit->action);
        $this->assertSame($operation, $audit->metadata['operation']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    #[DataProvider('operationNames')]
    public function a_refusal_shows_the_erps_reason_and_audits_nothing(string $operation): void
    {
        $this->skipUnsupported($operation);
        $this->fakeUpstream(fn () => Http::response([
            'status' => 'fail',
            'code' => 'EC03100003',
            'message' => 'Validation Error',
            'result' => null,
            'errors' => [['field' => 'name', 'code' => 'EC03100003', 'message' => 'Already used by another record']],
        ], 400));

        $this->send($operation)
            ->assertRedirect(route($this->resource().'.show', $this->sampleId()))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        $this->assertStringContainsString('Already used by another record', (string) session('inertia.flash_data.toast.message'));
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    #[DataProvider('operationNames')]
    public function a_second_write_while_one_is_in_flight_is_refused(string $operation): void
    {
        $this->skipUnsupported($operation);
        $writes = 0;
        $this->fakeUpstream(function () use (&$writes) {
            $writes++;

            return Http::response($this->envelope());
        });
        $lock = Cache::lock($this->prefix().'-write:'.$this->sampleId(), 60);
        $lock->get();

        try {
            $this->send($operation)->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        $this->assertSame(0, $writes);
    }

    #[Test]
    public function an_unknown_record_is_a_404(): void
    {
        $this->fakeUpstream(fn () => Http::response([
            'status' => 'fail', 'code' => 'EC03100004', 'message' => 'Not Found', 'result' => null, 'errors' => null,
        ], 400));

        $this->send((string) array_key_first($this->operations()))->assertNotFound();
        $this->assertSame(0, AuditLog::query()->count());
    }

    /**
     * Ids the route must refuse before anything reaches the ESB.
     *
     * @return list<string>
     */
    protected function invalidIds(): array
    {
        return ['abc', '0', '-1', rawurlencode('1/2')];
    }

    #[Test]
    public function an_id_the_route_does_not_accept_never_reaches_the_esb(): void
    {
        Http::fake();
        $operation = (string) array_key_first($this->operations());
        [$verb] = $this->operations()[$operation];
        $base = parse_url(route($this->resource().'.index'), PHP_URL_PATH);
        $suffix = $operation === 'delete' ? '' : '/'.$operation;

        foreach ($this->invalidIds() as $id) {
            $this->actingAs($this->manager())->call($verb, $base.'/'.$id.$suffix)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
