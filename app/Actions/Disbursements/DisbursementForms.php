<?php

namespace App\Actions\Disbursements;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\Disbursement;
use App\Domain\Canonical\DisbursementDraft;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Create or replace a disbursement in the ERP, on behalf of a person
 * ([ESB-039]). The write rules are GuardedEsbWrite's: one attempt, an
 * ambiguous failure audited with its correlation id. An update holds the
 * disbursement's lock; a create, which has no number yet, holds one per
 * person -- enough to stop a double submit.
 */
class DisbursementForms
{
    public function __construct(
        private readonly DisbursementSource $disbursements,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @return string|null The new number, when the ERP said it.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(DisbursementDraft $draft): ?string
    {
        $provider = $this->disbursements->provider();
        $detail = ['date' => $draft->date, 'payment' => $draft->payment->value, 'lines' => count($draft->lines)];

        $number = $this->guard->run(
            Disbursements::approvable()->lockPrefix.'-create:'.(Auth::id() ?? 'guest'),
            fn (): ?string => $this->disbursements->createDisbursement($draft),
            fn (EsbException $exception) => $this->recordUnconfirmed($provider, 'create', null, $exception, $detail),
        );

        AuditLog::record(
            action: AuditAction::DisbursementCreated,
            metadata: ['provider' => $provider, ...($number === null ? [] : ['number' => $number]), ...$detail],
        );

        return $number;
    }

    /**
     * @param  Disbursement  $current  Read just before, outside the lock, so a
     *                                 failed read is never taken for an
     *                                 unconfirmed update.
     *
     * @throws WriteAlreadyInFlight when another write on this disbursement is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(string $number, DisbursementDraft $draft, Disbursement $current): void
    {
        $provider = $this->disbursements->provider();
        $detail = ['payment' => $draft->payment->value, 'lines' => count($draft->lines)];

        $this->guard->run(
            Disbursements::approvable()->lock($number),
            fn () => $this->disbursements->updateDisbursement($number, $draft, $current->carryOver),
            fn (EsbException $exception) => $this->recordUnconfirmed($provider, 'update', $number, $exception, $detail),
        );

        AuditLog::record(
            action: AuditAction::DisbursementUpdated,
            metadata: ['provider' => $provider, 'number' => $number, ...$detail],
        );
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function recordUnconfirmed(string $provider, string $operation, ?string $number, EsbException $exception, array $detail): void
    {
        AuditLog::record(
            action: AuditAction::DisbursementWriteUnconfirmed,
            metadata: [
                'provider' => $provider,
                'operation' => $operation,
                'number' => $number,
                'failure' => $exception->kind(),
                'correlation_id' => $exception->context->correlationId,
                ...$detail,
            ],
        );
    }
}
