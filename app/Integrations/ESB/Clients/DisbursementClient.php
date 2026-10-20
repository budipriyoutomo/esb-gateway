<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\Disbursement;
use App\Domain\Canonical\DisbursementCarryOver;
use App\Domain\Canonical\DisbursementDraft;
use App\Domain\Canonical\DisbursementDraftLine;
use App\Domain\Canonical\DisbursementPage;
use App\Domain\Canonical\DisbursementPaymentMethod;
use App\Domain\Canonical\DisbursementQuery;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\DisbursementMapper;

/**
 * Adapter for the ESB Core disbursements ([ESB-037]–[ESB-040]). Writes --
 * and the PUT validate and the uploads -- are sent once (EsbClient never
 * retries a non-GET).
 */
class DisbursementClient implements DisbursementSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/finance/disbursement';

    public function __construct(
        private readonly EsbClient $client,
        private readonly DisbursementMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement/get
     */
    public function listDisbursements(DisbursementQuery $query): DisbursementPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'disbursementNum' => $query->number,
                'disbursementDateFrom' => $query->dateFrom,
                'disbursementDateTo' => $query->dateTo,
                'statusID' => $query->statusId,
                'branchID' => $query->branchId,
                'supplierID' => $query->supplierId,
                'paymentID' => $query->payment?->value,
                'ref' => $query->reference,
                // Documented as a boolean; sent only to narrow the list.
                'isOverDue' => $query->overdueOnly ? 'true' : null,
                'page' => $query->page,
                'limit' => $query->limit,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'disbursements.list',
        );

        return new DisbursementPage(
            disbursements: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum/get
     */
    public function findDisbursement(string $number): ?Disbursement
    {
        $envelope = $this->client->get(self::FAMILY, $this->path($number), operation: 'disbursements.view');

        return $this->mapper->fromDetail($envelope->resultArray());
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--authorize/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--reject/patch
     */
    public function decideDisbursement(string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $segment = $decision === ApprovalDecision::Authorize ? 'authorize' : 'reject';

        $this->client->patch(
            self::FAMILY,
            $this->path($number).'/'.$segment,
            $decision === ApprovalDecision::Reject && $reason !== null ? ['reason' => $reason] : [],
            operation: 'disbursements.'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum/delete
     */
    public function deleteDisbursement(string $number): void
    {
        $this->client->delete(self::FAMILY, $this->path($number), operation: 'disbursements.delete');
    }

    /**
     * The vendor says the create "returns the new disbursementNum"; its
     * example is {}. The number is read when it is there.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement/post
     */
    public function createDisbursement(DisbursementDraft $draft): ?string
    {
        $envelope = $this->client->post(self::FAMILY, self::PATH, $this->body($draft, new DisbursementCarryOver), operation: 'disbursements.create');

        $number = $envelope->resultArray()['disbursementNum'] ?? null;

        return is_string($number) && trim($number) !== '' ? trim($number) : null;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--validate/put
     */
    public function validateUpdate(string $number): void
    {
        $this->client->put(self::FAMILY, $this->path($number).'/validate', operation: 'disbursements.validate');
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum/put
     */
    public function updateDisbursement(string $number, DisbursementDraft $draft, DisbursementCarryOver $carryOver): void
    {
        $this->client->put(self::FAMILY, $this->path($number), $this->body($draft, $carryOver), operation: 'disbursements.update');
    }

    /**
     * The vendor's "existing" list is never sent: the caller attaches only
     * while the disbursement has none ([ESB-040]).
     *
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-disbursementNum--attachment/patch
     */
    public function attachFiles(string $number, array $files): void
    {
        $this->client->uploadFiles(self::FAMILY, $this->path($number).'/attachment', 'files', $files, method: 'PATCH', operation: 'disbursements.attach');
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-import-form/post
     */
    public function readFormLines(string $contents, string $filename): array
    {
        $envelope = $this->client->upload(self::FAMILY, self::PATH.'/import/form', 'file', $contents, $filename, operation: 'disbursements.read-form');

        return $this->mapper->formLinesFrom($envelope->resultArray());
    }

    /**
     * The vendor's create / update body. Fields a payment method does not
     * allow are left out (cash: credit term, reference; credit: the cash /
     * bank account), and so is any optional value the draft does not have.
     * The notes are always sent ("" clears them on an edit).
     *
     * @return array<string, mixed>
     */
    private function body(DisbursementDraft $draft, DisbursementCarryOver $carryOver): array
    {
        $optional = static fn (array $values): array => array_filter($values, static fn (mixed $value): bool => $value !== null);
        $cash = $draft->payment === DisbursementPaymentMethod::Cash;

        return [
            'disbursementDate' => $draft->date,
            'branchID' => $draft->branchId,
            'paymentID' => $draft->payment->value,
            'currencyID' => $draft->currencyId,
            'rate' => $draft->rate,
            ...$optional([
                'disbursementCoaNo' => $cash ? $draft->cashBankCoaNo : null,
                'supplierID' => $draft->supplierId,
                'creditTerm' => $cash ? null : $draft->creditTerm,
                'ref' => $cash ? null : $draft->reference,
                'invoiceDueDate' => $draft->invoiceDueDate,
            ]),
            'additionalInformation' => $draft->notes ?? '',
            'disbursementDetails' => array_map(static fn (DisbursementDraftLine $line): array => [
                'branchID' => $line->branchId,
                'coaNo' => $line->coaNo,
                'description' => $line->description,
                'amount' => $line->amount,
            ], $draft->lines),
            ...$optional([
                'costCenterID' => $carryOver->costCenterId,
                'projectID' => $carryOver->projectId,
                'bankName' => $carryOver->bankName,
                'bankAccountName' => $carryOver->bankAccountName,
                'bankAccountNumber' => $carryOver->bankAccountNumber,
            ]),
        ];
    }

    private function path(string $number): string
    {
        return self::PATH.'/'.rawurlencode($number);
    }
}
