<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\LedgerEntryPage;
use App\Domain\Canonical\LedgerQuery;
use App\Domain\Canonical\LedgerSide;
use App\Domain\Canonical\LedgerSummaryPage;
use App\Domain\Canonical\LedgerSummaryQuery;
use App\Integrations\Contracts\GeneralLedgerSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\JournalMapper;

/**
 * Adapter for the ESB Core general ledger ([ESB-042]). Both operations are
 * POSTs the vendor documents as reads ("List…", nothing saved), so they go
 * through postRead() and are retried like a GET (ADR-025).
 */
class GeneralLedgerClient implements GeneralLedgerSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    public function __construct(
        private readonly EsbClient $client,
        private readonly JournalMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * The vendor answers the whole period, unpaged; the gateway pages it.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-general-ledger/post
     */
    public function entries(LedgerQuery $query): LedgerEntryPage
    {
        $envelope = $this->client->postRead(
            self::FAMILY,
            '/accounting/general-ledger',
            array_filter([
                'coaNo' => $query->coaNo,
                'dateFrom' => $query->dateFrom,
                'dateTo' => $query->dateTo,
                'branchID' => $query->branchId,
                'debitCreditFilter' => match ($query->side) {
                    LedgerSide::Debit => 1,
                    LedgerSide::Credit => 2,
                    null => null,
                },
                'reference' => $query->reference,
                'notes' => $query->notes,
                'transactionType' => $query->transactionType,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'general-ledger.entries',
        );

        return LedgerEntryPage::fromLedger($this->mapper->ledgerEntriesFrom($envelope->resultArray()), $query);
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-general-ledger-summary/post
     */
    public function summary(LedgerSummaryQuery $query): LedgerSummaryPage
    {
        $envelope = $this->client->postRead(
            self::FAMILY,
            '/accounting/general-ledger/summary',
            array_filter([
                'startPeriod' => $query->dateFrom,
                'endPeriod' => $query->dateTo,
                'accountNo' => $query->accountNo,
                'branchID' => $query->branchId,
                'page' => $query->page,
                'limit' => $query->limit,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'general-ledger.summary',
        );

        return new LedgerSummaryPage(
            rows: $this->mapper->ledgerSummaryFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }
}
