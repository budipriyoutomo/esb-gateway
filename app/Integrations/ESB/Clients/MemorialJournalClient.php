<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\MemorialJournal;
use App\Domain\Canonical\MemorialJournalPage;
use App\Domain\Canonical\MemorialJournalQuery;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\JournalMapper;

/**
 * Adapter for the ESB Core memorial journals ([ESB-043]). Reads only so far
 * (S1).
 */
class MemorialJournalClient implements MemorialJournalSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/accounting/memorial-journal';

    public function __construct(
        private readonly EsbClient $client,
        private readonly JournalMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal/get
     */
    public function listJournals(MemorialJournalQuery $query): MemorialJournalPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'memorialJournalNum' => $query->number,
                'dateFrom' => $query->dateFrom,
                'dateTo' => $query->dateTo,
                'statusID' => $query->statusId,
                'page' => $query->page,
                'limit' => $query->limit,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'memorial-journals.list',
        );

        return new MemorialJournalPage(
            journals: $this->mapper->memorialJournalsFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum/get
     */
    public function findJournal(string $number): ?MemorialJournal
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.rawurlencode($number), operation: 'memorial-journals.view');

        return $this->mapper->memorialJournalFromDetail($envelope->resultArray());
    }
}
