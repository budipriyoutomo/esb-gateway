<?php

namespace Tests\Feature\Inventory;

use App\Authorization\Role;
use App\Domain\Canonical\InventoryKind;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;
use Tests\TestCase;

/**
 * Create / update of one inventory document (TODO-modules.md Â§3, S3,
 * [ESB-035]): esb.invoke + <kind>.manage, the vendor schema checked before
 * anything is sent, exactly one attempt, the ERP's field errors back on the
 * form (ADR-021), an unconfirmed write audited with its correlation id. An
 * update reads the document first and sends back what the form does not
 * show; a document that moves assets is not edited here.
 *
 * Each kind supplies its paths, a valid form and the bodies it must become.
 */
abstract class InventoryFormTestCase extends TestCase
{
    use RefreshDatabase;

    abstract protected function kind(): InventoryKind;

    /** e.g. "/simple-transfer". */
    abstract protected function upstreamPath(): string;

    abstract protected function number(): string;

    /** e.g. "simple-transfer". */
    abstract protected function auditPrefix(): string;

    /** 'PUT' or 'PATCH'. */
    abstract protected function updateMethod(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function validInput(): array;

    /**
     * The vendor body validInput() becomes on a create.
     *
     * @return array<string, mixed>
     */
    abstract protected function expectedCreateBody(): array;

    /**
     * The vendor body validInput() becomes on an update of detailResponse(),
     * with what the form does not show carried over.
     *
     * @return array<string, mixed>
     */
    abstract protected function expectedUpdateBody(): array;

    /**
     * An editable detail, carrying something the form does not show.
     *
     * @return array<string, mixed>
     */
    abstract protected function detailResponse(): array;

    /**
     * The same document moving assets, or null when the kind has none.
     *
     * @return array<string, mixed>|null
     */
    abstract protected function assetDetailResponse(): ?array;

    /**
     * What the vendor's create answers with the new number, or null when it
     * documents none.
     *
     * @return array<string, mixed>|null
     */
    abstract protected function createdResult(): ?array;

    /** The vendor's date field, for the field-error mapping. */
    abstract protected function vendorDateField(): string;

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

    protected function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    protected function manager(): User
    {
        $user = $this->userWithRole(Role::IntegrationOperator);
        $user->givePermissionTo($this->kind()->value.'.manage');

        return $user;
    }

    protected function upstreamUrl(string $suffix = ''): string
    {
        return 'https://stg7.esb.co.id/core-int'.$this->upstreamPath().$suffix;
    }

    /**
     * Answer the detail (and its extra reads) on GET, and $write on anything
     * else.
     *
     * @param  array<string, mixed>|null  $detail
     * @param  array<string, mixed>|null  $write
     */
    protected function fakeUpstream(?array $detail = null, ?array $write = null, int $writeStatus = 200): void
    {
        Http::fake(function (Request $request) use ($detail, $write, $writeStatus) {
            if ($request->method() !== 'GET') {
                return Http::response($write ?? CoreInventory::writeEnvelope(), $writeStatus);
            }

            if (str_contains($request->url(), '/stock-check/')) {
                return Http::response(CoreInventory::stockCheck(false, '/stock-check'));
            }

            return Http::response($detail ?? $this->detailResponse());
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function store(array $input, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->post(route($this->kind()->value.'.store'), $input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function update(array $input, ?User $user = null, ?string $number = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())
            ->put(route($this->kind()->value.'.update', $number ?? $this->number()), $input);
    }

    protected function showRoute(): string
    {
        return route($this->kind()->value.'.show', $this->number());
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function the_forms_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $approver = $this->userWithRole(Role::IntegrationOperator);
        $approver->givePermissionTo($this->kind()->value.'.approve');
        $withoutInvoke = $this->userWithRole(Role::Viewer);
        $withoutInvoke->givePermissionTo($this->kind()->value.'.manage');
        $otherKinds = $this->userWithRole(Role::IntegrationOperator);

        foreach (InventoryKind::cases() as $kind) {
            if ($kind !== $this->kind()) {
                $otherKinds->givePermissionTo($kind->value.'.manage');
            }
        }

        foreach ([$this->userWithRole(Role::IntegrationOperator), $approver, $withoutInvoke, $otherKinds] as $user) {
            $this->actingAs($user)->get(route($this->kind()->value.'.create'))->assertForbidden();
            $this->actingAs($user)->get(route($this->kind()->value.'.edit', $this->number()))->assertForbidden();
            $this->store($this->validInput(), $user)->assertForbidden();
            $this->update($this->validInput(), $user)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_create_form_is_the_shared_inventory_form(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route($this->kind()->value.'.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('inventory/form')
                ->where('kind.value', $this->kind()->value)
                ->where('document', null));

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- create

    #[Test]
    public function a_manager_can_create_the_document(): void
    {
        $created = $this->createdResult();
        $this->fakeUpstream(write: CoreInventory::writeEnvelope(result: $created ?? []));

        $manager = $this->manager();

        $response = $this->store($this->validInput(), $manager)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        // With the new number, its page; without one, the list.
        $response->assertRedirect($created === null ? route($this->kind()->value.'.index') : $this->showRoute());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === $this->upstreamUrl()
            && $request->data() === $this->expectedCreateBody());

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.created', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame('esb', $audit->metadata['provider']);
        $this->assertSame(count($this->validInput()['lines']), $audit->metadata['lines']);
        $this->assertSame($created === null ? null : $this->number(), $audit->metadata['number'] ?? null);
    }

    #[Test]
    public function a_form_without_lines_or_a_valid_date_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->store([...$this->validInput(), 'lines' => []])->assertSessionHasErrors('lines');
        $this->store([...$this->validInput(), 'date' => '20-03-2026'])->assertSessionHasErrors('date');
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 'many']]])
            ->assertSessionHasErrors('lines.0.qty');
        $this->store([...$this->validInput(), 'notes' => str_repeat('x', 1001)])->assertSessionHasErrors('notes');

        Http::assertNothingSent();
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function the_erps_field_errors_land_on_the_form(): void
    {
        $this->fakeUpstream(write: [
            ...CoreInventory::writeEnvelope('EC03100003', 'Validation Error', 'fail'),
            'errors' => [['attribute' => $this->vendorDateField(), 'code' => 'EC03100003', 'message' => 'Must be after the active stock period']],
        ], writeStatus: 400);

        $this->store($this->validInput())
            ->assertSessionHasErrors(['date' => 'Must be after the active stock period']);

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unconfirmed_create_is_audited_and_lands_on_the_list(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->store($this->validInput())
            ->assertRedirect(route($this->kind()->value.'.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.write-unconfirmed', $audit->action);
        $this->assertSame('create', $audit->metadata['operation']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function one_create_per_person_at_a_time(): void
    {
        Http::fake();

        $manager = $this->manager();
        $lock = Cache::lock($this->auditPrefix().'-create:'.$manager->id, 60);
        $lock->get();

        try {
            $this->store($this->validInput(), $manager)
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- update

    #[Test]
    public function the_edit_form_reads_the_document(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->manager())
            ->get(route($this->kind()->value.'.edit', $this->number()))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('inventory/form')
                ->where('kind.value', $this->kind()->value)
                ->where('document.number', $this->number()));

        Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
    }

    #[Test]
    public function editing_an_unknown_or_unusable_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreInventory::notFoundResponse($this->upstreamPath().'/'.$this->number()), 400)]);

        $this->actingAs($this->manager())->get(route($this->kind()->value.'.edit', $this->number()))->assertNotFound();
        $this->actingAs($this->manager())->get(route($this->kind()->value.'.edit', 'detail'))->assertNotFound();
        $this->update($this->validInput(), number: 'initialize')->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_document_that_moves_assets_is_not_edited_here(): void
    {
        $assets = $this->assetDetailResponse();

        if ($assets === null) {
            $this->markTestSkipped('This document has no assets.');
        }

        $this->fakeUpstream($assets);

        $this->actingAs($this->manager())
            ->get(route($this->kind()->value.'.edit', $this->number()))
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->update($this->validInput())
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_manager_can_update_the_document_and_what_the_form_does_not_show_is_kept(): void
    {
        $this->fakeUpstream();

        $manager = $this->manager();

        $this->update($this->validInput(), $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSent(fn (Request $request): bool => $request->method() === $this->updateMethod()
            && $request->url() === $this->upstreamUrl('/'.$this->number())
            && $request->data() === $this->expectedUpdateBody());
        $this->assertCount(1, Http::recorded(fn (Request $request): bool => $request->method() !== 'GET'));

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.updated', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame($this->number(), $audit->metadata['number']);
    }

    #[Test]
    public function an_update_is_sent_once_even_when_unconfirmed(): void
    {
        $writes = 0;
        Http::fake(function (Request $request) use (&$writes) {
            if ($request->method() === 'GET') {
                return str_contains($request->url(), '/stock-check/')
                    ? Http::response(CoreInventory::stockCheck(false, '/stock-check'))
                    : Http::response($this->detailResponse());
            }

            $writes++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->update($this->validInput())
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $writes);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.write-unconfirmed', $audit->action);
        $this->assertSame('update', $audit->metadata['operation']);
        $this->assertSame($this->number(), $audit->metadata['number']);
    }

    #[Test]
    public function an_update_shares_the_documents_write_lock(): void
    {
        $this->fakeUpstream();

        $lock = Cache::lock($this->auditPrefix().'-write:'.$this->number(), 60);
        $lock->get();

        try {
            $this->update($this->validInput())
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
    }
}
