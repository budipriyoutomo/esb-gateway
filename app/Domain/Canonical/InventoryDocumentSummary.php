<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One row of an inventory document list ([ESB-033], ADR-026). The number
 * is null on a "Pending" goods delivery / receipt row: a reference awaiting
 * its document.
 */
readonly class InventoryDocumentSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public string $kind,
        public ?string $number,
        public ?string $date,
        public ?int $statusId,
        public ?string $statusName,
        public ?InventoryParty $from = null,
        public ?InventoryParty $to = null,
        public ?string $transactionType = null,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?string $createdBy = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
