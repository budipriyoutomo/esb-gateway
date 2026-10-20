<?php

namespace App\Domain\Canonical;

/**
 * A product sub-category to create, or to replace an existing one with
 * ([ESB-030]). Whether it is active is changed by deactivate / restore, not
 * here.
 */
final readonly class ProductSubCategoryDraft
{
    public function __construct(
        public string $name,
        public int $deadStockDays,
        public ?string $notes = null,
    ) {}
}
