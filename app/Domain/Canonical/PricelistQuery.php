<?php

namespace App\Domain\Canonical;

/**
 * Filters for the active pricelist. Null means "omit this filter".
 */
readonly class PricelistQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        /** The ESB's indexSearch. */
        public ?string $search = null,
    ) {}
}
