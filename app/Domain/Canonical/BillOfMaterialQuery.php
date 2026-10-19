<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a BillOfMaterialSource for. Values are already
 * validated; null means "omit this filter".
 *
 * A subset of the ESB's documented GET /product/bom filters ([ESB-014]).
 * `bomTypeID` needs a BOM type lookup and `sort` has no documented list of
 * accepted columns, so neither is offered yet; the ESB default sort (bomName)
 * applies.
 */
readonly class BillOfMaterialQuery
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
        public ?string $code = null,
        public ?string $productName = null,
        public ?bool $activeOnly = null,
    ) {}
}
