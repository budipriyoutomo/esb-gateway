<?php

namespace App\Actions\ClosingRecaps;

use App\Audit\AuditAction;
use App\Domain\Canonical\InventoryDraft;
use App\Domain\Canonical\InventoryDraftLine;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Responses\EsbFieldError;
use App\Models\AuditLog;
use App\Models\ClosingItemJournal;
use App\Models\ClosingRecap;
use App\Support\Decimal;
use Illuminate\Support\Facades\Cache;

/**
 * Send a closing recap's prepared item journals to the ERP (ADR-032, P28).
 *
 * 1. Every journal an earlier send created is deleted first. A refused
 *    delete, or one whose outcome stays unknown, stops here: nothing new is
 *    created while the old journals may still be in the ERP.
 * 2. Each prepared (or failed) journal of the current version is created,
 *    one attempt each. A refusal is `failed` and can be sent again; an
 *    unknown outcome is `unconfirmed` and blocks the recap until settled.
 *
 * One send per recap at a time.
 */
class SendClosingItemJournals
{
    private const LOCK_SECONDS = 600;

    public function __construct(private readonly InventoryDocumentSource $documents) {}

    /**
     * @return array<string, int> How many journals ended in each status.
     *
     * @throws ClosingRecapNotReady
     */
    public function handle(ClosingRecap $recap): array
    {
        $lock = Cache::lock('closing-recap-send:'.$recap->id, self::LOCK_SECONDS);

        if (! $lock->get()) {
            throw new ClosingRecapNotReady('This recap is already being sent. Wait for it to finish.');
        }

        try {
            return $this->send($recap);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, int>
     */
    private function send(ClosingRecap $recap): array
    {
        if ($recap->hasUnsettledJournal()) {
            throw new ClosingRecapNotReady('An item journal of this recap has an unknown outcome in the ERP. Settle it first.');
        }

        $pending = $recap->itemJournals()
            ->where('recap_sequence', $recap->sequence)
            ->whereIn('status', [ClosingItemJournal::STATUS_PREPARED, ClosingItemJournal::STATUS_FAILED])
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            throw new ClosingRecapNotReady($recap->status === ClosingRecap::STATUS_SENT
                ? 'Every item journal of this recap is already in the ERP.'
                : 'Nothing is prepared for the current version. Prepare the recap first.');
        }

        $outcomes = [];

        $earlier = $recap->itemJournals()
            ->where('recap_sequence', '<', $recap->sequence)
            ->whereIn('status', [ClosingItemJournal::STATUS_CREATED, ClosingItemJournal::STATUS_DELETE_REFUSED])
            ->orderBy('id')
            ->get();

        $cleared = true;

        foreach ($earlier as $journal) {
            $status = $this->delete($journal);
            $outcomes[] = ['journal' => $journal->public_id, 'number' => $journal->item_journal_num, 'status' => $status];
            $cleared = $cleared && $status === ClosingItemJournal::STATUS_DELETED;
        }

        if ($cleared) {
            foreach ($pending as $journal) {
                $status = $this->create($journal);
                $outcomes[] = ['journal' => $journal->public_id, 'kind' => $journal->kind, 'number' => $journal->item_journal_num, 'status' => $status];
            }
        }

        $allCreated = $cleared && $recap->itemJournals()
            ->where('recap_sequence', $recap->sequence)
            ->whereIn('status', [ClosingItemJournal::STATUS_PREPARED, ClosingItemJournal::STATUS_FAILED, ClosingItemJournal::STATUS_UNCONFIRMED])
            ->doesntExist();

        $recap->update(['status' => $allCreated ? ClosingRecap::STATUS_SENT : ClosingRecap::STATUS_ATTENTION]);

        AuditLog::record(
            action: AuditAction::ClosingRecapSent,
            auditable: $recap,
            metadata: ['sequence' => $recap->sequence, 'journals' => $outcomes],
        );

        return array_count_values(array_column($outcomes, 'status'));
    }

    private function create(ClosingItemJournal $journal): string
    {
        $draft = new InventoryDraft(
            date: $journal->journal_date->toDateString(),
            branchId: $journal->branch_id,
            locationId: $journal->location_id,
            destinationBranchId: null,
            destinationLocationId: null,
            notes: $journal->notes,
            lines: array_map(fn (array $line): InventoryDraftLine => new InventoryDraftLine(
                productDetailId: $line['productDetailId'],
                qty: Decimal::toNumber($line['qty']),
                purposeId: $journal->purpose_id,
            ), $journal->lines),
        );

        try {
            $number = $this->documents->createDocument(InventoryKind::ItemJournals, $draft);
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->fail($journal, ClosingItemJournal::STATUS_UNCONFIRMED, $exception);
                $this->auditUnconfirmed($journal, 'create', $exception);

                return ClosingItemJournal::STATUS_UNCONFIRMED;
            }

            $this->fail($journal, ClosingItemJournal::STATUS_FAILED, $exception);

            return ClosingItemJournal::STATUS_FAILED;
        }

        // The ERP documents itemJournalNum on a create. Without it the journal
        // could never be deleted on a revision, so someone must look it up.
        $status = $number === null ? ClosingItemJournal::STATUS_UNCONFIRMED : ClosingItemJournal::STATUS_CREATED;

        $journal->update([
            'status' => $status,
            'item_journal_num' => $number,
            'failure' => $number === null ? 'no_number' : null,
            'correlation_id' => null,
            'errors' => $number === null
                ? [['field' => null, 'message' => 'The ERP accepted the journal but did not say its number.']]
                : [],
            'sent_at' => now(),
        ]);

        return $status;
    }

    private function delete(ClosingItemJournal $journal): string
    {
        $number = (string) $journal->item_journal_num;

        try {
            $this->documents->deleteDocument(InventoryKind::ItemJournals, $number);
        } catch (EsbNotFoundException) {
            // Already gone (someone deleted it in the ERP).
        } catch (EsbException $exception) {
            if (! $exception->mayHaveBeenApplied()) {
                $this->fail($journal, ClosingItemJournal::STATUS_DELETE_REFUSED, $exception);

                return ClosingItemJournal::STATUS_DELETE_REFUSED;
            }

            // The outcome is unknown: read it back before creating anything.
            if (! $this->isGone($number)) {
                $this->fail($journal, ClosingItemJournal::STATUS_DELETE_UNCONFIRMED, $exception);
                $this->auditUnconfirmed($journal, 'delete', $exception);

                return ClosingItemJournal::STATUS_DELETE_UNCONFIRMED;
            }
        }

        $journal->update([
            'status' => ClosingItemJournal::STATUS_DELETED,
            'failure' => null,
            'correlation_id' => null,
            'errors' => [],
            'deleted_in_erp_at' => now(),
        ]);

        return ClosingItemJournal::STATUS_DELETED;
    }

    /**
     * Whether the journal is no longer in the ERP. A failed read is not proof.
     */
    private function isGone(string $number): bool
    {
        try {
            return $this->documents->findDocument(InventoryKind::ItemJournals, $number) === null;
        } catch (EsbNotFoundException) {
            return true;
        } catch (EsbException) {
            return false;
        }
    }

    private function fail(ClosingItemJournal $journal, string $status, EsbException $exception): void
    {
        $errors = array_map(
            fn (EsbFieldError $error): array => ['field' => $error->field, 'message' => $error->message],
            $exception->fieldErrors,
        );

        $journal->update([
            'status' => $status,
            'failure' => $exception->kind(),
            'correlation_id' => $exception->context->correlationId,
            'errors' => $errors !== [] ? $errors : [['field' => null, 'message' => $exception->upstreamMessage ?? $exception->safeMessage()]],
        ]);
    }

    private function auditUnconfirmed(ClosingItemJournal $journal, string $operation, EsbException $exception): void
    {
        AuditLog::record(
            action: AuditAction::ItemJournalWriteUnconfirmed,
            auditable: $journal,
            metadata: [
                'provider' => $this->documents->provider(),
                'operation' => $operation,
                'number' => $journal->item_journal_num,
                'failure' => $exception->kind(),
                'correlation_id' => $exception->context->correlationId,
                'source' => 'closing-recap',
            ],
        );
    }
}
