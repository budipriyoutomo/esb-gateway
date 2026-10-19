<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One choice in a picker: what is submitted (`value`), what is shown
 * (`label`, `code`), and the few facts a form may need about it (`meta`,
 * e.g. the branch of a location or the unit of a product).
 */
readonly class LookupOption implements JsonSerializable
{
    /**
     * @param  array<string, int|float|string|null>  $meta
     */
    public function __construct(
        public string $value,
        public string $label,
        public ?string $code = null,
        public array $meta = [],
    ) {}

    /**
     * Case-insensitive match on the label or the code.
     */
    public function matches(string $search): bool
    {
        $needle = mb_strtolower($search);

        return str_contains(mb_strtolower($this->label), $needle)
            || ($this->code !== null && str_contains(mb_strtolower($this->code), $needle));
    }

    /**
     * @return array{value: string, label: string, code: string|null, meta: array<string, int|float|string|null>}
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'code' => $this->code,
            'meta' => $this->meta,
        ];
    }
}
