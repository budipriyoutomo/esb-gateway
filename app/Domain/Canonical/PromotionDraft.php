<?php

namespace App\Domain\Canonical;

/**
 * A promotion to create ([ESB-050]), as validated by CreatePromotionRequest:
 * the common fields, and the type's own fields in $values, keyed by the
 * names in PromotionType::fields().
 */
readonly class PromotionDraft
{
    public const APPLY_TO_MENU_CATEGORY = 1;

    public const APPLY_TO_MENU_CATEGORY_DETAIL = 2;

    public const APPLY_TO_MENU = 3;

    /**
     * @param  list<string>  $branchCodes
     * @param  list<int>  $days  1 Monday .. 7 Sunday
     * @param  list<int>  $scopeIds  The menu categories, details or menus $applyDiscountTo names.
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public PromotionType $type,
        public array $branchCodes,
        public string $notes,
        public string $promotionCode,
        public array $days,
        public string $startAt,
        public string $endAt,
        public bool $allCategories,
        public ?int $applyDiscountTo = null,
        public array $scopeIds = [],
        public ?string $masterCode = null,
        public ?string $description = null,
        public ?bool $usedForLoyalty = null,
        public ?string $paymentMethodName = null,
        public array $values = [],
    ) {}
}
