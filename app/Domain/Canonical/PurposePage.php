<?php

namespace App\Domain\Canonical;

/**
 * One page of purpose summaries, plus the page state that produced it.
 */
readonly class PurposePage
{
    /**
     * @param  list<PurposeSummary>  $purposes
     */
    public function __construct(
        public array $purposes,
        public ?Pagination $pagination = null,
    ) {}
}
