<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\Promotion;
use App\Integrations\ESB\Clients\PromotionClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * ESB OMS promotions onto the canonical shape ([ESB-049]). The rows come
 * from a bare JSON array (ADR-027) and are read defensively: a row without
 * an id is dropped, "" is none, the payment method is one object or a list.
 */
class PromotionMapper
{
    use ReadsEsbValues;

    /**
     * @param  array<mixed>  $rows
     * @return list<Promotion>
     */
    public function fromRows(array $rows): array
    {
        $promotions = [];

        foreach ($rows as $row) {
            $id = is_array($row) ? $this->nullableId($row['promotionID'] ?? null) : null;

            if ($id === null) {
                continue;
            }

            $promotions[] = new Promotion(
                provider: PromotionClient::PROVIDER,
                id: $id,
                code: $this->nullableString($row['promotionCode'] ?? null),
                typeName: $this->nullableString($row['promotionTypeDesc'] ?? null),
                notes: $this->nullableString($row['notes'] ?? null),
                discount: $this->number($row['discount'] ?? null),
                minSubtotal: $this->number($row['minSubtotal'] ?? null),
                startAt: $this->nullableString($row['startDate'] ?? null),
                endAt: $this->nullableString($row['endDate'] ?? null),
                shownOnEso: $this->flag($row['flagShow'] ?? null),
                branches: $this->branches($row['branches'] ?? null),
                selfOrderPaymentMethods: $this->names($row['selfOrderPaymentMethods'] ?? null, 'selfOrderPaymentMethodName'),
                paymentMethods: $this->names($this->listOf($row['paymentMethod'] ?? null), 'paymentMethodName'),
                scope: $this->scope($row['promotionCategory'] ?? null),
            );
        }

        return $promotions;
    }

    /**
     * @return list<array{id: int|null, code: string|null, name: string}>
     */
    private function branches(mixed $rows): array
    {
        $branches = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $name = is_array($row) ? $this->nullableString($row['branchName'] ?? null) : null;

            if ($name !== null) {
                $branches[] = [
                    'id' => $this->nullableId($row['branchID'] ?? null),
                    'code' => $this->nullableString($row['branchCode'] ?? null),
                    'name' => $name,
                ];
            }
        }

        return $branches;
    }

    /**
     * @return list<string>
     */
    private function names(mixed $rows, string $field): array
    {
        $names = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $name = is_array($row) ? $this->nullableString($row[$field] ?? null) : null;

            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * The vendor's example has one object where its field list has an array.
     *
     * @return array<mixed>
     */
    private function listOf(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }

    /**
     * @return array{menuCategories: int, menuCategoryDetails: int, menus: int}
     */
    private function scope(mixed $rows): array
    {
        $scope = ['menuCategories' => 0, 'menuCategoryDetails' => 0, 'menus' => 0];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $scope['menuCategories'] += $this->nullableId($row['menuCategoryID'] ?? null) === null ? 0 : 1;
            $scope['menuCategoryDetails'] += $this->nullableId($row['menuCategoryDetailID'] ?? null) === null ? 0 : 1;
            $scope['menus'] += $this->nullableId($row['menuID'] ?? null) === null ? 0 : 1;
        }

        return $scope;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
