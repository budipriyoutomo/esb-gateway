<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\MenuCategory;
use App\Domain\Canonical\MenuCategoryDetail;
use App\Integrations\ESB\Clients\MenuCategoryClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * ESB OMS menu categories onto the canonical shape ([ESB-047]). "" is none;
 * a row or a detail without an id or a name is dropped; a button colour is
 * kept only as a hex code (it ends up in a style attribute).
 */
class MenuCategoryMapper
{
    use ReadsEsbValues;

    /**
     * @param  array<mixed>  $rows
     * @return list<MenuCategory>
     */
    public function fromRows(array $rows): array
    {
        $categories = [];

        foreach ($rows as $row) {
            $category = is_array($row) ? $this->fromRow($row) : null;

            if ($category !== null) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    /**
     * @param  array<mixed>  $row
     */
    public function fromRow(array $row): ?MenuCategory
    {
        $id = $this->nullableId($row['menuCategoryID'] ?? null);
        $name = $this->nullableString($row['menuCategoryName'] ?? null);

        if ($id === null || $name === null) {
            return null;
        }

        return new MenuCategory(
            provider: MenuCategoryClient::PROVIDER,
            id: $id,
            name: $name,
            code: $this->nullableString($row['menuCategoryCode'] ?? null),
            salesAccount: $this->nullableString($row['salesAccount'] ?? null),
            cogsAccount: $this->nullableString($row['cogsAccount'] ?? null),
            discountAccount: $this->nullableString($row['discountAccount'] ?? null),
            description: $this->nullableString($row['description'] ?? null),
            notes: $this->nullableString($row['notes'] ?? null),
            statusName: $this->nullableString($row['status'] ?? null),
            buttonColor: $this->color($row['buttonColor'] ?? null),
            details: $this->details($row['menuCategoryDetails'] ?? null),
        );
    }

    /**
     * @return list<MenuCategoryDetail>
     */
    private function details(mixed $rows): array
    {
        $details = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = $this->nullableId($row['menuCategoryDetailID'] ?? null);
            $name = $this->nullableString($row['menuCategoryDetailName'] ?? null);

            if ($id === null || $name === null) {
                continue;
            }

            $details[] = new MenuCategoryDetail(
                id: $id,
                name: $name,
                code: $this->nullableString($row['menuCategoryDetailCode'] ?? null),
                description: $this->nullableString($row['description'] ?? null),
                maxOrderQty: is_numeric($row['maxOrderQty'] ?? null) ? (float) $row['maxOrderQty'] : null,
                statusName: $this->nullableString($row['status'] ?? null),
                buttonColor: $this->color($row['buttonColor'] ?? null),
            );
        }

        return $details;
    }

    private function color(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value) === 1 ? $value : null;
    }
}
