<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A person to contact at a supplier (the ESB's supplierPic, [ESB-024]).
 */
readonly class SupplierContact implements JsonSerializable
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $cellPhone = null,
        public ?string $email = null,
        public ?bool $isDefault = null,
        public ?bool $sendsPurchaseOrderEmail = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
