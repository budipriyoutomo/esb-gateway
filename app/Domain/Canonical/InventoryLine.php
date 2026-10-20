<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One product line of an inventory document. Quantities a document does not
 * have are null; a 0 the ERP sends is a real quantity. `qty` is the
 * document's own quantity (transferred, delivered, received, adjusted).
 */
readonly class InventoryLine implements JsonSerializable
{
    public function __construct(
        public ?int $productDetailId,
        public ?string $productCode,
        public ?string $productName,
        public ?string $unit,
        public ?float $qty,
        public ?float $requestedQty = null,
        public ?float $availableQty = null,
        public ?float $stockQty = null,
        public ?float $pendingQty = null,
        public ?float $receivedQty = null,
        public ?float $outstandingQty = null,
        public ?float $cost = null,
        public ?string $notes = null,
        public ?string $purpose = null,
        public ?int $purposeId = null,
    ) {}

    public function withOutstanding(?float $qty): self
    {
        return new self(...[...get_object_vars($this), 'outstandingQty' => $qty]);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
