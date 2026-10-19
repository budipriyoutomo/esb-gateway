<?php

namespace App\Domain\Canonical;

/**
 * Filters for a ProductCategorySource list. Values are already validated;
 * null means "omit this filter".
 */
readonly class ProductCategoryQuery
{
    /**
     * The vendor's category type legend ([ESB-021]).
     */
    public const TYPES = [
        1 => 'Inventory',
        2 => 'Non Inventory',
        3 => 'Asset',
        4 => 'Non Depreciated Asset',
    ];

    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        /** Free text on category or category type name (the ESB's indexSearch). */
        public ?string $search = null,
        public ?int $typeId = null,
        public ?bool $activeOnly = null,
    ) {}
}
