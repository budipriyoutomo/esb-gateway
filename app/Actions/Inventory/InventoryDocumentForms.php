<?php

namespace App\Actions\Inventory;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\InventoryDocument;
use App\Domain\Canonical\InventoryDraft;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Create or replace an inventory document in the ERP, on behalf of a person
 * ([ESB-035]). The write rules are GuardedEsbWrite's: one attempt, an
 * ambiguous failure audited with its correlation id. An update holds the
 * document's lock (the one its decisions and delete hold); a create, which
 * has no number yet, holds one per person and kind -- enough to stop a
 * double submit.
 */
class InventoryDocumentForms
{
    public function __construct(
        private readonly InventoryDocumentSource $documents,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @return string|null The new number, when the ERP said it.
     *
     * @throws WriteAlreadyInFlight when this person's previous create is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function create(InventoryKind $kind, InventoryDraft $draft): ?string
    {
        [$created, $unconfirmed] = $this->audits($kind);
        $provider = $this->documents->provider();
        $detail = ['date' => $draft->date, 'lines' => count($draft->lines)];

        $number = $this->guard->run(
            InventoryDocuments::approvable($kind)->lockPrefix.'-create:'.(Auth::id() ?? 'guest'),
            fn (): ?string => $this->documents->createDocument($kind, $draft),
            fn (EsbException $exception) => $this->recordUnconfirmed($unconfirmed, $provider, 'create', null, $exception, $detail),
        );

        AuditLog::record(
            action: $created,
            metadata: ['provider' => $provider, ...($number === null ? [] : ['number' => $number]), ...$detail],
        );

        return $number;
    }

    /**
     * @param  InventoryDocument  $current  Read just before, outside the lock, so a
     *                                      failed read is never taken for an
     *                                      unconfirmed update.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function update(InventoryKind $kind, string $number, InventoryDraft $draft, InventoryDocument $current): void
    {
        [, $unconfirmed, $updated] = $this->audits($kind);
        $provider = $this->documents->provider();
        $detail = ['lines' => count($draft->lines)];

        $this->guard->run(
            InventoryDocuments::approvable($kind)->lock($number),
            fn () => $this->documents->updateDocument($kind, $number, $draft, $current->carryOver),
            fn (EsbException $exception) => $this->recordUnconfirmed($unconfirmed, $provider, 'update', $number, $exception, $detail),
        );

        AuditLog::record(
            action: $updated,
            metadata: ['provider' => $provider, 'number' => $number, ...$detail],
        );
    }

    /**
     * @return array{AuditAction, AuditAction, AuditAction} created, write-unconfirmed, updated
     */
    private function audits(InventoryKind $kind): array
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => [AuditAction::GoodsTransferRequestCreated, AuditAction::GoodsTransferRequestWriteUnconfirmed, AuditAction::GoodsTransferRequestUpdated],
            InventoryKind::ItemJournals => [AuditAction::ItemJournalCreated, AuditAction::ItemJournalWriteUnconfirmed, AuditAction::ItemJournalUpdated],
            InventoryKind::SimpleTransfers => [AuditAction::SimpleTransferCreated, AuditAction::SimpleTransferWriteUnconfirmed, AuditAction::SimpleTransferUpdated],
            default => throw new LogicException("{$kind->value} is not created or edited through the gateway."),
        };
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function recordUnconfirmed(
        AuditAction $action,
        string $provider,
        string $operation,
        ?string $number,
        EsbException $exception,
        array $detail,
    ): void {
        AuditLog::record(
            action: $action,
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
