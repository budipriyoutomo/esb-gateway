<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A document template as a list row: a named set of products that pre-fills
 * a purchase request, usage, or item journal. The field set is the ESB Core
 * GET /document-template row's ([ESB-016]).
 */
readonly class DocumentTemplateSummary implements JsonSerializable
{
    /**
     * @param  list<string>  $typeNames  As the ESB labels them.
     * @param  list<string>  $branchNames
     */
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $name,
        public array $typeNames = [],
        public array $branchNames = [],
        public ?bool $active = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
