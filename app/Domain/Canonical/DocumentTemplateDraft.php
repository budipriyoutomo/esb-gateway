<?php

namespace App\Domain\Canonical;

/**
 * A document template to create ([ESB-016]). Values are already validated.
 *
 * Carries only what the create screen asks for. The vendor's other optional
 * fields -- cost centers, supplier, currency, destination branch, auto
 * transfer, allow zero pricelist -- are left unset: there is no picker for
 * the ids, and the flags' meanings are undocumented (TBD).
 */
readonly class DocumentTemplateDraft
{
    /**
     * @param  list<int>  $branchIds
     * @param  list<DocumentTemplateDraftLine>  $lines
     */
    public function __construct(
        public string $name,
        public DocumentTemplateType $type,
        public bool $active,
        public array $branchIds,
        public array $lines,
    ) {}
}
