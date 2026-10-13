<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * Page state for a list read, in the gateway's own terms.
 *
 * Promoted out of EsbPagination (ADR-016): the page arithmetic belongs to no
 * particular provider, while parsing the ESB's list envelope stays with the
 * ESB. A provider that paginates by cursor rather than by page will need this
 * shape widened -- see ADR-019, which records that this was extracted from one
 * real example.
 */
readonly class Pagination implements JsonSerializable
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
