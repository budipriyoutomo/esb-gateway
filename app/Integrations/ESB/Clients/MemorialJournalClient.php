<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\MemorialJournal;
use App\Domain\Canonical\MemorialJournalCarryOver;
use App\Domain\Canonical\MemorialJournalDraft;
use App\Domain\Canonical\MemorialJournalDraftLine;
use App\Domain\Canonical\MemorialJournalPage;
use App\Domain\Canonical\MemorialJournalQuery;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\JournalMapper;

/**
 * Adapter for the ESB Core memorial journals ([ESB-043]–[ESB-046]). Writes
 * and the image upload are sent once (EsbClient never retries a non-GET).
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
        $envelope = $this->client->get(self::FAMILY, $this->path($number), operation: 'memorial-journals.view');

        return $this->mapper->memorialJournalFromDetail($envelope->resultArray());
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum--authorize/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum--reject/patch
     */
    public function decideJournal(string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $segment = $decision === ApprovalDecision::Authorize ? 'authorize' : 'reject';

        $this->client->patch(
            self::FAMILY,
            $this->path($number).'/'.$segment,
            $decision === ApprovalDecision::Reject && $reason !== null ? ['reason' => $reason] : [],
            operation: 'memorial-journals.'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum/delete
     */
    public function deleteJournal(string $number): void
    {
        $this->client->delete(self::FAMILY, $this->path($number), operation: 'memorial-journals.delete');
    }

    /**
     * The vendor says the create returns the number "in
     * result.memorialJournalNum"; its example is {}. Read when it is there.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal/post
     */
    public function createJournal(MemorialJournalDraft $draft): ?string
    {
        $envelope = $this->client->post(self::FAMILY, self::PATH, $this->body($draft, new MemorialJournalCarryOver), operation: 'memorial-journals.create');

        $number = $envelope->resultArray()['memorialJournalNum'] ?? null;

        return is_string($number) && trim($number) !== '' ? trim($number) : null;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum/put
     */
    public function updateJournal(string $number, MemorialJournalDraft $draft, MemorialJournalCarryOver $carryOver): void
    {
        $this->client->put(self::FAMILY, $this->path($number), $this->body($draft, $carryOver), operation: 'memorial-journals.update');
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-memorial-journal-memorialJournalNum--asset-images/post
     */
    public function attachImage(string $number, string $contents, string $filename): void
    {
        $this->client->upload(self::FAMILY, $this->path($number).'/asset-images', 'file', $contents, $filename, operation: 'memorial-journals.attach');
    }

    /**
     * The vendor's create / update body. A line sends only the side it is on
     * (the other is "empty"), its notes when it has them, and -- on an edit
     * -- the cost center and project the ERP has for it. The journal's notes
     * are always sent ("" clears them on an edit).
     *
     * @return array<string, mixed>
     */
    private function body(MemorialJournalDraft $draft, MemorialJournalCarryOver $carryOver): array
    {
        $optional = static fn (array $values): array => array_filter($values, static fn (mixed $value): bool => $value !== null);

        return [
            'memorialJournalDate' => $draft->date,
            'additionalInfo' => $draft->notes ?? '',
            'memorialJournalDetails' => array_map(static function (MemorialJournalDraftLine $line) use ($optional, $carryOver): array {
                $kept = $carryOver->forLine($line->id);

                return [
                    'branchID' => $line->branchId,
                    'coaNo' => $line->coaNo,
                    'currencyID' => $line->currencyId,
                    'rate' => $line->rate,
                    ...$optional([
                        'drAmount' => $line->debit,
                        'crAmount' => $line->credit,
                        'notes' => $line->notes,
                        'costCenterID' => $kept['costCenterId'],
                        'projectID' => $kept['projectId'],
                    ]),
                ];
            }, $draft->lines),
        ];
    }

    private function path(string $number): string
    {
        return self::PATH.'/'.rawurlencode($number);
    }
}
