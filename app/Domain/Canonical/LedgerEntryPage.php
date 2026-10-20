<?php

namespace App\Domain\Canonical;

/**
 * One page of an account's ledger. The provider answers the whole period at
 * once ([ESB-042]); the gateway pages it, in the provider's order.
 */
final readonly class LedgerEntryPage
{
    /**
     * @param  list<LedgerEntry>  $entries
     */
    public function __construct(
        public array $entries,
        public Pagination $pagination,
    ) {}

    /**
     * @param  list<LedgerEntry>  $entries  the whole period
     */
    public static function fromLedger(array $entries, LedgerQuery $query): self
    {
        return new self(
            entries: array_slice($entries, ($query->page - 1) * $query->limit, $query->limit),
            pagination: new Pagination(page: $query->page, limit: $query->limit, count: count($entries)),
        );
    }
}
