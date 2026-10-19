<?php

namespace App\Imports\DocumentTemplates;

use App\Domain\Canonical\DocumentTemplateType;

/**
 * One template of a sheet: the rows sharing a name (ADR-023).
 */
readonly class ParsedTemplate
{
    /**
     * @param  list<string>  $branchCodes  Upper-cased, every row's merged.
     * @param  list<ParsedLine>  $lines
     * @param  list<string>  $problems  Why the ERP would refuse it as written.
     */
    public function __construct(
        public string $name,
        public ?DocumentTemplateType $type,
        public array $branchCodes,
        public array $lines,
        public array $problems = [],
    ) {}
}
