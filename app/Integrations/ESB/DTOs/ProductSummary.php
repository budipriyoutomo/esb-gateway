<?php

namespace App\Integrations\ESB\DTOs;

use JsonSerializable;

/**
 * A row from the ESB product list, in our shape rather than the vendor's.
 *
 * Fields are transcribed from the vendor documentation for GET /product/list
 * only. Other operations get their own DTOs when they are implemented -- the
 * catalogs in docs/esb/ index every operation, but nothing is transcribed until
 * it is actually used.
 */
readonly class ProductSummary implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public ?int $categoryId,
        public ?string $categoryName,
        public ?int $subCategoryId,
        public ?string $subCategoryName,
        public ?int $bomId,
        public ?string $bomName,
        public ?int $categoryTypeId,
        public ?string $categoryTypeName,
        public bool $isActive,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'categoryId' => $this->categoryId,
            'categoryName' => $this->categoryName,
            'subCategoryId' => $this->subCategoryId,
            'subCategoryName' => $this->subCategoryName,
            'bomId' => $this->bomId,
            'bomName' => $this->bomName,
            'categoryTypeId' => $this->categoryTypeId,
            'categoryTypeName' => $this->categoryTypeName,
            'isActive' => $this->isActive,
        ];
    }
}
