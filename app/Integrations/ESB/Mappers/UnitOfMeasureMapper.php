<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\UnitOfMeasure;
use App\Domain\Canonical\UnitOfMeasureSummary;
use App\Integrations\ESB\Clients\UnitOfMeasureClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core units onto the canonical UnitOfMeasure ([ESB-020]).
 */
class UnitOfMeasureMapper
{
    use ReadsEsbValues;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<UnitOfMeasureSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): UnitOfMeasureSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): UnitOfMeasureSummary
    {
        return new UnitOfMeasureSummary(
            provider: UnitOfMeasureClient::PROVIDER,
            id: $this->nullableId($row['uomID'] ?? null),
            name: $this->stringOr($row['uomName'] ?? null, ''),
            metricId: $this->nullableId($row['metricID'] ?? null),
            metricName: $this->nullableString($row['metricName'] ?? null),
            notes: $this->nullableString($row['notes'] ?? null),
            taxCode: $this->nullableString($row['refTaxCode'] ?? null),
            taxName: $this->nullableString($row['refTaxName'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no id.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?UnitOfMeasure
    {
        $id = $this->nullableId($result['uomID'] ?? null);

        if ($id === null) {
            return null;
        }

        return new UnitOfMeasure(
            provider: UnitOfMeasureClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['uomName'] ?? null, ''),
            metricId: $this->nullableId($result['metricID'] ?? null),
            metricName: $this->nullableString($result['metricName'] ?? null),
            notes: $this->nullableString($result['notes'] ?? null),
            taxId: $this->nullableId($result['refTaxID'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }
}
