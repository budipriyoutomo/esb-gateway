<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One purchase request as it appears in a list, independent of who supplied it.
 *
 * Transcribed from the ESB's GET /purchase/purchase-request row, the only
 * example that exists (ADR-019), so expect revision when a second provider
 * lands. Two things are deliberately left as the provider sent them, because
 * the vendor documents neither:
 *
 * - Dates are strings. The vendor's examples are empty placeholders, so the
 *   format is TBD and parsing it here would be a guess.
 * - Status is the provider's own id and label. The meaning of each statusID is
 *   TBD; nothing in the gateway branches on it.
 */
readonly class PurchaseRequestSummary implements JsonSerializable
{
    /**
     * @param  string  $number  The document number, e.g. PR202603260005. It is
     *                          the provider's key for the document, and what
     *                          the detail read is addressed by.
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date = null,
        public ?string $requiredDate = null,
        public ?string $createdAt = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $templateId = null,
        public ?string $templateName = null,
        public ?int $statusId = null,
        public ?string $statusName = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
