<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One row of the memorial journal list ([ESB-043]). `hasApproval` is the
 * ERP's approval flag; null when it does not say.
 */
readonly class MemorialJournalSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date,
        public ?string $notes,
        public ?bool $hasApproval,
        public ?string $createdBy,
        public ?int $statusId,
        public ?string $statusName,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
