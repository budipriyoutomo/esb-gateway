<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One POS promotion as the ESB OMS list gives it ([ESB-049]). The scope is
 * how many menu categories, category details and menus it applies to; none
 * of the three means all.
 */
readonly class Promotion implements JsonSerializable
{
    /**
     * @param  list<array{id: int|null, code: string|null, name: string}>  $branches
     * @param  list<string>  $selfOrderPaymentMethods
     * @param  list<string>  $paymentMethods
     * @param  array{menuCategories: int, menuCategoryDetails: int, menus: int}  $scope
     */
    public function __construct(
        public string $provider,
        public int $id,
        public ?string $code,
        public ?string $typeName,
        public ?string $notes,
        public ?float $discount,
        public ?float $minSubtotal,
        public ?string $startAt,
        public ?string $endAt,
        public ?bool $shownOnEso,
        public array $branches = [],
        public array $selfOrderPaymentMethods = [],
        public array $paymentMethods = [],
        public array $scope = ['menuCategories' => 0, 'menuCategoryDetails' => 0, 'menus' => 0],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
