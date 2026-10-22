<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One POS menu category with its category details ([ESB-047]). The
 * accounts are the ERP's account descriptions, as the vendor sends them.
 */
readonly class MenuCategory implements JsonSerializable
{
    /**
     * @param  list<MenuCategoryDetail>  $details
     */
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?string $code,
        public ?string $salesAccount,
        public ?string $cogsAccount,
        public ?string $discountAccount,
        public ?string $description,
        public ?string $notes,
        public ?string $statusName,
        public ?string $buttonColor,
        public array $details = [],
    ) {}

    /**
     * @return list<int>
     */
    public function detailIds(): array
    {
        return array_map(static fn (MenuCategoryDetail $detail): int => $detail->id, $this->details);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
