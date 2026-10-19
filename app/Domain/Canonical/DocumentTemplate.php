<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One document template, as the ESB Core GET
 * /document-template/{requestTemplateID} result returns it ([ESB-016]).
 *
 * Branches and cost centers come as ids only; their names need further
 * reads (/template-branch, /cost-center) that are not built. Supplier,
 * currency, and destination branch ids are not carried for the same reason.
 */
readonly class DocumentTemplate implements JsonSerializable
{
    /**
     * @param  list<int>  $branchIds
     * @param  list<int>  $costCenterIds
     * @param  list<DocumentTemplateLine>  $lines
     */
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?DocumentTemplateType $type = null,
        public ?bool $active = null,
        public ?bool $autoTransfer = null,
        public ?bool $allowZeroPricelist = null,
        public array $branchIds = [],
        public array $costCenterIds = [],
        public array $lines = [],
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
        public ?string $syncedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ...get_object_vars($this),
            'typeName' => $this->type?->label(),
        ];
    }
}
