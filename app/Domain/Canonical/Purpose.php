<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One purpose, as the ESB Core GET /purpose/{purposeID} result returns it
 * ([ESB-015]).
 *
 * The detail carries transaction type ids, not names. The names come from
 * GET /purpose/transaction-type, whose row shape the vendor does not document,
 * so they are not resolved here (TBD).
 */
readonly class Purpose implements JsonSerializable
{
    /**
     * @param  list<int>  $transactionTypeIds
     */
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?string $accountNo = null,
        public ?bool $active = null,
        public array $transactionTypeIds = [],
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
