<?php

namespace App\Domain\Canonical;

/**
 * Filters for the disbursement list ([ESB-037]). Null is "no filter", and so
 * is overdueOnly false: the vendor's isOverDue only narrows.
 */
final readonly class DisbursementQuery
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    /**
     * The statuses the vendor documents for disbursements, in workflow order.
     */
    public const STATUSES = [
        1 => 'New',
        38 => 'Waiting For Approval',
        3 => 'Authorized',
        2 => 'Rejected',
    ];

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $number = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $statusId = null,
        public ?int $branchId = null,
        public ?int $supplierId = null,
        public ?DisbursementPaymentMethod $payment = null,
        public ?string $reference = null,
        public bool $overdueOnly = false,
    ) {}
}
