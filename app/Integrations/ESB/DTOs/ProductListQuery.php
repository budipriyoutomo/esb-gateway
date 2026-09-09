<?php

namespace App\Integrations\ESB\DTOs;

/**
 * The filters GET /product/list accepts, as documented by the vendor.
 *
 * Values here have already been validated. Anything null is omitted from the
 * request: sending an empty filter would make the ESB narrow the result set to
 * rows matching an empty string.
 */
readonly class ProductListQuery
{
    /**
     * Sort columns the ESB accepts. Anything else answers EC03100032
     * ("Unknown Error"), which tells the user nothing -- so we reject it first.
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
     * The ESB rejects a limit above 100 with EC03100040.
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $productName = null,
        public ?string $productCode = null,
        public ?int $categoryId = null,
        public ?int $subCategoryId = null,
        public ?int $categoryTypeId = null,
        public ?string $bomName = null,
        public ?string $sort = null,
        public ?int $flagActive = null,
    ) {}

    /**
     * Query parameters for the ESB request, with unset filters omitted.
     *
     * @return array<string, int|string>
     */
    public function toQueryParameters(): array
    {
        return array_filter([
            'page' => $this->page,
            'limit' => $this->limit,
            'productName' => $this->productName,
            'productCode' => $this->productCode,
            'categoryID' => $this->categoryId,
            'subCategoryID' => $this->subCategoryId,
            'categoryTypeID' => $this->categoryTypeId,
            'bomName' => $this->bomName,
            'sort' => $this->sort,
            'flagActive' => $this->flagActive,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
