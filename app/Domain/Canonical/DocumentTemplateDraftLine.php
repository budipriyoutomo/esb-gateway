<?php

namespace App\Domain\Canonical;

/**
 * One product of a document template to create ([ESB-016]).
 *
 * The vendor's optional `mode` ("+"/"-") and `requestProcessID` (0-3) are
 * not carried: their meanings are undocumented (TBD).
 */
readonly class DocumentTemplateDraftLine
{
    public function __construct(
        public int $productDetailId,
        public string $name,
        public ?int $purposeId = null,
    ) {}
}
