<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A menu's price on one menu template (a price list, e.g. dine-in).
 */
readonly class MasterMenuTemplatePrice implements JsonSerializable
{
    public function __construct(
        public int $templateId,
        public ?string $templateName = null,
        public ?bool $active = null,
        public ?float $price = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
