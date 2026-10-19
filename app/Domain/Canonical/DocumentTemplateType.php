<?php

namespace App\Domain\Canonical;

/**
 * The kinds of document a template pre-fills ([ESB-016]). The ids and their
 * meanings are the vendor's, from the requestTemplateTypeID filter of
 * GET /document-template. An id outside this list is unknown, not an error.
 */
enum DocumentTemplateType: int
{
    case PurchaseRequest = 1;
    case UsageManagement = 2;
    case ItemJournal = 3;

    public function label(): string
    {
        return match ($this) {
            self::PurchaseRequest => 'Purchase Request',
            self::UsageManagement => 'Usage Management',
            self::ItemJournal => 'Item Journal',
        };
    }

    /**
     * The type whose label this is, ignoring case and surrounding space, or
     * null for any other text.
     */
    public static function fromLabel(string $label): ?self
    {
        $wanted = strtolower(trim($label));

        foreach (self::cases() as $type) {
            if (strtolower($type->label()) === $wanted) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
