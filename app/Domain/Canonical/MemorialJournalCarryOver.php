<?php

namespace App\Domain\Canonical;

/**
 * What an edit of a memorial journal must send back unchanged because the
 * gateway's form does not show it ([ESB-045]): each line's cost center and
 * project, by the line's ERP id. Never serialized: it stays on the server,
 * so the browser cannot choose them.
 */
readonly class MemorialJournalCarryOver
{
    /**
     * @param  array<int, array{costCenterId: ?int, projectId: ?int}>  $lines  By line id.
     */
    public function __construct(
        public array $lines = [],
    ) {}

    /**
     * The line's cost center and project, or none for a line the journal
     * does not have (a new one).
     *
     * @return array{costCenterId: ?int, projectId: ?int}
     */
    public function forLine(?int $id): array
    {
        return ($id === null ? null : $this->lines[$id] ?? null) ?? ['costCenterId' => null, 'projectId' => null];
    }
}
