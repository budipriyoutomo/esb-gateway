<?php

namespace App\Domain\Canonical;

/**
 * One page of branches, searched and paged by the gateway from the ESB's
 * whole list ([ESB-051]).
 */
readonly class BranchPage
{
    /**
     * @param  list<BranchSummary>  $branches
     */
    public function __construct(
        public array $branches,
        public Pagination $pagination,
    ) {}

    /**
     * Matches the search against name and code, case-insensitively, keeping
     * the ESB's order.
     *
     * @param  list<BranchSummary>  $all
     */
    public static function fromAll(array $all, BranchQuery $query): self
    {
        $needle = $query->search === null ? '' : mb_strtolower(trim($query->search));

        if ($needle !== '') {
            $all = array_values(array_filter(
                $all,
                static fn (BranchSummary $branch): bool => str_contains(mb_strtolower($branch->name), $needle)
                    || str_contains(mb_strtolower($branch->code ?? ''), $needle),
            ));
        }

        return new self(
            branches: array_slice($all, ($query->page - 1) * $query->limit, $query->limit),
            pagination: new Pagination(page: $query->page, limit: $query->limit, count: count($all)),
        );
    }
}
