<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One requested product on a purchase request.
 *
 * Quantities are floats because the vendor types them as `number`, not
 * `integer` -- a request can be for 2.5 kg. `minQty`/`maxQty` are the branch's
 * stock bounds for the product, and `suggestionQty` is the provider's own
 * suggestion; all three are informational.
 *
 * `requestProcessId` is not shown, but an update sends every line back with
 * it, so the edit form needs it.
 */
readonly class PurchaseRequestLine implements JsonSerializable
{
    public function __construct(
        public ?int $productId,
        public ?int $productDetailId,
        public string $productCode,
        public string $productName,
        public ?string $uomName,
        public float $qty,
        public ?string $notes = null,
        public ?float $stock = null,
        public ?float $suggestionQty = null,
        public ?float $minQty = null,
        public ?float $maxQty = null,
        public ?int $requestProcessId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
