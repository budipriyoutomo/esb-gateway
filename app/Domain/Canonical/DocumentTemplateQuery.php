<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a DocumentTemplateSource for. Values are
 * already validated; null means "omit this filter".
 *
 * The ESB's documented GET /document-template filters ([ESB-016]), except
 * branchID: there is no branch picker yet.
 */
readonly class DocumentTemplateQuery
{
    /**
     * The gateway's own ceiling, matching the other Core list reads: the ESB
     * rejects a limit above 100 elsewhere with EC03100040.
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $name = null,
        public ?DocumentTemplateType $type = null,
        public ?bool $activeOnly = null,
    ) {}
}
