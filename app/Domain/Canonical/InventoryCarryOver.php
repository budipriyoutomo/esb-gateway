<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * What an edit of an inventory document must send back unchanged because
 * the gateway's form does not show it ([ESB-035]): the purchase requests a
 * transfer request was raised from, a simple transfer's cost center and
 * project, an item journal's request template. `hasAssets` marks a document
 * that moves assets -- the gateway does not edit those, so an update cannot
 * drop them.
 */
readonly class InventoryCarryOver implements JsonSerializable
{
    /**
     * @param  list<string>  $purchaseRequestNumbers
     */
    public function __construct(
        public array $purchaseRequestNumbers = [],
        public ?int $costCenterId = null,
        public ?int $projectId = null,
        public ?int $requestTemplateId = null,
        public bool $hasAssets = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
