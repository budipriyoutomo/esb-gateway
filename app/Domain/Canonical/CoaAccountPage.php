<?php

namespace App\Domain\Canonical;

/**
 * One page of the chart of accounts, plus the page state that produced it.
 */
readonly class CoaAccountPage
{
    /**
     * @param  list<CoaAccountSummary>  $accounts
     */
    public function __construct(
        public array $accounts,
        public Pagination $pagination,
    ) {}

    /**
     * Search, filter and page a chart the provider returned whole.
     *
     * @param  list<CoaAccountSummary>  $accounts  already in chart order
     */
    public static function fromChart(array $accounts, CoaAccountQuery $query): self
    {
        $search = trim((string) $query->search);

        $accounts = array_values(array_filter(
            $accounts,
            static fn (CoaAccountSummary $account): bool => ($search === '' || $account->matches($search))
                && ($query->level === null || $account->level === $query->level),
        ));

        return new self(
            accounts: array_slice($accounts, ($query->page - 1) * $query->limit, $query->limit),
            pagination: new Pagination(page: $query->page, limit: $query->limit, count: count($accounts)),
        );
    }
}
