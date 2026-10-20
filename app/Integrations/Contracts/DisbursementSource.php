<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\Disbursement;
use App\Domain\Canonical\DisbursementCarryOver;
use App\Domain\Canonical\DisbursementDraft;
use App\Domain\Canonical\DisbursementLine;
use App\Domain\Canonical\DisbursementPage;
use App\Domain\Canonical\DisbursementQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads disbursements ([ESB-037]), changes their state ([ESB-038]), creates
 * and replaces them ([ESB-039]), and their attachments and Excel form
 * ([ESB-040]).
 */
interface DisbursementSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listDisbursements(DisbursementQuery $query): DisbursementPage;

    /**
     * The disbursement with this number, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findDisbursement(string $number): ?Disbursement;

    /**
     * @param  string|null  $reason  Sent with a rejection only.
     *
     * @throws EsbException
     */
    public function decideDisbursement(string $number, ApprovalDecision $decision, ?string $reason = null): void;

    /**
     * @throws EsbException
     */
    public function deleteDisbursement(string $number): void;

    /**
     * The new number, or null when the provider does not say it.
     *
     * @throws EsbException
     */
    public function createDisbursement(DisbursementDraft $draft): ?string;

    /**
     * Ask whether the disbursement may be changed (period, supplier, cost
     * center, payments); nothing is saved. Returns when it may.
     *
     * @throws EsbException with the provider's reason when it may not
     */
    public function validateUpdate(string $number): void;

    /**
     * Replace the disbursement with the draft, sending back what the form
     * does not show.
     *
     * @throws EsbException
     */
    public function updateDisbursement(string $number, DisbursementDraft $draft, DisbursementCarryOver $carryOver): void;

    /**
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws EsbException
     */
    public function attachFiles(string $number, array $files): void;

    /**
     * The lines the provider reads from its Excel disbursement form; nothing
     * is saved.
     *
     * @return list<DisbursementLine>
     *
     * @throws EsbException
     */
    public function readFormLines(string $contents, string $filename): array;
}
