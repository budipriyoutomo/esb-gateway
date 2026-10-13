<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A single purchase request with its lines, as the gateway understands it.
 *
 * Transcribed from the ESB's GET /purchase/purchase-request/{purchaseRequestNum}
 * result (ADR-019: one real example, expect revision). The same caveats as
 * PurchaseRequestSummary apply to dates and status.
 *
 * Two parts of the vendor's result are not carried: `print` (the ESB's own
 * document layout and company logo URL) and `imageAttachments` (absolute URLs
 * on the vendor's storage). Serving attachments means deciding how the gateway
 * proxies them -- the browser does not talk to the ESB's hosts (ADR-006) -- and
 * that is deferred until it is needed.
 */
readonly class PurchaseRequest implements JsonSerializable
{
    /**
     * @param  list<PurchaseRequestLine>  $lines
     * @param  list<string>  $purchaseNumbers  Document numbers the provider
     *                                         lists under `purchaseNums`.
     * @param  list<string>  $transferNumbers  Document numbers the provider
     *                                         lists under `transferNums`.
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date = null,
        public ?string $requiredDate = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $costCenterId = null,
        public ?string $costCenterName = null,
        public ?int $projectId = null,
        public ?string $projectName = null,
        public ?int $templateId = null,
        public bool $isTemplate = false,
        public ?int $statusId = null,
        public ?string $statusName = null,
        public ?string $notes = null,
        public ?string $createdByName = null,
        public ?string $createdAt = null,
        public ?string $editedByName = null,
        public ?string $editedAt = null,
        public ?string $authorizedByName = null,
        public ?string $authorizedAt = null,
        public array $lines = [],
        public array $purchaseNumbers = [],
        public array $transferNumbers = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
