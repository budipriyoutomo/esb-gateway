<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\Menu;
use App\Integrations\ESB\Clients\MenuClient;

/**
 * Maps ESB Core GET /menu/browse rows onto the canonical Menu ([ESB-012]).
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively: empty strings become null and an id of 0 means "none" --
 * the vendor's own example row uses both for unset values.
 */
class MenuMapper
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<Menu>
     */
    public function fromRows(array $rows): array
    {
        return array_map(fn (array $row): Menu => $this->fromRow($row), $rows);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function fromRow(array $row): Menu
    {
        return new Menu(
            provider: MenuClient::PROVIDER,
            id: $this->nullableId($row['menuID'] ?? null),
            code: $this->stringOr($row['menuCode'] ?? null, ''),
            name: $this->stringOr($row['menuName'] ?? null, ''),
            shortName: $this->nullableString($row['menuShortName'] ?? null),
            categoryId: $this->nullableId($row['categoryID'] ?? null),
            categoryName: $this->nullableString($row['categoryDesc'] ?? null),
            categoryDetailId: $this->nullableId($row['categoryDetailID'] ?? null),
            categoryDetailName: $this->nullableString($row['categoryDetailDesc'] ?? null),
            notes: $this->nullableString($row['notes'] ?? null),
        );
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
