<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\MasterMenu;
use App\Domain\Canonical\MasterMenuExtra;
use App\Domain\Canonical\MasterMenuPackageGroup;
use App\Domain\Canonical\MasterMenuPackageItem;
use App\Domain\Canonical\MasterMenuReference;
use App\Domain\Canonical\MasterMenuTemplatePrice;
use App\Integrations\ESB\Clients\MasterMenuClient;

/**
 * Maps ESB OMS Get Menu rows onto the canonical MasterMenu ([ESB-013]).
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively: empty strings become null, an id of 0 means "none", and a
 * nested entry without its identifying fields is skipped. `menuImage` and
 * `menuIconUrl` point at vendor hosts and are not carried (ADR-006).
 */
class MasterMenuMapper
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<MasterMenu>
     */
    public function fromRows(array $rows): array
    {
        return array_map(fn (array $row): MasterMenu => $this->fromRow($row), $rows);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function fromRow(array $row): MasterMenu
    {
        $packages = is_array($row['menuPackages'] ?? null) ? $row['menuPackages'] : [];

        return new MasterMenu(
            provider: MasterMenuClient::PROVIDER,
            id: $this->nullableId($row['menuID'] ?? null),
            code: $this->stringOr($row['menuCode'] ?? null, ''),
            name: $this->stringOr($row['menuName'] ?? null, ''),
            shortName: $this->nullableString($row['menuShortName'] ?? null),
            alternativeName: $this->nullableString($row['alternativeMenuName'] ?? null),
            categoryName: $this->nullableString($row['categoryDetail'] ?? null),
            bomName: $this->nullableString($row['bomName'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
            taxLabel: $this->nullableString($row['flagTax'] ?? null),
            otherTax: $this->flag($row['flagOtherTax'] ?? null),
            openPrice: $this->flag($row['flagOpenPrice'] ?? null),
            printZeroValue: $this->flag($row['printZeroValue'] ?? null),
            zeroValueText: $this->nullableString($row['zeroValueText'] ?? null),
            salesAccount: $this->nullableString($row['salesAccount'] ?? null),
            cogsAccount: $this->nullableString($row['cogsAccount'] ?? null),
            discountAccount: $this->nullableString($row['discountAccount'] ?? null),
            description: $this->nullableString($row['description'] ?? null),
            notes: $this->nullableString($row['notes'] ?? null),
            buttonColor: $this->color($row['themeMenuOnPos'] ?? null),
            separatePrintPackage: $this->flag($packages['flagSeparatePrintPackage'] ?? null),
            separateTaxCalculation: $this->flag($packages['flagSeparateTaxCalculation'] ?? null),
            templates: $this->templatesFrom($row['menuTemplates'] ?? null),
            packageGroups: $this->groupsFrom($packages['menuGroup'] ?? null),
            extras: $this->extrasFrom($row['menuExtras'] ?? null),
            icons: $this->namesFrom($row['menuIcons'] ?? null, 'menuIconName'),
            tags: $this->namesFrom($row['menuTags'] ?? null, 'tagName'),
            relatedMenus: $this->referencesFrom($row['relatedMenus'] ?? null),
        );
    }

    /**
     * @return list<MasterMenuTemplatePrice>
     */
    private function templatesFrom(mixed $templates): array
    {
        $mapped = [];

        foreach ($this->entries($templates) as $entry) {
            $id = $this->nullableId($entry['menuTemplateID'] ?? null);

            if ($id === null) {
                continue;
            }

            $mapped[] = new MasterMenuTemplatePrice(
                templateId: $id,
                templateName: $this->nullableString($entry['menuTemplateName'] ?? null),
                active: $this->flag($entry['flagActive'] ?? null),
                price: $this->nullableFloat($entry['price'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * @return list<MasterMenuPackageGroup>
     */
    private function groupsFrom(mixed $groups): array
    {
        $mapped = [];

        foreach ($this->entries($groups) as $entry) {
            $name = $this->nullableString($entry['menuGroupName'] ?? null);

            if ($name === null) {
                continue;
            }

            $mapped[] = new MasterMenuPackageGroup(
                groupId: $this->nullableId($entry['menuGroupID'] ?? null),
                name: $name,
                active: $this->flag($entry['flagActive'] ?? null),
                order: $this->nullableInt($entry['orderID'] ?? null),
                minQty: $this->nullableFloat($entry['minQty'] ?? null),
                maxQty: $this->nullableFloat($entry['maxQty'] ?? null),
                notes: $this->nullableString($entry['notes'] ?? null),
                items: $this->packageItemsFrom($entry['menus'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * @return list<MasterMenuPackageItem>
     */
    private function packageItemsFrom(mixed $items): array
    {
        $mapped = [];

        foreach ($this->entries($items) as $entry) {
            $id = $this->nullableId($entry['menuID'] ?? null);
            $name = $this->nullableString($entry['menuName'] ?? null);

            if ($id === null || $name === null) {
                continue;
            }

            $mapped[] = new MasterMenuPackageItem(
                menuId: $id,
                name: $name,
                code: $this->nullableString($entry['menuCode'] ?? null),
                active: $this->flag($entry['flagActive'] ?? null),
                additionalPrice: $this->nullableFloat($entry['additionalPrice'] ?? null),
                defaultItem: $this->flag($entry['defaultItem'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * @return list<MasterMenuExtra>
     */
    private function extrasFrom(mixed $extras): array
    {
        $mapped = [];

        foreach ($this->entries($extras) as $entry) {
            $name = $this->nullableString($entry['menuExtraName'] ?? null);

            if ($name === null) {
                continue;
            }

            $mapped[] = new MasterMenuExtra(
                extraId: $this->nullableId($entry['menuExtraID'] ?? null),
                name: $name,
                active: $this->flag($entry['flagActive'] ?? null),
                minQty: $this->nullableFloat($entry['minExtraQty'] ?? null),
                maxQty: $this->nullableFloat($entry['maxExtraQty'] ?? null),
                price: $this->nullableFloat($entry['price'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * @return list<MasterMenuReference>
     */
    private function referencesFrom(mixed $references): array
    {
        $mapped = [];

        foreach ($this->entries($references) as $entry) {
            $id = $this->nullableId($entry['menuID'] ?? null);

            if ($id === null) {
                continue;
            }

            $mapped[] = new MasterMenuReference(
                menuId: $id,
                name: $this->nullableString($entry['menuName'] ?? null),
                code: $this->nullableString($entry['menuCode'] ?? null),
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
     * The vendor writes flags as "Yes"/"No" in reads and 1/0 or booleans
     * elsewhere. Anything else is unknown, not false.
     */
    private function flag(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                'yes' => true,
                'no' => false,
                default => null,
            };
        }

        return null;
    }

    /**
     * A POS button colour, only as a hex code: it ends up in a style
     * attribute, so nothing else is let through.
     */
    private function color(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value) === 1 ? $value : null;
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
