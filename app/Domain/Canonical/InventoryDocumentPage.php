<?php

namespace App\Domain\Canonical;

/**
 * One page of an inventory document list.
 */
final readonly class InventoryDocumentPage
{
    /**
     * @param  list<InventoryDocumentSummary>  $documents
     */
    public function __construct(
        public array $documents,
        public ?Pagination $pagination,
    ) {}
}
