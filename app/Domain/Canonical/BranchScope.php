<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * Which branches a record applies to: every branch, or the named ones
 * (the ESB's applicableBranch, [ESB-027]). `all` is null when the provider
 * sent a scope the gateway does not know.
 */
readonly class BranchScope implements JsonSerializable
{
    /**
     * @param  list<string>  $names
     * @param  list<int>  $ids  The named branches' ids, when the provider sends them.
     */
    public function __construct(
        public ?bool $all,
        public array $names = [],
        public array $ids = [],
    ) {}

    /**
     * @return array{all: bool|null, names: list<string>, ids: list<int>}
     */
    public function jsonSerialize(): array
    {
        return ['all' => $this->all, 'names' => $this->names, 'ids' => $this->ids];
    }
}
