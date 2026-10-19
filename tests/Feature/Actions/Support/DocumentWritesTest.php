<?php

namespace Tests\Feature\Actions\Support;

use App\Actions\Support\ApprovableDocument;
use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The decide/delete rules shared by every approvable document.
 *
 * Purchase order audit actions stand in for "any document"; the per-module
 * feature tests prove each module is wired to its own.
 */
class DocumentWritesTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = 'PO-0001';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function document(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'purchase-order',
            authorized: AuditAction::PurchaseOrderAuthorized,
            rejected: AuditAction::PurchaseOrderRejected,
            deleted: AuditAction::PurchaseOrderDeleted,
            writeUnconfirmed: AuditAction::PurchaseOrderWriteUnconfirmed,
        );
    }

    private function writes(): DocumentWrites
    {
        return $this->app->make(DocumentWrites::class);
    }

    private function timeout(): EsbTimeoutException
    {
        return new EsbTimeoutException(new EsbContext(correlationId: 'corr-123', operation: 'test'));
    }

    private function refusal(): EsbBusinessRejectionException
    {
        return new EsbBusinessRejectionException(new EsbContext(correlationId: 'corr-456', operation: 'test'));
    }

    // ------------------------------------------------------------- lock key

    #[Test]
    public function the_lock_key_is_per_document_and_matches_the_existing_scheme(): void
    {
        $this->assertSame('purchase-order-write:PO-0001', $this->document()->lock(self::NUMBER));
    }

    // -------------------------------------------------------------- decide

    #[Test]
    public function an_authorization_drops_the_reason_and_is_audited(): void
    {
        $received = 'not called';

        $this->writes()->decide(
            $this->document(), 'esb', self::NUMBER, ApprovalDecision::Authorize, 'ignored',
            function (?string $reason) use (&$received): void {
                $received = $reason;
            },
        );

        $this->assertNull($received);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.authorized', $audit->action);
        $this->assertSame(['provider' => 'esb', 'number' => self::NUMBER], $audit->metadata);
    }

    #[Test]
    public function a_rejection_forwards_and_audits_its_reason(): void
    {
        $received = null;

        $this->writes()->decide(
            $this->document(), 'esb', self::NUMBER, ApprovalDecision::Reject, 'Wrong supplier',
            function (?string $reason) use (&$received): void {
                $received = $reason;
            },
        );

        $this->assertSame('Wrong supplier', $received);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.rejected', $audit->action);
        $this->assertSame(
            ['provider' => 'esb', 'number' => self::NUMBER, 'reason' => 'Wrong supplier'],
            $audit->metadata,
        );
    }

    #[Test]
    public function a_rejection_without_a_reason_audits_no_reason_key(): void
    {
        $this->writes()->decide(
            $this->document(), 'esb', self::NUMBER, ApprovalDecision::Reject, null,
            function (?string $reason): void {},
        );

        $this->assertSame(['provider' => 'esb', 'number' => self::NUMBER], AuditLog::query()->sole()->metadata);
    }

    #[Test]
    public function an_ambiguous_decision_failure_is_audited_as_unconfirmed_and_rethrown(): void
    {
        $timeout = $this->timeout();

        try {
            $this->writes()->decide(
                $this->document(), 'esb', self::NUMBER, ApprovalDecision::Reject, 'Wrong supplier',
                fn (?string $reason) => throw $timeout,
            );
            $this->fail('The failure was swallowed.');
        } catch (EsbException $exception) {
            $this->assertSame($timeout, $exception);
        }

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.write-unconfirmed', $audit->action);
        $this->assertSame([
            'provider' => 'esb',
            'operation' => 'reject',
            'number' => self::NUMBER,
            'failure' => 'timeout',
            'correlation_id' => 'corr-123',
        ], $audit->metadata);
    }

    #[Test]
    public function a_definite_decision_failure_is_rethrown_without_an_audit(): void
    {
        $this->expectException(EsbBusinessRejectionException::class);

        try {
            $this->writes()->decide(
                $this->document(), 'esb', self::NUMBER, ApprovalDecision::Authorize, null,
                fn (?string $reason) => throw $this->refusal(),
            );
        } finally {
            $this->assertSame(0, AuditLog::query()->count());
        }
    }

    #[Test]
    public function a_decision_while_the_document_is_locked_never_runs_the_write(): void
    {
        $lock = Cache::lock('purchase-order-write:'.self::NUMBER, 60);
        $lock->get();
        $called = false;

        try {
            $this->writes()->decide(
                $this->document(), 'esb', self::NUMBER, ApprovalDecision::Authorize, null,
                function (?string $reason) use (&$called): void {
                    $called = true;
                },
            );
            $this->fail('The lock was ignored.');
        } catch (WriteAlreadyInFlight $exception) {
            $this->assertSame('purchase-order-write:'.self::NUMBER, $exception->lockKey);
        } finally {
            $lock->release();
        }

        $this->assertFalse($called);
        $this->assertSame(0, AuditLog::query()->count());
    }

    // -------------------------------------------------------------- delete

    #[Test]
    public function a_delete_is_audited(): void
    {
        $calls = 0;

        $this->writes()->delete($this->document(), 'esb', self::NUMBER, function () use (&$calls): void {
            $calls++;
        });

        $this->assertSame(1, $calls);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.deleted', $audit->action);
        $this->assertSame(['provider' => 'esb', 'number' => self::NUMBER], $audit->metadata);
    }

    #[Test]
    public function an_ambiguous_delete_failure_is_audited_as_unconfirmed_and_rethrown(): void
    {
        try {
            $this->writes()->delete($this->document(), 'esb', self::NUMBER, fn () => throw $this->timeout());
            $this->fail('The failure was swallowed.');
        } catch (EsbTimeoutException) {
        }

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.write-unconfirmed', $audit->action);
        $this->assertSame('delete', $audit->metadata['operation']);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
    }

    #[Test]
    public function the_lock_is_released_after_a_write_so_the_next_one_can_run(): void
    {
        $this->writes()->delete($this->document(), 'esb', self::NUMBER, function (): void {});
        $this->writes()->delete($this->document(), 'esb', self::NUMBER, function (): void {});

        $this->assertSame(2, AuditLog::query()->count());
    }
}
