<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A bill of material with its material lines, other costs, and per-menu
 * replacements. The field set is the ESB Core GET /product/bom/{bomID}
 * result's, read-only ([ESB-014]).
 *
 * Costs are the ERP's figures, carried for display only; the gateway does not
 * recompute them.
 */
readonly class BillOfMaterial implements JsonSerializable
{
    /**
     * @param  list<BillOfMaterialLine>  $lines
     * @param  list<BillOfMaterialCost>  $costs
     * @param  list<BillOfMaterialReplacement>  $replacements
     */
    public function __construct(
        public string $provider,
        public int $id,
        public ?string $code,
        public string $name,
        public ?int $typeId = null,
        public ?string $typeName = null,
        public ?string $accessType = null,
        public ?int $productId = null,
        public ?string $productCode = null,
        public ?string $productName = null,
        public ?string $uomName = null,
        public ?string $notes = null,
        public ?bool $active = null,
        public ?float $materialCostTotal = null,
        public ?float $otherCostTotal = null,
        public ?float $weightFactorTotal = null,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
        public array $lines = [],
        public array $costs = [],
        public array $replacements = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
