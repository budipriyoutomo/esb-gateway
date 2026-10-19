<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\BillOfMaterial;
use App\Domain\Canonical\BillOfMaterialCost;
use App\Domain\Canonical\BillOfMaterialLine;
use App\Domain\Canonical\BillOfMaterialReplacement;
use App\Domain\Canonical\BillOfMaterialSubstitute;
use App\Domain\Canonical\BillOfMaterialSummary;
use App\Integrations\ESB\Clients\BillOfMaterialClient;

/**
 * Maps ESB Core Master Bill of Material onto the canonical BOM ([ESB-014]).
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively: empty strings become null, an id of 0 means "none", and a
 * nested entry without its product is skipped. The vendor spells substitutes
 * `subtitution`; that spelling stops here.
 */
class BillOfMaterialMapper
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<BillOfMaterialSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): BillOfMaterialSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): BillOfMaterialSummary
    {
        return new BillOfMaterialSummary(
            provider: BillOfMaterialClient::PROVIDER,
            id: $this->nullableId($row['bomID'] ?? null),
            code: $this->nullableString($row['bomCode'] ?? null),
            name: $this->stringOr($row['bomName'] ?? null, ''),
            typeId: $this->nullableId($row['bomTypeID'] ?? null),
            typeName: $this->nullableString($row['bomTypeName'] ?? null),
            productName: $this->nullableString($row['productName'] ?? null),
            uomName: $this->nullableString($row['uomName'] ?? null),
            notes: $this->nullableString($row['notes'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no BOM id -- the vendor's
     * example is zero-valued, so a "successful" empty result is possible.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?BillOfMaterial
    {
        $id = $this->nullableId($result['bomID'] ?? null);

        if ($id === null) {
            return null;
        }

        return new BillOfMaterial(
            provider: BillOfMaterialClient::PROVIDER,
            id: $id,
            code: $this->nullableString($result['bomCode'] ?? null),
            name: $this->stringOr($result['bomName'] ?? null, ''),
            typeId: $this->nullableId($result['bomTypeID'] ?? null),
            typeName: $this->nullableString($result['bomTypeName'] ?? null),
            accessType: $this->nullableString($result['accessType'] ?? null),
            productId: $this->nullableId($result['productID'] ?? null),
            productCode: $this->nullableString($result['productCode'] ?? null),
            productName: $this->nullableString($result['productName'] ?? null),
            uomName: $this->nullableString($result['uomName'] ?? null),
            notes: $this->nullableString($result['notes'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            materialCostTotal: $this->nullableFloat($result['bomCostTotal'] ?? null),
            otherCostTotal: $this->nullableFloat($result['otherCostTotal'] ?? null),
            weightFactorTotal: $this->nullableFloat($result['weightFactorTotal'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
            lines: $this->linesFrom($result['bomDetails'] ?? null),
            costs: $this->costsFrom($result['bomCosts'] ?? null),
            replacements: $this->replacementsFrom($result['bomReplacements'] ?? null),
        );
    }

    /**
     * @return list<BillOfMaterialLine>
     */
    private function linesFrom(mixed $lines): array
    {
        $mapped = [];

        foreach ($this->entries($lines) as $entry) {
            $productDetailId = $this->nullableId($entry['productDetailID'] ?? null);
            $productName = $this->nullableString($entry['productName'] ?? null);

            if ($productDetailId === null || $productName === null) {
                continue;
            }

            $mapped[] = new BillOfMaterialLine(
                lineId: $this->nullableId($entry['ID'] ?? null),
                productDetailId: $productDetailId,
                productCode: $this->nullableString($entry['productCode'] ?? null),
                productName: $productName,
                uomName: $this->nullableString($entry['uomName'] ?? null),
                qty: $this->nullableFloat($entry['qty'] ?? null),
                uomQty: $this->nullableFloat($entry['uomQty'] ?? null),
                conversionQty: $this->nullableFloat($entry['convertionQty'] ?? null),
                lastCost: $this->nullableFloat($entry['lastHpp'] ?? null),
                price: $this->nullableFloat($entry['price'] ?? null),
                printGroup: $this->nullableString($entry['printGroup'] ?? null),
                tolerancePercent: $this->nullableFloat($entry['tolerancePercent'] ?? null),
                yieldPercent: $this->nullableFloat($entry['yieldPercent'] ?? null),
                weightFactor: $this->nullableFloat($entry['weightFactor'] ?? null),
                substitutes: $this->substitutesFrom($entry['subtitution'] ?? null),
                manufacturingSubstitutes: $this->namesFrom($entry['subtitutionManufacturing'] ?? null, 'productName'),
            );
        }

        return $mapped;
    }

    /**
     * @return list<BillOfMaterialSubstitute>
     */
    private function substitutesFrom(mixed $substitutes): array
    {
        $mapped = [];

        foreach ($this->entries($substitutes) as $entry) {
            $productDetailId = $this->nullableId($entry['productDetailID'] ?? null);

            if ($productDetailId === null) {
                continue;
            }

            $mapped[] = new BillOfMaterialSubstitute(
                productDetailId: $productDetailId,
                productCode: $this->nullableString($entry['productCodeSubtitution'] ?? null),
                productName: $this->nullableString($entry['productNameSubtitution'] ?? null),
                uomName: $this->nullableString($entry['uomNameSubtitution'] ?? null),
                order: $this->nullableInt($entry['orderList'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * @return list<BillOfMaterialCost>
     */
    private function costsFrom(mixed $costs): array
    {
        $mapped = [];

        foreach ($this->entries($costs) as $entry) {
            $mapped[] = new BillOfMaterialCost(
                accountNo: $this->nullableString($entry['coaNo'] ?? null),
                description: $this->nullableString($entry['costDescription'] ?? null),
                total: $this->nullableFloat($entry['costTotal'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * @return list<BillOfMaterialReplacement>
     */
    private function replacementsFrom(mixed $replacements): array
    {
        $mapped = [];

        foreach ($this->entries($replacements) as $entry) {
            $mapped[] = new BillOfMaterialReplacement(
                menuCode: $this->nullableString($entry['menuCode'] ?? null),
                menuName: $this->nullableString($entry['menuName'] ?? null),
                productCode: $this->nullableString($entry['productCode'] ?? null),
                productName: $this->nullableString($entry['productName'] ?? null),
                replacementProductCode: $this->nullableString($entry['replacementProductCode'] ?? null),
                replacementProductName: $this->nullableString($entry['replacementProductName'] ?? null),
                uomName: $this->nullableString($entry['uomName'] ?? null),
                qty: $this->nullableFloat($entry['qty'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * @return list<string>
     */
    private function namesFrom(mixed $entries, string $key): array
    {
        $names = [];

        foreach ($this->entries($entries) as $entry) {
            $name = $this->nullableString($entry[$key] ?? null);

            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * The array entries of a list, or nothing when it is not one.
     *
     * @return list<array<mixed>>
     */
    private function entries(mixed $list): array
    {
        if (! is_array($list)) {
            return [];
        }

        return array_values(array_filter($list, 'is_array'));
    }

    /**
     * The vendor documents flagActive as 1/0. Anything else is unknown, not
     * false.
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

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

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
