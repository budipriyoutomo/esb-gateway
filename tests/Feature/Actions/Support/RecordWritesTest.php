<?php

namespace Tests\Feature\Actions\Support;

use App\Actions\Support\CreatedRecord;
use App\Actions\Support\MasterRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\RecordWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The delete / deactivate / restore rules shared by master data records.
 * Product category audit actions stand in for "any record".
 */
class RecordWritesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function record(): MasterRecord
    {
        return new MasterRecord(
            lockPrefix: 'product-category',
            audits: [
                RecordOperation::Delete->value => AuditAction::ProductCategoryDeleted,
                RecordOperation::Deactivate->value => AuditAction::ProductCategoryDeactivated,
                RecordOperation::Restore->value => AuditAction::ProductCategoryRestored,
                RecordOperation::Create->value => AuditAction::ProductCategoryCreated,
                RecordOperation::Update->value => AuditAction::ProductCategoryUpdated,
            ],
            writeUnconfirmed: AuditAction::ProductCategoryWriteUnconfirmed,
        );
    }

    private function writes(): RecordWrites
    {
        return $this->app->make(RecordWrites::class);
    }

    private function context(): EsbContext
    {
        return new EsbContext(correlationId: 'corr-1', operation: 'test');
    }

    #[Test]
    public function the_lock_is_per_record(): void
    {
        $this->assertSame('product-category-write:3', $this->record()->lock(3));
        $this->assertSame('product-category-write:1101', $this->record()->lock('1101'));
    }

    #[Test]
    public function a_write_is_audited_with_its_operation_and_any_detail_it_returns(): void
    {
        $this->writes()->run($this->record(), 'esb', 3, RecordOperation::Delete, fn () => ['hardDelete' => true]);
        $this->writes()->run($this->record(), 'esb', 3, RecordOperation::Restore, fn () => null);

        $audits = AuditLog::query()->orderBy('id')->get();
        $this->assertSame('product-category.deleted', $audits[0]->action);
        $this->assertSame(['provider' => 'esb', 'id' => 3, 'hardDelete' => true], $audits[0]->metadata);
        $this->assertSame('product-category.restored', $audits[1]->action);
        $this->assertSame(['provider' => 'esb', 'id' => 3], $audits[1]->metadata);
    }

    #[Test]
    public function an_operation_the_record_does_not_support_is_refused_before_any_call(): void
    {
        $record = new MasterRecord(
            lockPrefix: 'unit',
            audits: [RecordOperation::Delete->value => AuditAction::UnitDeleted],
            writeUnconfirmed: AuditAction::UnitWriteUnconfirmed,
        );
        $called = false;

        try {
            $this->writes()->run($record, 'esb', 4, RecordOperation::Deactivate, function () use (&$called) {
                $called = true;
            });
            $this->fail('An unsupported operation ran.');
        } catch (\LogicException) {
        }

        $this->assertFalse($called);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_ambiguous_failure_is_audited_as_unconfirmed_and_rethrown(): void
    {
        try {
            $this->writes()->run($this->record(), 'esb', 3, RecordOperation::Deactivate, fn () => throw new EsbTimeoutException($this->context()));
            $this->fail('The failure was swallowed.');
        } catch (EsbTimeoutException) {
        }

        $audit = AuditLog::query()->sole();
        $this->assertSame('product-category.write-unconfirmed', $audit->action);
        $this->assertSame([
            'provider' => 'esb',
            'operation' => 'deactivate',
            'id' => 3,
            'failure' => 'timeout',
            'correlation_id' => 'corr-1',
        ], $audit->metadata);
    }

    #[Test]
    public function a_definite_refusal_is_rethrown_without_an_audit(): void
    {
        try {
            $this->writes()->run($this->record(), 'esb', 3, RecordOperation::Delete, fn () => throw new EsbBusinessRejectionException($this->context()));
        } catch (EsbBusinessRejectionException) {
        }

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_write_while_the_record_is_locked_never_runs(): void
    {
        $lock = Cache::lock('product-category-write:3', 60);
        $lock->get();
        $called = false;

        try {
            $this->writes()->run($this->record(), 'esb', 3, RecordOperation::Delete, function () use (&$called) {
                $called = true;
            });
            $this->fail('The lock was ignored.');
        } catch (WriteAlreadyInFlight) {
        } finally {
            $lock->release();
        }

        $this->assertFalse($called);
    }

    #[Test]
    public function an_update_is_audited_like_any_other_write(): void
    {
        $this->writes()->run($this->record(), 'esb', 3, RecordOperation::Update, fn () => ['fields' => ['name']]);

        $audit = AuditLog::query()->sole();
        $this->assertSame('product-category.updated', $audit->action);
        $this->assertSame(['provider' => 'esb', 'id' => 3, 'fields' => ['name']], $audit->metadata);
    }

    #[Test]
    public function a_create_is_locked_per_person_and_audited_with_its_detail(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertSame('product-category-create:'.$user->id, $this->record()->createLock($user->id));

        $this->writes()->create($this->record(), 'esb', fn () => null, ['name' => 'Bahan Baku']);

        $audit = AuditLog::query()->sole();
        $this->assertSame('product-category.created', $audit->action);
        $this->assertSame(['provider' => 'esb', 'name' => 'Bahan Baku'], $audit->metadata);
    }

    #[Test]
    public function a_create_returns_and_audits_the_new_id_when_the_erp_gives_one(): void
    {
        $this->actingAs(User::factory()->create());

        $id = $this->writes()->create($this->record(), 'esb', fn () => 12, ['name' => 'Bahan Baku']);

        $this->assertSame(12, $id);
        $this->assertSame(['provider' => 'esb', 'id' => 12, 'name' => 'Bahan Baku'], AuditLog::query()->sole()->metadata);
    }

    #[Test]
    public function a_create_waiting_for_approval_in_the_erp_is_audited_as_such(): void
    {
        $this->actingAs(User::factory()->create());

        $created = $this->writes()->create($this->record(), 'esb', fn () => new CreatedRecord(50, pendingApproval: true), ['name' => 'Beras']);

        $this->assertEquals(new CreatedRecord(50, pendingApproval: true), $created);
        $this->assertSame(
            ['provider' => 'esb', 'id' => 50, 'pending_approval' => true, 'name' => 'Beras'],
            AuditLog::query()->sole()->metadata,
        );
    }

    #[Test]
    public function a_second_create_by_the_same_person_while_one_is_in_flight_never_runs(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $lock = Cache::lock('product-category-create:'.$user->id, 60);
        $lock->get();
        $called = false;

        try {
            $this->writes()->create($this->record(), 'esb', function () use (&$called) {
                $called = true;
            }, []);
            $this->fail('The lock was ignored.');
        } catch (WriteAlreadyInFlight) {
        } finally {
            $lock->release();
        }

        $this->assertFalse($called);
    }

    #[Test]
    public function an_ambiguous_create_is_audited_as_unconfirmed_with_its_detail(): void
    {
        $this->actingAs(User::factory()->create());

        try {
            $this->writes()->create($this->record(), 'esb', fn () => throw new EsbTimeoutException($this->context()), ['name' => 'Bahan Baku']);
            $this->fail('The failure was swallowed.');
        } catch (EsbTimeoutException) {
        }

        $audit = AuditLog::query()->sole();
        $this->assertSame('product-category.write-unconfirmed', $audit->action);
        $this->assertSame([
            'provider' => 'esb',
            'operation' => 'create',
            'id' => null,
            'failure' => 'timeout',
            'correlation_id' => 'corr-1',
            'name' => 'Bahan Baku',
        ], $audit->metadata);
    }

    #[Test]
    public function a_record_without_create_refuses_it_before_any_call(): void
    {
        $record = new MasterRecord(
            lockPrefix: 'unit',
            audits: [RecordOperation::Delete->value => AuditAction::UnitDeleted],
            writeUnconfirmed: AuditAction::UnitWriteUnconfirmed,
        );

        $this->expectException(\LogicException::class);

        $this->writes()->create($record, 'esb', fn () => null, []);
    }
}
