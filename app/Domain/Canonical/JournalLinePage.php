<?php

namespace App\Domain\Canonical;

/**
 * One page of a general journal's lines ([ESB-041]).
 */
final readonly class JournalLinePage
{
    public const LIMIT = 50;

    /**
     * @param  list<JournalLine>  $lines
     */
    public function __construct(
        public array $lines,
        public ?Pagination $pagination,
    ) {}
}
