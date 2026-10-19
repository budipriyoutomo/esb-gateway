<?php

namespace App\Domain\Canonical;

/**
 * One page of unit summaries, plus the page state that produced it.
 */
readonly class UnitOfMeasurePage
{
    /**
     * @param  list<UnitOfMeasureSummary>  $units
     */
    public function __construct(
        public array $units,
        public ?Pagination $pagination = null,
    ) {}
}
