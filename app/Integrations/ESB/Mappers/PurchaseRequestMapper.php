<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\Branch;
use App\Domain\Canonical\PurchaseRequest;
use App\Domain\Canonical\PurchaseRequestDraft;
use App\Domain\Canonical\PurchaseRequestDraftLine;
use App\Domain\Canonical\PurchaseRequestLine;
use App\Domain\Canonical\PurchaseRequestSummary;
use App\Domain\Canonical\PurchaseRequestTemplateLine;
use App\Domain\Canonical\RequestTemplate;
use App\Integrations\ESB\Clients\PurchaseRequestClient;

/**
 * Maps the ESB's purchase request list rows and detail result onto the
 * canonical shapes.
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively. Empty strings become null: the vendor sends "" rather than
 * null for unset text fields such as `editedByName`.
 */
class PurchaseRequestMapper
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function summaryFromRow(array $row): PurchaseRequestSummary
    {
        return new PurchaseRequestSummary(
            provider: PurchaseRequestClient::PROVIDER,
            number: $this->stringOr($row['purchaseRequestNum'] ?? null, ''),
            date: $this->nullableString($row['purchaseRequestDate'] ?? null),
            requiredDate: $this->nullableString($row['requiredDate'] ?? null),
            createdAt: $this->nullableString($row['createdDate'] ?? null),
            branchId: $this->nullableId($row['branchID'] ?? null),
            branchName: $this->nullableString($row['branchName'] ?? null),
            templateId: $this->nullableId($row['requestTemplateID'] ?? null),
            templateName: $this->nullableString($row['requestTemplateName'] ?? null),
            statusId: $this->nullableInt($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
            notes: $this->nullableString($row['additionalInfo'] ?? null),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<PurchaseRequestSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): PurchaseRequestSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): PurchaseRequest
    {
        return new PurchaseRequest(
            provider: PurchaseRequestClient::PROVIDER,
            number: $this->stringOr($result['purchaseRequestNum'] ?? null, ''),
            date: $this->nullableString($result['purchaseRequestDate'] ?? null),
            requiredDate: $this->nullableString($result['requiredDate'] ?? null),
            branchId: $this->nullableId($result['branchID'] ?? null),
            branchName: $this->nullableString($result['branchName'] ?? null),
            costCenterId: $this->nullableId($result['costCenterID'] ?? null),
            costCenterName: $this->nullableString($result['costCenterName'] ?? null),
            projectId: $this->nullableId($result['projectID'] ?? null),
            projectName: $this->nullableString($result['projectName'] ?? null),
            templateId: $this->nullableId($result['requestTemplateID'] ?? null),
            isTemplate: ($result['isTemplate'] ?? false) === true,
            statusId: $this->nullableInt($result['statusID'] ?? null),
            statusName: $this->nullableString($result['statusName'] ?? null),
            notes: $this->nullableString($result['additionalInfo'] ?? null),
            createdByName: $this->nullableString($result['createdByName'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedByName: $this->nullableString($result['editedByName'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
            authorizedByName: $this->nullableString($result['authorizedByName'] ?? null),
            authorizedAt: $this->nullableString($result['authorizedDate'] ?? null),
            lines: $this->linesFrom($result['purchaseRequestDetails'] ?? null),
            purchaseNumbers: $this->documentNumbers($result['purchaseNums'] ?? null),
            transferNumbers: $this->documentNumbers($result['transferNums'] ?? null),
        );
    }

    /**
     * GET /branch rows. A row without an id cannot be picked, so it is skipped.
     *
     * @param  array<mixed>  $rows
     * @return list<Branch>
     */
    public function branchesFrom(array $rows): array
    {
        $branches = [];

        foreach ($rows as $row) {
            $id = is_array($row) ? $this->nullableId($row['branchID'] ?? null) : null;

            if ($id === null) {
                continue;
            }

            $branches[] = new Branch(
                id: $id,
                name: $this->stringOr($row['branchName'] ?? null, ''),
                code: $this->nullableString($row['branchCode'] ?? null),
            );
        }

        return $branches;
    }

    /**
     * GET /document-template/list rows.
     *
     * @param  array<mixed>  $rows
     * @return list<RequestTemplate>
     */
    public function templatesFrom(array $rows): array
    {
        $templates = [];

        foreach ($rows as $row) {
            $id = is_array($row) ? $this->nullableId($row['requestTemplateID'] ?? null) : null;

            if ($id === null) {
                continue;
            }

            $templates[] = new RequestTemplate(
                id: $id,
                name: $this->stringOr($row['requestTemplateName'] ?? null, ''),
            );
        }

        return $templates;
    }

    /**
     * GET /purchase/purchase-request/template-details rows.
     *
     * Create requires productDetailID and requestProcessID on every line, so
     * a row missing either could only produce a request the ERP refuses; it is
     * dropped here rather than offered.
     *
     * @param  array<mixed>  $rows
     * @return list<PurchaseRequestTemplateLine>
     */
    public function templateLinesFrom(array $rows): array
    {
        $lines = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $productDetailId = $this->nullableId($row['productDetailID'] ?? null);
            $requestProcessId = $this->nullableId($row['requestProcessID'] ?? null);

            if ($productDetailId === null || $requestProcessId === null) {
                continue;
            }

            $lines[] = new PurchaseRequestTemplateLine(
                productDetailId: $productDetailId,
                requestProcessId: $requestProcessId,
                requestProcessName: $this->nullableString($row['requestProcessName'] ?? null),
                productId: $this->nullableId($row['productID'] ?? null),
                productCode: $this->stringOr($row['productCode'] ?? null, ''),
                productName: $this->stringOr($row['productName'] ?? null, ''),
                uomName: $this->nullableString($row['uomName'] ?? null),
                group: $this->nullableString($row['requestTemplateDetailName'] ?? null),
                stock: $this->nullableFloat($row['stock'] ?? null),
                suggestionQty: $this->nullableFloat($row['suggestionQty'] ?? null),
                minQty: $this->nullableFloat($row['minBranchProductQty'] ?? null),
                maxQty: $this->nullableFloat($row['maxBranchProductQty'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * The body of POST /purchase/purchase-request and
     * PUT /purchase/purchase-request/{purchaseRequestNum}; both take the same
     * schema. Optional references are omitted rather than sent as 0.
     *
     * @return array<string, mixed>
     */
    public function toRequestBody(PurchaseRequestDraft $draft): array
    {
        return array_filter([
            'branchID' => $draft->branchId,
            'purchaseRequestDate' => $draft->date,
            'requiredDate' => $draft->requiredDate,
            'isTemplate' => $draft->isTemplate,
            'purchaseRequestDetails' => array_map(
                static fn (PurchaseRequestDraftLine $line): array => array_filter([
                    'productDetailID' => $line->productDetailId,
                    'requestProcessID' => $line->requestProcessId,
                    'qty' => $line->qty,
                    'notes' => $line->notes,
                ], static fn (mixed $value): bool => $value !== null),
                $draft->lines,
            ),
            'requestTemplateID' => $draft->templateId,
            'costCenterID' => $draft->costCenterId,
            'projectID' => $draft->projectId,
            'additionalInfo' => $draft->notes,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return list<PurchaseRequestLine>
     */
    private function linesFrom(mixed $details): array
    {
        if (! is_array($details)) {
            return [];
        }

        $lines = [];

        foreach ($details as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lines[] = new PurchaseRequestLine(
                productId: $this->nullableId($row['productID'] ?? null),
                productDetailId: $this->nullableId($row['productDetailID'] ?? null),
                productCode: $this->stringOr($row['productCode'] ?? null, ''),
                productName: $this->stringOr($row['productName'] ?? null, ''),
                uomName: $this->nullableString($row['uomName'] ?? null),
                qty: $this->nullableFloat($row['qty'] ?? null) ?? 0.0,
                notes: $this->nullableString($row['notes'] ?? null),
                stock: $this->nullableFloat($row['stock'] ?? null),
                suggestionQty: $this->nullableFloat($row['suggestionQty'] ?? null),
                minQty: $this->nullableFloat($row['minBranchProductQty'] ?? null),
                maxQty: $this->nullableFloat($row['maxBranchProductQty'] ?? null),
                requestProcessId: $this->nullableId($row['requestProcessID'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function documentNumbers(mixed $numbers): array
    {
        if (! is_array($numbers)) {
            return [];
        }

        return array_values(array_filter(
            $numbers,
            static fn (mixed $number): bool => is_string($number) && $number !== '',
        ));
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * A reference id, where the vendor's 0 means "none attached".
     *
     * The vendor sends `projectID: 0` with an empty `projectName` when a
     * request has no project; treating 0 as a real id would invent one.
     */
    private function nullableId(mixed $value): ?int
    {
        $id = $this->nullableInt($value);

        return $id !== null && $id > 0 ? $id : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function stringOr(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
