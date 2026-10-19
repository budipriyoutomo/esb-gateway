<?php

namespace App\Imports\DocumentTemplates;

/**
 * A document template sheet, read (ADR-023).
 */
readonly class ParsedSheet
{
    /**
     * @param  list<ParsedTemplate>  $templates  In the order they first appear.
     * @param  list<string>  $problems  Rows that belong to no template.
     */
    public function __construct(
        public array $templates,
        public array $problems = [],
    ) {}

    public function lineCount(): int
    {
        return array_sum(array_map(static fn (ParsedTemplate $template): int => count($template->lines), $this->templates));
    }
}
