<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\GeneralJournalPage;
use App\Domain\Canonical\GeneralJournalQuery;
use App\Domain\Canonical\JournalLinePage;
use App\Integrations\Contracts\GeneralJournalSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\JournalMapper;

/**
 * Adapter for the ESB Core general journal ([ESB-041]). Reads only.
 */
class GeneralJournalClient implements GeneralJournalSource
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
     * The vendor's array filters (branchID, account, costCenterID,
     * projectID) are not sent: their query format is undocumented.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-general-journal-search/get
     */
    public function listEntries(GeneralJournalQuery $query): GeneralJournalPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/accounting/general-journal/search',
            array_filter([
                'dateFrom' => $query->dateFrom,
                'dateTo' => $query->dateTo,
                'journalSearch' => $query->search,
                'page' => $query->page,
                'limit' => $query->limit,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'general-journals.list',
        );

        return new GeneralJournalPage(
            entries: $this->mapper->journalEntriesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-general-journal-journalHeadID--detail/get
     */
    public function journalLines(int $journalHeadId, int $page): JournalLinePage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/accounting/general-journal/'.$journalHeadId.'/detail',
            ['page' => $page, 'limit' => JournalLinePage::LIMIT],
            operation: 'general-journals.view',
        );

        return new JournalLinePage(
            lines: $this->mapper->journalLinesFrom($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }
}
