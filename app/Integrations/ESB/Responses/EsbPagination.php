<?php

namespace App\Integrations\ESB\Responses;

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
