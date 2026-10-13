<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a PurchaseInvoiceSource for.
 *
 * Values are already validated. Null means "omit this filter" -- the ESB
 * narrows a result set to rows matching an empty value.
 *
 * Deliberately absent, though the ESB accepts them:
 *
 * - `sort`: the accepted columns are not documented. TBD.
 * - `statusID`: the id-to-status mapping is not documented. TBD.
 * - `supplierID(s)`, `currencyID`: the gateway has no lookup to pick one from
 *   yet.
 * - `rate`, `invoiceTotal`, `createdBy`, `additionalInfo`: no screen needs them.
 */
readonly class PurchaseInvoiceQuery
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
        public ?string $supplierInvoiceNumber = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public array $branchIds = [],
        public ?string $search = null,
    ) {}
}
