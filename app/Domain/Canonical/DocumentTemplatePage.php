<?php

namespace App\Domain\Canonical;

/**
 * One page of document template summaries, plus the page state that
 * produced it.
 */
readonly class DocumentTemplatePage
{
    /**
     * @param  list<DocumentTemplateSummary>  $templates
     */
    public function __construct(
        public array $templates,
        public ?Pagination $pagination = null,
    ) {}
}
