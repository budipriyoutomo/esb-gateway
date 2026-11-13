<?php

namespace App\Actions\ClosingRecaps;

use App\Audit\AuditAction;
use App\Models\AuditLog;
use App\Models\ClosingItemJournal;
use App\Models\ClosingRecap;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Settle an item journal whose ERP outcome was unknown, after someone checked
 * the ERP (ADR-032, P28). The gateway cannot tell: the ERP offers no search by
 * the journal's notes.
 */
class ResolveClosingItemJournal
{
    public const CREATED = 'created';

    public const NOT_CREATED = 'not_created';

    public const DELETED = 'deleted';

    public const NOT_DELETED = 'not_deleted';

    public const OUTCOMES = [self::CREATED, self::NOT_CREATED, self::DELETED, self::NOT_DELETED];

    public function handle(ClosingItemJournal $journal, string $outcome, ?string $number): void
    {
        $status = match ([$journal->status, $outcome]) {
            [ClosingItemJournal::STATUS_UNCONFIRMED, self::CREATED] => ClosingItemJournal::STATUS_CREATED,
            [ClosingItemJournal::STATUS_UNCONFIRMED, self::NOT_CREATED] => ClosingItemJournal::STATUS_FAILED,
            [ClosingItemJournal::STATUS_DELETE_UNCONFIRMED, self::DELETED] => ClosingItemJournal::STATUS_DELETED,
            // Still in the ERP: the next send deletes it again.
            [ClosingItemJournal::STATUS_DELETE_UNCONFIRMED, self::NOT_DELETED] => ClosingItemJournal::STATUS_CREATED,
            default => throw ValidationException::withMessages([
                'outcome' => in_array($journal->status, ClosingItemJournal::BLOCKING, true)
                    ? 'This outcome does not apply to this item journal.'
                    : 'Only an item journal with an unknown outcome can be settled.',
            ]),
        };

        if ($status === ClosingItemJournal::STATUS_CREATED && $journal->status === ClosingItemJournal::STATUS_UNCONFIRMED && ($number === null || $number === '')) {
            throw ValidationException::withMessages(['itemJournalNum' => 'Give the number the ERP shows for this item journal.']);
        }

        DB::transaction(function () use ($journal, $status, $outcome, $number): void {
            $before = $journal->status;

            $journal->update([
                'status' => $status,
                'item_journal_num' => $before === ClosingItemJournal::STATUS_UNCONFIRMED && $status === ClosingItemJournal::STATUS_CREATED
                    ? $number
                    : $journal->item_journal_num,
                'failure' => $status === ClosingItemJournal::STATUS_FAILED ? 'not_created' : null,
                'errors' => $status === ClosingItemJournal::STATUS_FAILED
                    ? [['field' => null, 'message' => 'Checked in the ERP: it was not created.']]
                    : [],
                'sent_at' => $status === ClosingItemJournal::STATUS_CREATED ? ($journal->sent_at ?? now()) : $journal->sent_at,
                'deleted_in_erp_at' => $status === ClosingItemJournal::STATUS_DELETED ? now() : $journal->deleted_in_erp_at,
            ]);

            AuditLog::record(
                action: AuditAction::ClosingItemJournalResolved,
                auditable: $journal,
                metadata: ['outcome' => $outcome, 'before' => $before, 'after' => $status, 'number' => $journal->item_journal_num],
            );

            $this->settleRecap($journal->recap()->firstOrFail());
        });
    }

    /**
     * Sent once nothing is left to delete or create; otherwise it still needs
     * attention.
     */
    private function settleRecap(ClosingRecap $recap): void
    {
        $open = $recap->itemJournals()
            ->where(function ($query) use ($recap): void {
                $query
                    ->where(fn ($q) => $q->where('recap_sequence', $recap->sequence)->whereIn('status', [
                        ClosingItemJournal::STATUS_PREPARED, ClosingItemJournal::STATUS_FAILED, ClosingItemJournal::STATUS_UNCONFIRMED,
                    ]))
                    ->orWhere(fn ($q) => $q->where('recap_sequence', '<', $recap->sequence)->whereIn('status', [
                        ClosingItemJournal::STATUS_CREATED, ClosingItemJournal::STATUS_DELETE_REFUSED, ClosingItemJournal::STATUS_DELETE_UNCONFIRMED,
                    ]));
            })
            ->exists();

        $recap->update(['status' => $open ? ClosingRecap::STATUS_ATTENTION : ClosingRecap::STATUS_SENT]);
    }
}
