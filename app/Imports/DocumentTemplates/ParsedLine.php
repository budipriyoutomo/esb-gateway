<?php

namespace App\Imports\DocumentTemplates;

/**
 * One product row of a sheet (ADR-023). The category becomes the ESB's
 * requestTemplateDetailName (owner's decision, 2026-10-15).
 */
readonly class ParsedLine
{
    public function __construct(
        public int $rowNumber,
        public string $productCode,
        public string $productName,
        public string $unit,
        public string $category,
    ) {}
}
