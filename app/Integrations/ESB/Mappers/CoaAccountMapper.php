<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\CoaAccount;
use App\Domain\Canonical\CoaAccountQuery;
use App\Domain\Canonical\CoaAccountSummary;
use App\Domain\Canonical\CoaMapping;
use App\Integrations\ESB\Clients\CoaAccountClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core COA rows onto the canonical CoaAccount ([ESB-025]).
 */
class CoaAccountMapper
{
    use ReadsEsbValues;

    /**
     * Rows in chart order: by orderList, then number. Rows without a number
     * are dropped.
     *
     * @param  array<mixed>  $rows
     * @return list<CoaAccountSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        $accounts = [];

        foreach ($rows as $row) {
            $coaNo = is_array($row) ? $this->nullableString($row['coaNo'] ?? null) : null;

            if ($coaNo === null) {
                continue;
            }

            $accounts[] = new CoaAccountSummary(
                provider: CoaAccountClient::PROVIDER,
                coaNo: $coaNo,
                description: $this->stringOr($row['description'] ?? null, ''),
                level: $this->level($row['coaLevel'] ?? null),
                order: is_numeric($row['orderList'] ?? null) ? (int) $row['orderList'] : null,
                branchId: $this->nullableId($row['branchID'] ?? null),
                currency: $this->nullableString($row['currency'] ?? null),
                moduleAccount: $this->flag($row['flagModule'] ?? null),
                allowBudget: $this->flag($row['allowBudget'] ?? null),
                active: $this->flag($row['flagActive'] ?? null),
            );
        }

        usort($accounts, static fn (CoaAccountSummary $a, CoaAccountSummary $b): int => [$a->order ?? PHP_INT_MAX, $a->coaNo] <=> [$b->order ?? PHP_INT_MAX, $b->coaNo]);

        return $accounts;
    }

    /**
     * The detail result, or null when it carries no number.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?CoaAccount
    {
        $coaNo = $this->nullableString($result['coaNo'] ?? null);

        if ($coaNo === null) {
            return null;
        }

        $mappings = [];

        foreach (is_array($result['mapCoa'] ?? null) ? $result['mapCoa'] : [] as $map) {
            if (is_array($map)) {
                $mappings[] = new CoaMapping(
                    id: $this->nullableId($map['ID'] ?? null),
                    key: $this->nullableString($map['key'] ?? null),
                    typeId: $this->nullableId($map['mapCoaTypeID'] ?? null),
                    currencyId: $this->nullableId($map['currencyID'] ?? null),
                );
            }
        }

        return new CoaAccount(
            provider: CoaAccountClient::PROVIDER,
            coaNo: $coaNo,
            description: $this->stringOr($result['description'] ?? null, ''),
            level: $this->level($result['coaLevel'] ?? null),
            order: is_numeric($result['orderList'] ?? null) ? (int) $result['orderList'] : null,
            branchId: $this->nullableId($result['branchID'] ?? null),
            currency: $this->nullableString($result['currency'] ?? null),
            moduleAccount: $this->flag($result['flagModule'] ?? null),
            allowBudget: $this->flag($result['allowBudget'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            additionalInfo: $this->nullableString($result['additionalInfo'] ?? null),
            releasePaymentCode: $this->nullableString($result['releasePaymentCode'] ?? null),
            mappings: $mappings,
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }

    private function level(mixed $value): ?int
    {
        $level = is_numeric($value) ? (int) $value : null;

        return in_array($level, CoaAccountQuery::LEVELS, true) ? $level : null;
    }
}
