<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One general journal entry -- the journal a module posted for a document
 * ([ESB-041]). `viewableInErp` is the ERP's typeHasAccessView; null when it
 * does not say.
 */
readonly class GeneralJournalEntry implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public int $journalHeadId,
        public ?string $date,
        public ?string $transactionType,
        public ?string $reference,
        public ?string $notes,
        public ?bool $viewableInErp,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
