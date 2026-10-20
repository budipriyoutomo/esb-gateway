<?php

namespace Tests\Feature\MasterData;

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
use Tests\TestCase;

/**
 * Create / update of a master data record (TODO-modules.md §2, S3):
 * esb.invoke + <module>.manage, the form validated before anything reaches
 * the ESB, the vendor's own field names on the wire, one attempt, an audit
 * row, the ERP's field errors back on the form, and an unconfirmed create
 * audited with its correlation id. Each module supplies its fields and paths.
 */
abstract class MasterDataFormTestCase extends TestCase
{
    use RefreshDatabase;

    protected const BASE_URL = 'https://stg7.esb.co.id/core-int';

    /** Route name prefix, e.g. "units". */
    abstract protected function resource(): string;

    /** Audit action prefix, e.g. "unit". */
    abstract protected function prefix(): string;

    /** The Inertia page, e.g. "units/form". */
    abstract protected function pageComponent(): string;

    /** The page prop holding the record being edited, e.g. "unit". */
    abstract protected function recordProp(): string;

    abstract protected function sampleId(): int;

    /** e.g. "/units". */
    abstract protected function createPath(): string;

    /** e.g. "/units/4". */
    abstract protected function updatePath(): string;

    /** The ESB detail response of sampleId(), for the edit page. */
    abstract protected function detailResponse(): array;

    /**
     * A complete, valid form submission; its "name" is what the audit and
     * the toast use.
     *
     * @return array<string, mixed>
     */
    abstract protected function validInput(): array;

    /**
     * The JSON body the ESB should receive for validInput().
     *
     * @return array<string, mixed>
     */
    abstract protected function expectedBody(): array;

    /**
     * Submissions the gateway must refuse itself: [input overrides, the form
     * field that gets the error].
     *
     * @return list<array{array<string, mixed>, string}>
     */
    abstract protected function invalidInputs(): array;

    /**
     * A vendor request field and the form field its errors belong on.
     *
     * @return array{string, string}
     */
    abstract protected function vendorFieldError(): array;

    protected function updateVerb(): string
    {
        return 'PUT';
    }

    /**
     * @return array<string, mixed>
     */
    protected function expectedUpdateBody(): array
    {
        return $this->expectedBody();
    }

    /** The create's envelope result. Most of the vendor's return nothing. */
    protected function createResult(): mixed
    {
        return [];
    }

    /** Where a successful create lands. */
    protected function createdLanding(): string
    {
        return route($this->resource().'.index');
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

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function envelope(string $path, mixed $result = null): array
    {
        return [
            'path' => self::BASE_URL.$path,
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $result,
            'errors' => null,
        ];
    }

    #[Test]
    public function the_form_and_its_writes_need_the_manage_permission(): void
    {
        Http::fake();
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $this->actingAs($operator);

        $this->get(route($this->resource().'.create'))->assertForbidden();
        $this->get(route($this->resource().'.edit', $this->sampleId()))->assertForbidden();
        $this->post(route($this->resource().'.store'), $this->validInput())->assertForbidden();
        $this->put(route($this->resource().'.update', $this->sampleId()), $this->validInput())->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_create_page_is_an_empty_form(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route($this->resource().'.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($this->pageComponent())
                ->where($this->recordProp(), null));

        Http::assertNothingSent();
    }

    #[Test]
    public function the_edit_page_is_filled_from_the_erp(): void
    {
        Http::fake(['*' => Http::response($this->detailResponse())]);

        $this->actingAs($this->manager())
            ->get(route($this->resource().'.edit', $this->sampleId()))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component($this->pageComponent())
                ->where($this->recordProp().'.id', $this->sampleId()));
    }

    #[Test]
    public function editing_an_unknown_record_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'fail', 'code' => 'EC03100004', 'message' => 'Not Found', 'result' => null, 'errors' => null,
        ], 400)]);

        $this->actingAs($this->manager())
            ->get(route($this->resource().'.edit', $this->sampleId()))
            ->assertNotFound();
    }

    #[Test]
    public function a_create_is_sent_once_with_the_vendors_fields_and_audited(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = [$request->method().' '.$request->url(), $request->data()];

            return Http::response($this->envelope($this->createPath(), $this->createResult()));
        });

        $this->actingAs($this->manager())
            ->post(route($this->resource().'.store'), $this->validInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->createdLanding())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame([['POST '.self::BASE_URL.$this->createPath(), $this->expectedBody()]], $sent);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->prefix().'.created', $audit->action);
        $this->assertSame($this->validInput()['name'], $audit->metadata['name']);
    }

    #[Test]
    public function an_update_is_sent_once_with_the_vendors_fields_and_audited(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = [$request->method().' '.$request->url(), $request->data()];

            return Http::response($this->envelope($this->updatePath()));
        });

        $this->actingAs($this->manager())
            ->put(route($this->resource().'.update', $this->sampleId()), $this->validInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route($this->resource().'.show', $this->sampleId()))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame([[$this->updateVerb().' '.self::BASE_URL.$this->updatePath(), $this->expectedUpdateBody()]], $sent);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->prefix().'.updated', $audit->action);
        $this->assertSame($this->sampleId(), $audit->metadata['id']);
    }

    #[Test]
    public function an_invalid_form_never_reaches_the_esb(): void
    {
        Http::fake();
        $this->actingAs($this->manager());

        foreach ($this->invalidInputs() as [$overrides, $field]) {
            $this->from(route($this->resource().'.create'))
                ->post(route($this->resource().'.store'), [...$this->validInput(), ...$overrides])
                ->assertRedirect(route($this->resource().'.create'))
                ->assertSessionHasErrors($field);
        }

        Http::assertNothingSent();
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function the_erps_field_errors_land_on_the_form(): void
    {
        [$vendorField, $formField] = $this->vendorFieldError();
        Http::fake(['*' => Http::response([
            'status' => 'fail',
            'code' => 'EC03100003',
            'message' => 'Validation Error',
            'result' => null,
            'errors' => [['attribute' => $vendorField, 'code' => 'EC03100003', 'message' => 'Already used by another record']],
        ], 400)]);

        $this->actingAs($this->manager())
            ->from(route($this->resource().'.edit', $this->sampleId()))
            ->put(route($this->resource().'.update', $this->sampleId()), $this->validInput())
            ->assertRedirect(route($this->resource().'.edit', $this->sampleId()))
            ->assertSessionHasErrors([$formField => 'Already used by another record']);

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_timed_out_create_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->manager())
            ->post(route($this->resource().'.store'), $this->validInput())
            ->assertRedirect(route($this->resource().'.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $audit = AuditLog::query()->sole();
        $this->assertSame($this->prefix().'.write-unconfirmed', $audit->action);
        $this->assertSame('create', $audit->metadata['operation']);
        $this->assertSame($this->validInput()['name'], $audit->metadata['name']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }
}
