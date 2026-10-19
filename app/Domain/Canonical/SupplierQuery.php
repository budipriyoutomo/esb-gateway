<?php

namespace App\Domain\Canonical;

/**
 * Filters for a SupplierSource list. Values are already validated; null
 * means "omit this filter".
 */
readonly class SupplierQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        /** Free text across supplier fields (the ESB's indexSearch). */
        public ?string $search = null,
        public ?bool $activeOnly = null,
    ) {}
}
