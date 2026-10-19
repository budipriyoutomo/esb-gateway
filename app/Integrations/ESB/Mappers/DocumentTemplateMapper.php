<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\DocumentTemplate;
use App\Domain\Canonical\DocumentTemplateDraft;
use App\Domain\Canonical\DocumentTemplateDraftLine;
use App\Domain\Canonical\DocumentTemplateLine;
use App\Domain\Canonical\DocumentTemplateSummary;
use App\Domain\Canonical\DocumentTemplateType;
use App\Domain\Canonical\ProductDetailOption;
use App\Integrations\ESB\Clients\DocumentTemplateClient;

/**
 * Maps ESB Core Master Document Template onto the canonical DocumentTemplate
 * ([ESB-016]).
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively: empty strings become null and an id of 0 means "none".
 */
class DocumentTemplateMapper
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<DocumentTemplateSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): DocumentTemplateSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): DocumentTemplateSummary
    {
        return new DocumentTemplateSummary(
            provider: DocumentTemplateClient::PROVIDER,
            id: $this->nullableId($row['requestTemplateID'] ?? null),
            name: $this->stringOr($row['requestTemplateName'] ?? null, ''),
            typeNames: $this->commaSeparated($row['requestTemplateTypeNames'] ?? null),
            branchNames: $this->commaSeparated($row['branchNames'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no template id -- the
     * vendor's example is zero-valued, so a "successful" empty result is
     * possible.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?DocumentTemplate
    {
        $id = $this->nullableId($result['requestTemplateID'] ?? null);

        if ($id === null) {
            return null;
        }

        $typeId = $this->nullableId($result['requestTemplateTypeID'] ?? null);

        return new DocumentTemplate(
            provider: DocumentTemplateClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['requestTemplateName'] ?? null, ''),
            type: $typeId === null ? null : DocumentTemplateType::tryFrom($typeId),
            active: $this->flag($result['flagActive'] ?? null),
            autoTransfer: $this->flag($result['flagAutoTransfer'] ?? null),
            allowZeroPricelist: $this->flag($result['allowZeroPricelist'] ?? null),
            branchIds: $this->ids($result['branchIDs'] ?? null),
            costCenterIds: $this->ids($result['costCenterIDs'] ?? null),
            lines: $this->lines($result['requestTemplateDetails'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
            syncedAt: $this->nullableString($result['syncDate'] ?? null),
        );
    }

    /**
     * The POST /document-template body. Optional fields the draft does not
     * carry are omitted, not sent as null.
     *
     * @return array<string, mixed>
     */
    public function toRequestBody(DocumentTemplateDraft $draft): array
    {
        return [
            'requestTemplateName' => $draft->name,
            'requestTemplateTypeID' => $draft->type->value,
            'flagActive' => $draft->active ? 1 : 0,
            'branchIDs' => $draft->branchIds,
            'requestTemplateDetails' => array_map(
                static fn (DocumentTemplateDraftLine $line): array => array_filter([
                    'productDetailID' => $line->productDetailId,
                    'requestTemplateDetailName' => $line->name,
                    'purposeID' => $line->purposeId,
                ], static fn (mixed $value): bool => $value !== null),
                $draft->lines,
            ),
        ];
    }

    /**
     * The new template's id from the create result. The vendor says the
     * create "returns its ID" but its example result is {} and names no key;
     * requestTemplateID, the id's name everywhere else, is assumed (TBD). The
     * id is later put in a URL, so anything but a positive integer is absent.
     *
     * @param  array<mixed>  $result
     */
    public function createdIdFrom(array $result): ?int
    {
        $id = $result['requestTemplateID'] ?? null;

        if (is_string($id) && ctype_digit($id)) {
            $id = (int) $id;
        }

        return is_int($id) && $id > 0 ? $id : null;
    }

    /**
     * Product ids from GET /product/list rows. The ESB matches productCode
     * partially (staging, 2026-10-15), so with $exactCode only rows whose
     * code is that code, ignoring case and surrounding space, count.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<int>
     */
    public function productIdsFromRows(array $rows, ?string $exactCode = null): array
    {
        $wanted = $exactCode === null ? null : strtoupper(trim($exactCode));
        $ids = [];

        foreach ($rows as $row) {
            $id = $this->nullableId($row['productID'] ?? null);

            if ($id === null) {
                continue;
            }

            if ($wanted !== null && strtoupper(trim($this->stringOr($row['productCode'] ?? null, ''))) !== $wanted) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * A product's units from GET /product/{productID}: each productDetails[]
     * row is the product in one unit. Inactive units are left out -- the ERP
     * would not take them on a template.
     *
     * @param  array<mixed>  $product
     * @return list<ProductDetailOption>
     */
    public function productDetailsFromProduct(array $product): array
    {
        $details = $product['productDetails'] ?? null;

        if (! is_array($details)) {
            return [];
        }

        $options = [];

        foreach ($details as $detail) {
            if (! is_array($detail) || $this->flag($detail['flagActive'] ?? null) !== true) {
                continue;
            }

            $id = $this->nullableId($detail['productDetailID'] ?? null);

            // Without its id a unit cannot become a template line.
            if ($id === null) {
                continue;
            }

            $options[] = new ProductDetailOption(
                productDetailId: $id,
                productCode: $this->nullableString($product['productCode'] ?? null),
                productName: $this->stringOr($product['productName'] ?? null, ''),
                uomName: $this->nullableString($detail['uomName'] ?? null),
            );
        }

        return $options;
    }

    /**
     * @param  array<mixed>  $product
     */
    public function productIsActive(array $product): ?bool
    {
        return $this->flag($product['flagActive'] ?? null);
    }

    /**
     * @return list<DocumentTemplateLine>
     */
    private function lines(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $lines = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lines[] = new DocumentTemplateLine(
                lineId: $this->nullableId($row['ID'] ?? null),
                productDetailId: $this->nullableId($row['productDetailID'] ?? null),
                productCode: $this->nullableString($row['productCode'] ?? null),
                productName: $this->stringOr($row['productName'] ?? null, ''),
                name: $this->nullableString($row['requestTemplateDetailName'] ?? null),
                uomName: $this->nullableString($row['uomName'] ?? null),
                categoryName: $this->nullableString($row['categoryName'] ?? null),
                purposeId: $this->nullableId($row['purposeID'] ?? null),
                lastCost: $this->nullableFloat($row['lastHpp'] ?? null),
                active: $this->flag($row['flagActive'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * The list row carries type and branch names as one comma-separated
     * string.
     *
     * @return list<string>
     */
    private function commaSeparated(mixed $value): array
    {
        if (! is_string($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $value)),
            static fn (string $part): bool => $part !== '',
        ));
    }

    /**
     * @return list<int>
     */
    private function ids(mixed $list): array
    {
        if (! is_array($list)) {
            return [];
        }

        $ids = [];

        foreach ($list as $value) {
            $id = $this->nullableId($value);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * The vendor documents these flags as 1/0 integers; a boolean is accepted
     * too. Anything else is unknown, not false.
     */
    private function flag(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match ($value) {
            1, '1' => true,
            0, '0' => false,
            default => null,
        };
    }

    private function nullableId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
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
