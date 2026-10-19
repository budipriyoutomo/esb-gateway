<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a SimplePurchaseSource for.
 *
 * Values are already validated. Null means "omit this filter" -- the ESB
 * narrows a result set to rows matching an empty value.
 *
 * The ESB documents a single `branchID` for this list, not the
 * comma-separated `branchIDs` the purchase order and invoice lists take.
 *
 * Deliberately absent, though the ESB accepts them:
 *
 * - `statusID`: ids named by example only; the rest are in GET /common/status,
 *   which the gateway does not read yet. TBD.
 * - `sort`: the accepted columns are not documented; the ESB's own default
 *   (`-cashPurchaseNum`) applies. TBD.
 * - `supplierID`: no supplier lookup to pick one from yet.
 * - `cashPurchaseTotal`, `additionalInfo`, `otherCashPurchaseNum`: no screen
 *   needs them; `otherCashPurchaseNum` is also undocumented in meaning.
 */
readonly class SimplePurchaseQuery
{
    /**
     * The gateway's own ceiling, matching the ESB's EC03100040 limit cap.
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    /**
     * @param  int|null  $branchId  Null means every branch the credential can see.
     */
    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $number = null,
        public ?string $supplierInvoiceNumber = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $branchId = null,
        public ?string $search = null,
    ) {}
}
