<?php

namespace App\Domain\Canonical;

/**
 * One page of promotions, paged by the gateway from the ESB's whole list
 * ([ESB-049]).
 */
readonly class PromotionPage
{
    /**
     * @param  list<Promotion>  $promotions
     */
    public function __construct(
        public array $promotions,
        public Pagination $pagination,
    ) {}

    /**
     * @param  list<Promotion>  $all
     */
    public static function fromAll(array $all, int $page, int $limit = PromotionQuery::LIMIT): self
    {
        return new self(
            promotions: array_slice($all, ($page - 1) * $limit, $limit),
            pagination: new Pagination(page: $page, limit: $limit, count: count($all)),
        );
    }
}
