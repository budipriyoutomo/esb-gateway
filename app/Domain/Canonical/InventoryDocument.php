<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One inventory document with its lines ([ESB-033], ADR-026). Fields a
 * document does not have are null. `stockUsed` comes from the stock check
 * (goods receipt, simple transfer); null when not asked or not answered.
 * `canEdit` / `canDelete` are the ERP's own answer where it gives one (goods
 * transfer requests); null leaves the choice to the ERP when it is tried.
 */
readonly class InventoryDocument implements JsonSerializable
{
    /**
     * @param  list<InventoryLine>  $lines
     * @param  list<InventoryLinkedDocument>  $linkedDocuments
     */
    public function __construct(
        public string $provider,
        public string $kind,
        public string $number,
        public ?string $date,
        public ?int $statusId,
        public ?string $statusName,
        public ?InventoryParty $from = null,
        public ?InventoryParty $to = null,
        public ?string $transactionType = null,
        public ?string $reference = null,
        public ?string $referenceDate = null,
        public ?string $notes = null,
        public ?string $costCenter = null,
        public ?string $project = null,
        public array $lines = [],
        public array $linkedDocuments = [],
        public int $attachmentCount = 0,
        public ?bool $stockUsed = null,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
        public ?string $authorizedBy = null,
        public ?string $authorizedAt = null,
        public ?bool $canEdit = null,
        public ?bool $canDelete = null,
        public InventoryCarryOver $carryOver = new InventoryCarryOver,
    ) {}

    /**
     * The lines with the outstanding quantity of each product detail.
     *
     * @param  array<int, float>  $outstanding  productDetailId => qty
     */
    public function withOutstanding(array $outstanding): self
    {
        return new self(...[...get_object_vars($this), 'lines' => array_map(
            static fn (InventoryLine $line): InventoryLine => $line->withOutstanding(
                $line->productDetailId === null ? null : ($outstanding[$line->productDetailId] ?? null),
            ),
            $this->lines,
        )]);
    }

    public function withStockUsed(?bool $used): self
    {
        return new self(...[...get_object_vars($this), 'stockUsed' => $used]);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
