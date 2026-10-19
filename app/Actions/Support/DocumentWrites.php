<?php

namespace App\Actions\Support;

use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Closure;

/**
 * Authorize, reject, or delete an existing document in the ERP, on behalf of
 * a person -- the part every approvable document shares.
 *
 * The write rules are GuardedEsbWrite's. The ERP records the gateway's shared
 * service credential (ADR-009) as the approver, so the audit row is the only
 * record of the actual person.
 */
class DocumentWrites
{
    public function __construct(private readonly GuardedEsbWrite $guard) {}

    /**
     * @param  string|null  $reason  Forwarded and audited for a rejection;
     *                               dropped for an authorization.
     * @param  Closure(?string): void  $write  Sends the decision, given the reason to send.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function decide(
        ApprovableDocument $document,
        string $provider,
        string $number,
        ApprovalDecision $decision,
        ?string $reason,
        Closure $write,
    ): void {
        $reason = $decision === ApprovalDecision::Reject ? $reason : null;

        $this->guard->run(
            $document->lock($number),
            fn () => $write($reason),
            fn (EsbException $exception) => $this->recordUnconfirmed($document, $provider, $decision->value, $number, $exception),
        );

        AuditLog::record(
            action: match ($decision) {
                ApprovalDecision::Authorize => $document->authorized,
                ApprovalDecision::Reject => $document->rejected,
            },
            metadata: array_filter([
                'provider' => $provider,
                'number' => $number,
                'reason' => $reason,
            ], static fn (?string $value): bool => $value !== null),
        );
    }

    /**
     * @param  Closure(): void  $write
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function delete(ApprovableDocument $document, string $provider, string $number, Closure $write): void
    {
        $this->guard->run(
            $document->lock($number),
            $write,
            fn (EsbException $exception) => $this->recordUnconfirmed($document, $provider, 'delete', $number, $exception),
        );

        AuditLog::record(
            action: $document->deleted,
            metadata: [
                'provider' => $provider,
                'number' => $number,
            ],
        );
    }

    /**
     * @param  'authorize'|'reject'|'delete'  $operation
     */
    private function recordUnconfirmed(
        ApprovableDocument $document,
        string $provider,
        string $operation,
        string $number,
        EsbException $exception,
    ): void {
        AuditLog::record(
            action: $document->writeUnconfirmed,
            metadata: [
                'provider' => $provider,
                'operation' => $operation,
                'number' => $number,
                'failure' => $exception->kind(),
                'correlation_id' => $exception->context->correlationId,
            ],
        );
    }
}
