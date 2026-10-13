<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product a request template offers for one branch, with the two ids a
 * purchase request line must carry.
 *
 * Transcribed from the ESB's GET /purchase/purchase-request/template-details
 * result. The stock figures are the branch's own and are informational, as on
 * PurchaseRequestLine.
 */
readonly class PurchaseRequestTemplateLine implements JsonSerializable
{
    /**
     * @param  string|null  $group  The template's own grouping of its lines
     *                              (`requestTemplateDetailName`), e.g. "Drinks".
     */
    public function __construct(
        public int $productDetailId,
        public int $requestProcessId,
        public ?string $requestProcessName,
        public ?int $productId,
        public string $productCode,
        public string $productName,
        public ?string $uomName,
        public ?string $group = null,
        public ?float $stock = null,
        public ?float $suggestionQty = null,
        public ?float $minQty = null,
        public ?float $maxQty = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
