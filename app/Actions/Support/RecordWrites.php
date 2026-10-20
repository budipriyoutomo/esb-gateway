<?php

namespace App\Actions\Support;

use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Closure;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Create, update, delete, deactivate or restore a master data record in the
 * ERP, on behalf of a person (TODO-modules.md §2, S2 and S3).
 *
 * The write rules are GuardedEsbWrite's: one write per record at a time,
 * one attempt, an ambiguous failure audited with its correlation id. The ERP
 * sees the shared service credential (ADR-009), so the audit row is the only
 * record of the actual person.
 */
class RecordWrites
{
    public function __construct(private readonly GuardedEsbWrite $guard) {}

    /**
     * @param  Closure(): (array<string, mixed>|null|void)  $write  Sends the write; may
     *                                                              return detail to audit.
     *
     * @throws WriteAlreadyInFlight when another write on this record is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     * @throws LogicException when the record does not support the operation
     */
    public function run(MasterRecord $record, string $provider, int|string $id, RecordOperation $operation, Closure $write): void
    {
        if (! $record->supports($operation)) {
            throw new LogicException("{$record->lockPrefix} does not support {$operation->value}.");
        }

        $detail = $this->guard->run(
            $record->lock($id),
            $write,
            function (EsbException $exception) use ($record, $provider, $operation, $id): void {
                AuditLog::record(
                    action: $record->writeUnconfirmed,
                    metadata: [
                        'provider' => $provider,
                        'operation' => $operation->value,
                        'id' => $id,
                        'failure' => $exception->kind(),
                        'correlation_id' => $exception->context->correlationId,
                    ],
                );
            },
        );

        AuditLog::record(
            action: $record->audits[$operation->value],
            metadata: [
                'provider' => $provider,
                'id' => $id,
                ...(is_array($detail) ? $detail : []),
            ],
        );
    }

    /**
     * Create a record. Most of the ERP's creates return no id for the new
     * record, so the audit also carries what the person entered to identify
     * it.
     *
     * @param  Closure(): (CreatedRecord|int|string|null|void)  $write  Sends the create; returns the new id, if any.
     * @param  array<string, mixed>  $detail  What identifies the new record, for the audit.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     * @throws LogicException when the record cannot be created here
     */
    public function create(MasterRecord $record, string $provider, Closure $write, array $detail): CreatedRecord|int|string|null
    {
        if (! $record->supports(RecordOperation::Create)) {
            throw new LogicException("{$record->lockPrefix} does not support create.");
        }

        $created = $this->guard->run(
            $record->createLock(Auth::id()),
            $write,
            function (EsbException $exception) use ($record, $provider, $detail): void {
                AuditLog::record(
                    action: $record->writeUnconfirmed,
                    metadata: [
                        'provider' => $provider,
                        'operation' => RecordOperation::Create->value,
                        'id' => null,
                        'failure' => $exception->kind(),
                        'correlation_id' => $exception->context->correlationId,
                        ...$detail,
                    ],
                );
            },
        );

        AuditLog::record(
            action: $record->audits[RecordOperation::Create->value],
            metadata: ['provider' => $provider, ...$this->createdDetail($created), ...$detail],
        );

        return is_int($created) || is_string($created) || $created instanceof CreatedRecord ? $created : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function createdDetail(mixed $created): array
    {
        if ($created instanceof CreatedRecord) {
            return [
                ...($created->id === null ? [] : ['id' => $created->id]),
                ...($created->pendingApproval ? ['pending_approval' => true] : []),
            ];
        }

        return is_int($created) || is_string($created) ? ['id' => $created] : [];
    }
}
