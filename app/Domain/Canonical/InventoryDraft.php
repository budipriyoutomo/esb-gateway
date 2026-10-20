<?php

namespace App\Domain\Canonical;

/**
 * An inventory document as the gateway's form fills it, to create or replace
 * one ([ESB-035]). Which sides a kind uses:
 *
 * - goods transfer request: branch (+ optional location) -> destination branch;
 * - item journal: branch and location;
 * - simple transfer: location -> destination location (the branch only narrows
 *   the form's pickers and is not sent).
 *
 * What the form does not show is carried over from the document on an update
 * (InventoryCarryOver).
 */
readonly class InventoryDraft
{
    /**
     * @param  list<InventoryDraftLine>  $lines
     */
    public function __construct(
        public string $date,
        public ?int $branchId,
        public ?int $locationId,
        public ?int $destinationBranchId,
        public ?int $destinationLocationId,
        public ?string $notes,
        public array $lines,
    ) {}
}
