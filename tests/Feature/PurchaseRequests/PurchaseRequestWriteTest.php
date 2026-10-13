<?php

namespace Tests\Feature\PurchaseRequests;

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Create, update, and delete a purchase request.
 *
 * POST /purchase/purchase-request, PUT and DELETE
 * /purchase/purchase-request/{purchaseRequestNum}. None of them is documented
 * as idempotent, so the rules from the decision slice carry over: one attempt,
 * a definite refusal reported as one, an ambiguous failure reported as
 * "check before retrying", and one write per document at a time.
 */
class PurchaseRequestWriteTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = 'PR202603260005';

    private const BASE = 'https://stg7.esb.co.id/core-int/purchase/purchase-request';

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
            'timestamp' => '2026-03-26 10:30:00',
            'status' => $code === 'EC03100000' ? 'ok' : 'fail',
            'code' => $code,
            'message' => $message,
            'result' => $result,
            'errors' => $errors,
        ];
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
            'branchId' => 3,
            'date' => '2026-03-26',
            'requiredDate' => '2026-03-28',
            'templateId' => 7,
            'notes' => 'Restock bar',
            'lines' => [
                ['productDetailId' => 901, 'requestProcessId' => 1, 'qty' => 24.5, 'notes' => 'cold'],
                ['productDetailId' => 902, 'requestProcessId' => 1, 'qty' => 3],
            ],
            ...$overrides,
        ];
    }

    // ================================================================ create

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->post(route('purchase-requests.store'), $this->payload())->assertRedirect(route('login'));
        $this->put(route('purchase-requests.update', self::NUMBER), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('purchase-requests.destroy', self::NUMBER))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_data_is_not_enough_to_write_purchase_requests(): void
    {
        Http::fake();

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)->post(route('purchase-requests.store'), $this->payload())->assertForbidden();
        $this->actingAs($operator)->put(route('purchase-requests.update', self::NUMBER), $this->payload())->assertForbidden();
        $this->actingAs($operator)->delete(route('purchase-requests.destroy', self::NUMBER))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_manager_can_create_a_purchase_request(): void
    {
        Http::fake(['*' => Http::response($this->envelope(result: ['purchaseRequestNum' => 'PR202603260099']))]);

        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('purchase-requests.store'), $this->payload())
            ->assertRedirect(route('purchase-requests.show', 'PR202603260099'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::BASE
            && $request->data() === [
                'branchID' => 3,
                'purchaseRequestDate' => '2026-03-26',
                'requiredDate' => '2026-03-28',
                'isTemplate' => false,
                'purchaseRequestDetails' => [
                    ['productDetailID' => 901, 'requestProcessID' => 1, 'qty' => 24.5, 'notes' => 'cold'],
                    ['productDetailID' => 902, 'requestProcessID' => 1, 'qty' => 3.0],
                ],
                'requestTemplateID' => 7,
                'additionalInfo' => 'Restock bar',
            ]);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.created', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame('PR202603260099', $audit->metadata['number']);
        $this->assertSame(2, $audit->metadata['lines']);

        $this->assertSame('purchase-request.create', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function a_create_without_a_usable_number_in_the_answer_lands_on_the_list(): void
    {
        // The ESB said OK, so the request exists; the placeholder example
        // just does not promise a number back. Never link to a guessed one.
        Http::fake(['*' => Http::response($this->envelope(result: ['purchaseRequestNum' => '../evil']))]);

        $this->actingAs($this->manager())
            ->post(route('purchase-requests.store'), $this->payload())
            ->assertRedirect(route('purchase-requests.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame('purchase-request.created', AuditLog::query()->sole()->action);
        $this->assertNull(AuditLog::query()->sole()->metadata['number']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'no branch' => [['branchId' => null], 'branchId'],
            'date not Y-m-d' => [['date' => '26/03/2026'], 'date'],
            'required before request date' => [['requiredDate' => '2026-03-25'], 'requiredDate'],
            'no lines' => [['lines' => []], 'lines'],
            'zero quantity' => [['lines' => [['productDetailId' => 901, 'requestProcessId' => 1, 'qty' => 0]]], 'lines.0.qty'],
            'line without process' => [['lines' => [['productDetailId' => 901, 'qty' => 1]]], 'lines.0.requestProcessId'],
            'notes too long' => [['notes' => str_repeat('x', 1001)], 'notes'],
            'line notes too long' => [['lines' => [['productDetailId' => 901, 'requestProcessId' => 1, 'qty' => 1, 'notes' => str_repeat('x', 101)]]], 'lines.0.notes'],
            // The vendor declares the lines uniqueItems.
            'duplicate line' => [['lines' => [
                ['productDetailId' => 901, 'requestProcessId' => 1, 'qty' => 1],
                ['productDetailId' => 901, 'requestProcessId' => 1, 'qty' => 2],
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
            ->post(route('purchase-requests.store'), $this->payload($overrides))
            ->assertSessionHasErrors($errorKey);

        Http::assertNothingSent();
    }

    #[Test]
    public function the_esbs_field_errors_come_back_as_form_errors(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100003', 'Validation Error', errors: [
            ['attribute' => 'requiredDate', 'field' => 'requiredDate', 'code' => 'EC03100003', 'message' => 'requiredDate must be after today'],
            ['attribute' => 'somethingElse', 'field' => 'somethingElse', 'code' => 'EC03100003', 'message' => 'template is inactive'],
        ]), 400)]);

        $this->actingAs($this->manager())
            ->from(route('purchase-requests.create'))
            ->post(route('purchase-requests.store'), $this->payload())
            ->assertRedirect(route('purchase-requests.create'))
            ->assertSessionHasErrors([
                'requiredDate' => 'requiredDate must be after today',
                'form' => 'template is inactive',
            ]);

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_validation_refusal_without_detail_still_says_so(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100003', 'Validation Error'), 400)]);

        $this->actingAs($this->manager())
            ->from(route('purchase-requests.create'))
            ->post(route('purchase-requests.store'), $this->payload())
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
            ->post(route('purchase-requests.store'), $this->payload())
            ->assertRedirect(route('purchase-requests.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.write-unconfirmed', $audit->action);
        $this->assertSame('create', $audit->metadata['operation']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function a_second_create_from_the_same_person_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        $manager = $this->manager();

        $lock = Cache::lock('purchase-request-create:'.$manager->id, 60);
        $lock->get();

        try {
            $this->actingAs($manager)
                ->post(route('purchase-requests.store'), $this->payload())
                ->assertRedirect(route('purchase-requests.index'))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    // ================================================================ update

    #[Test]
    public function a_manager_can_update_a_purchase_request(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $manager = $this->manager();

        $this->actingAs($manager)
            ->put(route('purchase-requests.update', self::NUMBER), $this->payload([
                'costCenterId' => 5,
                'projectId' => null,
                'isTemplate' => false,
            ]))
            ->assertRedirect(route('purchase-requests.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === self::BASE.'/'.self::NUMBER
            && $request['costCenterID'] === 5
            && ! array_key_exists('projectID', $request->data())
            && count($request['purchaseRequestDetails']) === 2);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.updated', $audit->action);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
        $this->assertSame('purchase-request.update', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function updating_an_unknown_document_is_a_404(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100004', 'Not Found'), 400)]);

        $this->actingAs($this->manager())
            ->put(route('purchase-requests.update', self::NUMBER), $this->payload())
            ->assertNotFound();

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_timed_out_update_lands_on_the_document_to_check_it(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->actingAs($this->manager())
            ->put(route('purchase-requests.update', self::NUMBER), $this->payload())
            ->assertRedirect(route('purchase-requests.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.write-unconfirmed', $audit->action);
        $this->assertSame('update', $audit->metadata['operation']);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
    }

    #[Test]
    public function any_write_on_a_document_waits_for_the_one_in_flight(): void
    {
        // An update racing a decision or a delete on the same document is
        // two non-idempotent writes whose order nobody chose.
        Http::fake();

        $lock = Cache::lock('purchase-request-write:'.self::NUMBER, 60);
        $lock->get();

        try {
            $manager = $this->manager();

            $this->actingAs($manager)
                ->put(route('purchase-requests.update', self::NUMBER), $this->payload())
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

            $this->actingAs($manager)
                ->delete(route('purchase-requests.destroy', self::NUMBER))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

            $this->actingAs($manager)
                ->post(route('purchase-requests.decisions.store', self::NUMBER), ['decision' => 'authorize'])
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $manager = $this->manager();

        $this->actingAs($manager)->put('/purchase-requests/PR1%2F..%2Fauthorize', $this->payload())->assertNotFound();
        $this->actingAs($manager)->delete('/purchase-requests/PR1%2F..%2Fauthorize')->assertNotFound();

        Http::assertNothingSent();
    }

    // ================================================================ delete

    #[Test]
    public function a_manager_can_delete_a_purchase_request(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $manager = $this->manager();

        $this->actingAs($manager)
            ->delete(route('purchase-requests.destroy', self::NUMBER))
            ->assertRedirect(route('purchase-requests.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === self::BASE.'/'.self::NUMBER);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.deleted', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
    }

    #[Test]
    public function a_refused_delete_is_reported_on_the_document(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100029', 'document already processed'), 400)]);

        $this->actingAs($this->manager())
            ->delete(route('purchase-requests.destroy', self::NUMBER))
            ->assertRedirect(route('purchase-requests.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_timed_out_delete_is_reported_as_unconfirmed(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        // Not the detail page: if the delete went through, it is a 404.
        $this->actingAs($this->manager())
            ->delete(route('purchase-requests.destroy', self::NUMBER))
            ->assertRedirect(route('purchase-requests.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.write-unconfirmed', $audit->action);
        $this->assertSame('delete', $audit->metadata['operation']);
    }
}
