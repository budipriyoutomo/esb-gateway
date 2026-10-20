<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One memorial journal -- a manual journal entry -- with its lines
 * ([ESB-043]). People are named by full name where the ERP gives one.
 */
readonly class MemorialJournal implements JsonSerializable
{
    /**
     * @param  list<JournalLine>  $lines
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date,
        public ?string $notes,
        public ?bool $hasApproval,
        public ?int $statusId,
        public ?string $statusName,
        public array $lines = [],
        public int $attachmentCount = 0,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
        public ?string $authorizedBy = null,
        public ?string $authorizedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
