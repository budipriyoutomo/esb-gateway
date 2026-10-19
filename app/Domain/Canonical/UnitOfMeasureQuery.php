<?php

namespace App\Domain\Canonical;

/**
 * Filters for a UnitOfMeasureSource list. Values are already validated; null
 * means "omit this filter".
 */
readonly class UnitOfMeasureQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        /** Free text on unit or metric name (the ESB's indexSearch). */
        public ?string $search = null,
        public ?bool $activeOnly = null,
    ) {}
}
