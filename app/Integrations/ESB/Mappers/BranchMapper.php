<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\BranchDetail;
use App\Domain\Canonical\BranchSummary;
use App\Integrations\ESB\Clients\BranchClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core Branch onto the canonical branch list row and detail
 * ([ESB-051]).
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively: empty strings become null and an id of 0 means "none".
 */
class BranchMapper
{
    use ReadsEsbValues;

    /**
     * The GET /branch result, a plain list. A row without a usable id cannot
     * be opened, so it is dropped.
     *
     * @param  array<mixed>  $result
     * @return list<BranchSummary>
     */
    public function summariesFromResult(array $result): array
    {
        $branches = [];

        foreach (array_is_list($result) ? $result : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = $this->nullableId($row['branchID'] ?? null);

            if ($id === null) {
                continue;
            }

            $branches[] = new BranchSummary(
                provider: BranchClient::PROVIDER,
                id: $id,
                name: $this->stringOr($row['branchName'] ?? null, ''),
                code: $this->nullableString($row['branchCode'] ?? null),
            );
        }

        return $branches;
    }

    /**
     * The detail result, or null when it carries no branch id: the vendor's
     * example is zero-valued, so a "successful" empty result is possible.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?BranchDetail
    {
        $id = $this->nullableId($result['branchID'] ?? null);

        if ($id === null) {
            return null;
        }

        return new BranchDetail(
            provider: BranchClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['branchName'] ?? null, ''),
            code: $this->nullableString($result['branchCode'] ?? null),
            externalCode: $this->nullableString($result['extBranchCode'] ?? null),
            esbOrderName: $this->nullableString($result['branchNameESBOrder'] ?? null),
            branchTypeId: $this->nullableId($result['branchTypeID'] ?? null),
            brandId: $this->nullableId($result['brandID'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            address: $this->nullableString($result['address'] ?? null),
            phone: $this->nullableString($result['phone'] ?? null),
            postalCode: $this->nullableString($result['postalCode'] ?? null),
            latitude: $this->nullableString($result['latitude'] ?? null),
            longitude: $this->nullableString($result['longitude'] ?? null),
            openDate: $this->nullableString($result['openDate'] ?? null),
            closeDate: $this->nullableString($result['closeDate'] ?? null),
            taxName: $this->nullableString($result['taxName'] ?? null),
            taxValue: $this->number($result['taxValue'] ?? null),
            additionalTaxName: $this->nullableString($result['additionalTaxName'] ?? null),
            // Documented as a string, unlike taxValue.
            additionalTaxValue: $this->number($result['additionalTaxValue'] ?? null),
            vatName: $this->nullableString($result['vatName'] ?? null),
            timezoneId: $this->nullableId($result['timezoneID'] ?? null),
            posVersion: $this->nullableString($result['posVersion'] ?? null),
            licences: [
                'cashier' => $this->positiveIntOrNull($result['cashierCount'] ?? null),
                'order' => $this->positiveIntOrNull($result['orderCount'] ?? null),
                'tab' => $this->positiveIntOrNull($result['tabCount'] ?? null),
                'serverOnly' => $this->positiveIntOrNull($result['serverOnlyCount'] ?? null),
            ],
            printingHeader: $this->nullableString($result['printingHeader'] ?? null),
            printingFooter: $this->nullableString($result['printingFooter'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }

    /**
     * A number or numeric string, as int or float; anything else is null.
     */
    private function number(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return is_string($value) && is_numeric($value) ? $value + 0 : null;
    }
}
