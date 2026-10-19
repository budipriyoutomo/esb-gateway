<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One page of picker options.
 */
readonly class LookupPage implements JsonSerializable
{
    /**
     * @param  list<LookupOption>  $options
     */
    public function __construct(
        public array $options,
        public bool $hasMore,
    ) {}

    /**
     * Search and page a list the provider returned whole, so a picker never
     * receives thousands of rows.
     *
     * @param  list<LookupOption>  $options
     */
    public static function fromAll(array $options, ?string $search, int $page, int $limit): self
    {
        $search = trim((string) $search);

        if ($search !== '') {
            $options = array_values(array_filter(
                $options,
                static fn (LookupOption $option): bool => $option->matches($search),
            ));
        }

        $offset = max(0, ($page - 1) * $limit);

        return new self(
            options: array_slice($options, $offset, $limit),
            hasMore: count($options) > $offset + $limit,
        );
    }

    /**
     * @return array{options: list<LookupOption>, hasMore: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'options' => $this->options,
            'hasMore' => $this->hasMore,
        ];
    }
}
