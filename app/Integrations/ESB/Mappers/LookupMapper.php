<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\LookupKind;
use App\Domain\Canonical\LookupOption;

/**
 * ESB Core reference rows -> picker options ([ESB-019]).
 *
 * The vendor's examples are zero-valued, so 0 and "" mean "not there": a row
 * with no id or no label is dropped rather than offered as a blank choice.
 */
class LookupMapper
{
    /**
     * @param  array<mixed>  $rows
     * @return list<LookupOption>
     */
    public function optionsFrom(LookupKind $kind, array $rows): array
    {
        $options = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $option = match ($kind) {
                LookupKind::Branches => $this->option($this->id($row['branchID'] ?? null), $row['branchName'] ?? null, $row['branchCode'] ?? null),
                LookupKind::Suppliers => $this->option($this->id($row['supplierID'] ?? null), $row['supplierName'] ?? null, $row['supplierCode'] ?? null),
                LookupKind::Locations => $this->option(
                    $this->id($row['locationID'] ?? null),
                    $row['locationName'] ?? null,
                    meta: ['branchId' => $this->intOrNull($row['branchID'] ?? null)],
                ),
                LookupKind::CoaAccounts => $this->account($row, []),
                LookupKind::CashBankAccounts => $this->account($row, ['branchId' => $this->intOrNull($row['branchID'] ?? null)]),
                LookupKind::Statuses => $this->option($this->id($row['statusID'] ?? null), $row['statusName'] ?? null),
                LookupKind::TransferProducts => $this->option(
                    $this->id($row['productDetailID'] ?? null),
                    $row['productName'] ?? null,
                    $row['productCode'] ?? null,
                    [
                        'productId' => $this->intOrNull($row['productID'] ?? null),
                        'uomName' => $this->text($row['uomName'] ?? null),
                        'stockQty' => is_numeric($row['stockQty'] ?? null) ? $row['stockQty'] + 0 : null,
                    ],
                ),
            };

            if ($option !== null) {
                $options[] = $option;
            }
        }

        return $options;
    }

    /**
     * An account is keyed, and coded, by its COA number.
     *
     * @param  array<mixed>  $row
     * @param  array<string, int|float|string|null>  $meta
     */
    private function account(array $row, array $meta): ?LookupOption
    {
        $coaNo = $this->text($row['coaNo'] ?? null);

        return $this->option($coaNo, $row['description'] ?? null, $coaNo, $meta);
    }

    /**
     * @param  array<string, int|float|string|null>  $meta
     */
    private function option(?string $value, mixed $label, mixed $code = null, array $meta = []): ?LookupOption
    {
        $label = $this->text($label);

        if ($value === null || $label === null) {
            return null;
        }

        return new LookupOption(
            value: $value,
            label: $label,
            code: $this->text($code),
            meta: array_filter($meta, static fn (mixed $v): bool => $v !== null),
        );
    }

    /**
     * A positive integer id as a string, or null.
     */
    private function id(mixed $value): ?string
    {
        $id = $this->intOrNull($value);

        return $id !== null && $id > 0 ? (string) $id : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
