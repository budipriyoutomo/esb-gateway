<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a ProductSource for.
 *
 * Values are already validated. Null means "omit this filter", never "match
 * the empty string" -- the ESB narrows a result set to rows matching an empty
 * value, and a provider that did the same would silently return nothing.
 *
 * Extracted from the ESB's documented GET /product/list filters (ADR-019).
 * Every field here reads as ordinary product-catalogue filtering except `sort`,
 * whose accepted values are still the vendor's own column names: see SORTABLE.
 */
readonly class ProductQuery
{
    /**
     * Accepted sort values.
     *
     * TBD: these are the ESB's column names, not a canonical vocabulary. A
     * second provider will not accept them, and translating a neutral sort key
     * per provider is the obvious fix -- deferred until there is a second
     * provider to design against, rather than guessed at now.
     *
     * @var list<string>
     */
    public const SORTABLE = [
        'productName',
        'productCode',
        'categoryID',
        'subCategoryID',
        'bomName',
        'uomName',
        'categoryTypeID',
        'FlagActive',
    ];

    /**
     * The ESB rejects a limit above 100 with EC03100040. Kept as the gateway's
     * own ceiling so a caller is refused before a round trip.
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $name = null,
        public ?string $code = null,
        public ?int $categoryId = null,
        public ?int $subCategoryId = null,
        public ?int $categoryTypeId = null,
        public ?string $bomName = null,
        public ?string $sort = null,
        public ?bool $activeOnly = null,
    ) {}
}
