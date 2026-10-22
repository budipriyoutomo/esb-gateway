<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\GeneralJournalEntry;
use App\Domain\Canonical\JournalLine;
use App\Domain\Canonical\LedgerEntry;
use App\Domain\Canonical\LedgerSummaryRow;
use App\Domain\Canonical\MemorialJournal;
use App\Domain\Canonical\MemorialJournalCarryOver;
use App\Domain\Canonical\MemorialJournalSummary;
use App\Integrations\ESB\Clients\GeneralJournalClient;
use App\Integrations\ESB\Clients\MemorialJournalClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * The ESB Core accounting reads onto the canonical shape: the general
 * journal ([ESB-041]), the general ledger ([ESB-042]) and memorial journals
 * ([ESB-043]). Field names differ per endpoint; a journal line means the same
 * in each.
 *
 * The vendor's examples are zero-valued, so 0 / "" ids and texts are "none".
 * Debit and credit are kept as sent, 0 included; a non-number is unknown.
 */
class JournalMapper
{
    use ReadsEsbValues;

    /**
     * @param  array<mixed>  $rows
     * @return list<GeneralJournalEntry>
     */
    public function journalEntriesFromRows(array $rows): array
    {
        $entries = [];

        foreach ($rows as $row) {
            $id = is_array($row) ? $this->nullableId($row['journalHeadID'] ?? null) : null;

            if ($id === null) {
                continue;
            }

            $entries[] = new GeneralJournalEntry(
                provider: GeneralJournalClient::PROVIDER,
                journalHeadId: $id,
                date: $this->nullableString($row['journalDate'] ?? null),
                transactionType: $this->nullableString($row['transactionType'] ?? null),
                reference: $this->nullableString($row['refNum'] ?? null),
                notes: $this->nullableString($row['notes'] ?? null),
                viewableInErp: $this->flag($row['typeHasAccessView'] ?? null),
            );
        }

        return $entries;
    }

    /**
     * General journal detail rows (coaDesc, journalDetailID).
     *
     * @return list<JournalLine>
     */
    public function journalLinesFrom(mixed $rows): array
    {
        return $this->lines($rows, idField: 'journalDetailID', descriptionField: 'coaDesc');
    }

    /**
     * @return list<LedgerEntry>
     */
    public function ledgerEntriesFrom(mixed $rows): array
    {
        $entries = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $coaNo = is_array($row) ? $this->nullableString($row['coaNumber'] ?? null) : null;

            if ($coaNo === null) {
                continue;
            }

            $entries[] = new LedgerEntry(
                date: $this->nullableString($row['journalDate'] ?? null),
                branchId: $this->nullableId($row['branchID'] ?? null),
                branchName: $this->nullableString($row['branchName'] ?? null),
                coaNo: $coaNo,
                coaDescription: $this->nullableString($row['coaDescription'] ?? null),
                transactionType: $this->nullableString($row['transactionType'] ?? null),
                reference: $this->nullableString($row['referenceNumber'] ?? null),
                notes: $this->nullableString($row['notes'] ?? null),
                info: $this->nullableString($row['generalLedgerInfo'] ?? null),
                debit: $this->amount($row['debitAmount'] ?? null),
                credit: $this->amount($row['creditAmount'] ?? null),
                costCenter: $this->nullableString($row['costCenterName'] ?? null),
                project: $this->nullableString($row['projectName'] ?? null),
                createdAt: $this->nullableString($row['createdDate'] ?? null),
            );
        }

        return $entries;
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<LedgerSummaryRow>
     */
    public function ledgerSummaryFromRows(array $rows): array
    {
        $summary = [];

        foreach ($rows as $row) {
            $accountNo = is_array($row) ? $this->nullableString($row['accountNo'] ?? null) : null;

            if ($accountNo === null) {
                continue;
            }

            $summary[] = new LedgerSummaryRow(
                date: $this->nullableString($row['journalDate'] ?? null),
                branchId: $this->nullableId($row['branchID'] ?? null),
                branchCode: $this->nullableString($row['branchCode'] ?? null),
                branchName: $this->nullableString($row['branchName'] ?? null),
                accountNo: $accountNo,
                accountDescription: $this->nullableString($row['accountDescription'] ?? null),
                debit: $this->amount($row['debitAmount'] ?? null),
                credit: $this->amount($row['creditAmount'] ?? null),
            );
        }

        return $summary;
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<MemorialJournalSummary>
     */
    public function memorialJournalsFromRows(array $rows): array
    {
        $journals = [];

        foreach ($rows as $row) {
            $journal = is_array($row) ? $this->memorialJournalFromRow($row) : null;

            if ($journal !== null) {
                $journals[] = $journal;
            }
        }

        return $journals;
    }

    /**
     * A list row, or null when it carries no number.
     *
     * @param  array<mixed>  $row
     */
    public function memorialJournalFromRow(array $row): ?MemorialJournalSummary
    {
        $number = $this->nullableString($row['memorialJournalNum'] ?? null);

        if ($number === null) {
            return null;
        }

        return new MemorialJournalSummary(
            provider: MemorialJournalClient::PROVIDER,
            number: $number,
            date: $this->nullableString($row['memorialJournalDate'] ?? null),
            notes: $this->nullableString($row['additionalInfo'] ?? null),
            hasApproval: $this->flag($row['approval'] ?? null),
            createdBy: $this->nullableString($row['createdBy'] ?? null),
            statusId: $this->nullableId($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no number.
     *
     * @param  array<mixed>  $result
     */
    public function memorialJournalFromDetail(array $result): ?MemorialJournal
    {
        $number = $this->nullableString($result['memorialJournalNum'] ?? null);

        if ($number === null) {
            return null;
        }

        $r = $result;

        return new MemorialJournal(
            provider: MemorialJournalClient::PROVIDER,
            number: $number,
            date: $this->nullableString($r['memorialJournalDate'] ?? null),
            notes: $this->nullableString($r['additionalInfo'] ?? null),
            hasApproval: $this->flag($r['approval'] ?? null),
            statusId: $this->nullableId($r['statusID'] ?? null),
            statusName: $this->nullableString($r['statusName'] ?? null),
            lines: $this->lines($r['memorialJournalDetails'] ?? null, idField: 'ID', descriptionField: 'description'),
            attachmentCount: count(array_filter(
                is_array($r['image'] ?? null) ? $r['image'] : [],
                fn (mixed $image): bool => is_array($image) && $this->nullableString($image['imageUrl'] ?? null) !== null,
            )),
            createdBy: $this->person($r['creator'] ?? null, $r['createdBy'] ?? null),
            createdAt: $this->nullableString($r['createdDate'] ?? null),
            editedBy: $this->person($r['editor'] ?? null, $r['editedBy'] ?? null),
            editedAt: $this->nullableString($r['editedDate'] ?? null),
            authorizedBy: $this->person($r['authorizer'] ?? null, $r['authorizedBy'] ?? null),
            authorizedAt: $this->nullableString($r['authorizedDate'] ?? null),
            carryOver: $this->carryOver($r['memorialJournalDetails'] ?? null),
        );
    }

    /**
     * Each memorial journal line's cost center and project, by line id, for
     * an edit to send back ([ESB-045]). Lines without an id are left out.
     */
    private function carryOver(mixed $rows): MemorialJournalCarryOver
    {
        $lines = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $id = is_array($row) ? $this->nullableId($row['ID'] ?? null) : null;

            if ($id !== null) {
                $lines[$id] = [
                    'costCenterId' => $this->nullableId($row['costCenterID'] ?? null),
                    'projectId' => $this->nullableId($row['projectID'] ?? null),
                ];
            }
        }

        return new MemorialJournalCarryOver($lines);
    }

    /**
     * @return list<JournalLine>
     */
    private function lines(mixed $rows, string $idField, string $descriptionField): array
    {
        $lines = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lines[] = new JournalLine(
                id: $this->nullableId($row[$idField] ?? null),
                branchId: $this->nullableId($row['branchID'] ?? null),
                branchName: $this->nullableString($row['branchName'] ?? null),
                coaNo: $this->nullableString($row['coaNo'] ?? null),
                coaDescription: $this->nullableString($row[$descriptionField] ?? null),
                costCenter: $this->nullableString($row['costCenterName'] ?? null),
                project: $this->nullableString($row['projectName'] ?? null),
                currencyName: $this->nullableString($row['currencyName'] ?? null),
                currencyId: $this->nullableId($row['currencyID'] ?? null),
                rate: $this->amount($row['rate'] ?? null),
                debit: $this->amount($row['drAmount'] ?? null),
                credit: $this->amount($row['crAmount'] ?? null),
                notes: $this->nullableString($row['notes'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * The person's full name, else their username.
     */
    private function person(mixed $person, mixed $username): ?string
    {
        $fullName = is_array($person) ? $this->nullableString($person['fullName'] ?? null) : null;

        return $fullName
            ?? (is_array($person) ? $this->nullableString($person['username'] ?? null) : null)
            ?? $this->nullableString($username);
    }

    private function amount(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
