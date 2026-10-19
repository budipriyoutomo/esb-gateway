<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One account in the chart of accounts, as listed (ESB Core
 * GET /accounting/coa, [ESB-025]).
 */
readonly class CoaAccountSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public string $coaNo,
        public string $description,
        /** 1 (top) to 4 (posting account). */
        public ?int $level = null,
        public ?int $order = null,
        /** Null when the account is not tied to one branch. */
        public ?int $branchId = null,
        public ?string $currency = null,
        public ?bool $moduleAccount = null,
        public ?bool $allowBudget = null,
        public ?bool $active = null,
    ) {}

    /**
     * Prefix match on the number, or a case-insensitive match in the
     * description.
     */
    public function matches(string $search): bool
    {
        return str_starts_with($this->coaNo, $search)
            || str_contains(mb_strtolower($this->description), mb_strtolower($search));
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
