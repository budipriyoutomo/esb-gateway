<?php

namespace App\Integrations\ESB\Responses;

use App\Domain\Canonical\Pagination;
use JsonSerializable;

/**
 * Pagination state from an ESB list response.
 *
 * The ESB also returns absolute `prev` and `next` URLs containing its own host.
 * They are deliberately not carried here: handing them to the browser would
 * leak the ESB host and invite direct browser-to-ESB calls, which ADR-006
 * forbids. Re-derive pagination from page and limit on our side.
 */
readonly class EsbPagination implements JsonSerializable
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $count,
    ) {}

    /**
     * The same page state, in the gateway's own terms (ADR-016).
     *
     * The page arithmetic now lives on Pagination; this class stays the shape
     * the ESB's list envelope is parsed into.
     */
    public function toCanonical(): Pagination
    {
        return new Pagination(
            page: $this->page,
            limit: $this->limit,
            count: $this->count,
        );
    }

    public function lastPage(): int
    {
        if ($this->limit < 1) {
            return 1;
        }

        return max(1, (int) ceil($this->count / $this->limit));
    }

    public function hasMorePages(): bool
    {
        return $this->page < $this->lastPage();
    }

    /**
     * @return array<string, int|bool>
     */
    public function jsonSerialize(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'count' => $this->count,
            'lastPage' => $this->lastPage(),
            'hasMorePages' => $this->hasMorePages(),
        ];
    }
}
