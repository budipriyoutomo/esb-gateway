<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\Purpose;
use App\Domain\Canonical\PurposeSummary;
use App\Integrations\ESB\Clients\PurposeClient;

/**
 * Maps ESB Core Purpose onto the canonical Purpose ([ESB-015]).
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively: empty strings become null and an id of 0 means "none".
 */
class PurposeMapper
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<PurposeSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): PurposeSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): PurposeSummary
    {
        return new PurposeSummary(
            provider: PurposeClient::PROVIDER,
            id: $this->nullableId($row['purposeID'] ?? null),
            name: $this->stringOr($row['purposeName'] ?? null, ''),
            accountNo: $this->nullableString($row['purposeCoaNo'] ?? null),
            accountName: $this->nullableString($row['purposeAccount'] ?? null),
            appliesTo: $this->strings($row['purposeAppliedTo'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no purpose id -- the
     * vendor's example is zero-valued, so a "successful" empty result is
     * possible.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?Purpose
    {
        $id = $this->nullableId($result['purposeID'] ?? null);

        if ($id === null) {
            return null;
        }

        return new Purpose(
            provider: PurposeClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['purposeName'] ?? null, ''),
            accountNo: $this->nullableString($result['purposeCoaNo'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            transactionTypeIds: $this->ids($result['transactionTypeIDs'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $list): array
    {
        if (! is_array($list)) {
            return [];
        }

        $strings = [];

        foreach ($list as $value) {
            $string = $this->nullableString($value);

            if ($string !== null) {
                $strings[] = $string;
            }
        }

        return $strings;
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
     * Rows document flagActive as a boolean; 1/0 is accepted too. Anything
     * else is unknown, not false.
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

    private function stringOr(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
