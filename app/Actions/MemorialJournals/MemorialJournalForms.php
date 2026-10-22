<?php

namespace App\Actions\MemorialJournals;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\MemorialJournal;
use App\Domain\Canonical\MemorialJournalDraft;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Create or replace a memorial journal in the ERP, on behalf of a person
 * ([ESB-045]). The write rules are GuardedEsbWrite's: one attempt, an
 * ambiguous failure audited with its correlation id. An update holds the
 * journal's lock; a create, which has no number yet, holds one per person --
 * enough to stop a double submit.
 */
class MemorialJournalForms
{
    public function __construct(
        private readonly MemorialJournalSource $journals,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @return string|null The new number, when the ERP said it.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(MemorialJournalDraft $draft): ?string
    {
        $provider = $this->journals->provider();
        $detail = ['date' => $draft->date, 'lines' => count($draft->lines)];

        $number = $this->guard->run(
            MemorialJournals::approvable()->lockPrefix.'-create:'.(Auth::id() ?? 'guest'),
            fn (): ?string => $this->journals->createJournal($draft),
            fn (EsbException $exception) => $this->recordUnconfirmed($provider, 'create', null, $exception, $detail),
        );

        AuditLog::record(
            action: AuditAction::MemorialJournalCreated,
            metadata: ['provider' => $provider, ...($number === null ? [] : ['number' => $number]), ...$detail],
        );

        return $number;
    }

    /**
     * @param  MemorialJournal  $current  Read just before, outside the lock, so a
     *                                    failed read is never taken for an
     *                                    unconfirmed update.
     *
     * @throws WriteAlreadyInFlight when another write on this journal is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(string $number, MemorialJournalDraft $draft, MemorialJournal $current): void
    {
        $provider = $this->journals->provider();
        $detail = ['lines' => count($draft->lines)];

        $this->guard->run(
            MemorialJournals::approvable()->lock($number),
            fn () => $this->journals->updateJournal($number, $draft, $current->carryOver),
            fn (EsbException $exception) => $this->recordUnconfirmed($provider, 'update', $number, $exception, $detail),
        );

        AuditLog::record(
            action: AuditAction::MemorialJournalUpdated,
            metadata: ['provider' => $provider, 'number' => $number, ...$detail],
        );
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function recordUnconfirmed(string $provider, string $operation, ?string $number, EsbException $exception, array $detail): void
    {
        AuditLog::record(
            action: AuditAction::MemorialJournalWriteUnconfirmed,
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
