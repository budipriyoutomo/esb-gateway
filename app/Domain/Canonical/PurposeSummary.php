<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A purpose as a list row: a reason a stock or cash transaction is booked,
 * with the account it posts to. The field set is the ESB Core GET /purpose
 * row's ([ESB-015]).
 */
readonly class PurposeSummary implements JsonSerializable
{
    /**
     * @param  list<string>  $appliesTo  Transaction type names, as the ESB labels them.
     */
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $name,
        public ?string $accountNo = null,
        public ?string $accountName = null,
        public array $appliesTo = [],
        public ?bool $active = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
