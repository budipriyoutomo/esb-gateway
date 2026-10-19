<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a PurchaseOrderSource for.
 *
 * Values are already validated. Null means "omit this filter" -- the ESB
 * narrows a result set to rows matching an empty value.
 *
 * Deliberately absent, though the ESB accepts them:
 *
 * - `statusID`: the vendor names only three ids by example and points to
 *   GET /common/status for the rest, which the gateway does not read yet. TBD.
 * - `sort`: the accepted columns are not documented. TBD.
 * - `supplierID(s)`: the gateway has no supplier lookup to pick one from yet.
 * - `branchID`: `branchIDs` covers it.
 * - `purchaseRequestNums`, `purchaseTotal`, `printedBy`, `sendEmail`,
 *   `flagPOSO`, `additionalInfo`: no screen needs them.
 */
readonly class PurchaseOrderQuery
{
    /**
     * The gateway's own ceiling, matching the ESB's EC03100040 limit cap.
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    /**
     * @param  list<int>  $branchIds  Empty means every branch the credential can see.
     */
    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $number = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $requiredDateFrom = null,
        public ?string $requiredDateTo = null,
        public array $branchIds = [],
        public ?string $search = null,
    ) {}
}
