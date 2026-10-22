<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\Disbursement;
use App\Domain\Canonical\DisbursementCarryOver;
use App\Domain\Canonical\DisbursementLine;
use App\Domain\Canonical\DisbursementLinkedDocument;
use App\Domain\Canonical\DisbursementSummary;
use App\Integrations\ESB\Clients\DisbursementClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * ESB Core disbursements onto the canonical shape ([ESB-037]).
 *
 * The vendor's examples are zero-valued, so 0 / "" ids and texts are "none".
 * Amounts are kept as sent, 0 included; a non-number is unknown (null).
 */
class DisbursementMapper
{
    use ReadsEsbValues;

    /**
     * @param  array<mixed>  $rows
     * @return list<DisbursementSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        $summaries = [];

        foreach ($rows as $row) {
            $summary = is_array($row) ? $this->summaryFromRow($row) : null;

            if ($summary !== null) {
                $summaries[] = $summary;
            }
        }

        return $summaries;
    }

    /**
     * A list row, or null when it carries no number.
     *
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): ?DisbursementSummary
    {
        $number = $this->nullableString($row['disbursementNum'] ?? null);

        if ($number === null) {
            return null;
        }

        return new DisbursementSummary(
            provider: DisbursementClient::PROVIDER,
            number: $number,
            date: $this->nullableString($row['disbursementDate'] ?? null),
            branchId: $this->nullableId($row['branchID'] ?? null),
            branchName: $this->nullableString($row['branchName'] ?? null),
            supplierId: $this->nullableId($row['supplierID'] ?? null),
            supplierName: $this->nullableString($row['supplierName'] ?? null),
            paymentMethod: $this->nullableString($row['paymentMethodName'] ?? null),
            reference: $this->nullableString($row['ref'] ?? null),
            notes: $this->nullableString($row['additionalInfo'] ?? null),
            grandTotal: $this->amount($row['grandTotal'] ?? null),
            invoiceDueDate: $this->nullableString($row['invoiceDueDate'] ?? null),
            overdue: $this->flag($row['overDue'] ?? null),
            hasApproval: $this->flag($row['hasApproval'] ?? null),
            statusId: $this->nullableId($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no number.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?Disbursement
    {
        $number = $this->nullableString($result['disbursementNum'] ?? null);

        if ($number === null) {
            return null;
        }

        $r = $result;

        return new Disbursement(
            provider: DisbursementClient::PROVIDER,
            number: $number,
            date: $this->nullableString($r['disbursementDate'] ?? null),
            statusId: $this->nullableId($r['statusID'] ?? null),
            statusName: $this->nullableString($r['statusName'] ?? null),
            branchId: $this->nullableId($r['branchID'] ?? null),
            branchName: $this->nullableString($r['branchName'] ?? null),
            paymentId: $this->nullableId($r['paymentID'] ?? null),
            paymentName: $this->nullableString($r['paymentName'] ?? null),
            cashBankCoaNo: $this->nullableString($r['disbursementCoaNo'] ?? null),
            supplierId: $this->nullableId($r['supplierID'] ?? null),
            supplierName: $this->nullableString($r['supplierName'] ?? null),
            currencyId: $this->nullableId($r['currencyID'] ?? null),
            currencyName: $this->nullableString($r['currencyName'] ?? null),
            currencySign: $this->nullableString($r['currencySign'] ?? null),
            // A rate of 0 converts nothing: the example's placeholder.
            rate: ($rate = $this->amount($r['rate'] ?? null)) === null || $rate <= 0.0 ? null : $rate,
            creditTerm: $this->positiveIntOrNull($r['creditTerm'] ?? null),
            invoiceDueDate: $this->nullableString($r['invoiceDueDate'] ?? null),
            reference: $this->nullableString($r['ref'] ?? null),
            notes: $this->nullableString($r['additionalInformation'] ?? null),
            bankName: $this->nullableString($r['bankName'] ?? null),
            bankAccountName: $this->nullableString($r['bankAccountName'] ?? null),
            bankAccountNumber: $this->accountNumber($r['bankAccountNumber'] ?? null),
            costCenter: $this->nullableString($r['costCenterName'] ?? null),
            project: $this->nullableString($r['projectName'] ?? null),
            grandTotal: $this->amount($r['grandTotal'] ?? null),
            releasePaymentCode: $this->nullableString($r['releasePaymentCode'] ?? null),
            lines: $this->linesFrom($r['disbursementDetails'] ?? null),
            linkedDocuments: [
                ...$this->linked('Purchase payment', $r['purchasePaymentDetails'] ?? null, 'purchasePaymentNum', 'purchasePaymentDate'),
                ...$this->linked('Supplier settlement', $r['supplierSettlementDetails'] ?? null, 'payableSettlementNum', 'payableSettlementDate'),
            ],
            attachmentCount: count(array_filter(
                is_array($r['imageAttachments'] ?? null) ? $r['imageAttachments'] : [],
                static fn (mixed $url): bool => is_string($url) && trim($url) !== '',
            )),
            createdBy: $this->nullableString($r['createdBy'] ?? null),
            createdAt: $this->nullableString($r['createdDate'] ?? null),
            editedBy: $this->nullableString($r['editedBy'] ?? null),
            editedAt: $this->nullableString($r['editedDate'] ?? null),
            authorizedBy: $this->nullableString($r['authorizedBy'] ?? null),
            authorizedAt: $this->nullableString($r['authorizedDate'] ?? null),
            carryOver: new DisbursementCarryOver(
                costCenterId: $this->nullableId($r['costCenterID'] ?? null),
                projectId: $this->nullableId($r['projectID'] ?? null),
                bankName: $this->nullableString($r['bankName'] ?? null),
                bankAccountName: $this->nullableString($r['bankAccountName'] ?? null),
                bankAccountNumber: $this->nullableString(is_string($r['bankAccountNumber'] ?? null) ? trim($r['bankAccountNumber']) : null),
            ),
        );
    }

    /**
     * POST /finance/disbursement/import/form rows ([ESB-040]). A row without
     * an account is the example's placeholder and is dropped.
     *
     * @return list<DisbursementLine>
     */
    public function formLinesFrom(mixed $rows): array
    {
        return array_values(array_filter(
            $this->linesFrom($rows),
            static fn (DisbursementLine $line): bool => $line->coaNo !== null,
        ));
    }

    /**
     * @return list<DisbursementLine>
     */
    private function linesFrom(mixed $rows): array
    {
        $lines = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lines[] = new DisbursementLine(
                branchId: $this->nullableId($row['branchID'] ?? null),
                branchName: $this->nullableString($row['branchName'] ?? null),
                coaNo: $this->nullableString($row['coaNo'] ?? null),
                coaDescription: $this->nullableString($row['coaDescription'] ?? null),
                description: $this->nullableString($row['description'] ?? null),
                amount: $this->amount($row['amount'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * @return list<DisbursementLinkedDocument>
     */
    private function linked(string $type, mixed $rows, string $numberField, string $dateField): array
    {
        $linked = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $number = is_array($row) ? $this->nullableString($row[$numberField] ?? null) : null;

            if ($number === null) {
                continue;
            }

            $linked[] = new DisbursementLinkedDocument(
                type: $type,
                number: $number,
                date: $this->nullableString($row[$dateField] ?? null),
                total: $this->amount($row['paymentTotal'] ?? null),
                currencyName: $this->nullableString($row['currencyName'] ?? null),
                statusName: $this->nullableString($row['status'] ?? null),
            );
        }

        return $linked;
    }

    private function amount(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
