<?php

namespace App\Domain\Canonical;

/**
 * A purchase request as the gateway sends it to a provider: the whole
 * document, for a create or for an update that replaces it.
 *
 * Dates are YYYY-MM-DD. The vendor types them as plain strings and documents
 * no format for this operation; YYYY-MM-DD is what its list filters take, so it
 * is assumed here too (TBD, docs/05-api-integration.md).
 *
 * costCenterId, projectId and isTemplate have no picker in the gateway. They
 * exist so an update carries the document's current values back: the update
 * replaces the document, and leaving them out would detach them.
 */
readonly class PurchaseRequestDraft
{
    /**
     * @param  list<PurchaseRequestDraftLine>  $lines
     */
    public function __construct(
        public int $branchId,
        public string $date,
        public string $requiredDate,
        public array $lines,
        public ?int $templateId = null,
        public ?string $notes = null,
        public ?int $costCenterId = null,
        public ?int $projectId = null,
        public bool $isTemplate = false,
    ) {}
}
