<?php

namespace App\Domain\Canonical;

/**
 * One category detail of a MenuCategoryDraft. $id is the ERP's detail it
 * updates; null makes a new one.
 */
readonly class MenuCategoryDraftDetail
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $code,
        public string $description,
        public int|float|null $maxOrderQty,
        public string $buttonColor,
    ) {}
}
