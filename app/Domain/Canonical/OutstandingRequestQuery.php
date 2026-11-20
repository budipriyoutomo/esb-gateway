<?php

namespace App\Domain\Canonical;

/**
 * The PR outstanding report's filters (ADR-034), already validated.
 *
 * The required-date period and the branch / request number reach the ERP;
 * the stage, the overdue filter and the page are the gateway's.
 */
readonly class OutstandingRequestQuery
{
    /** The longest required-date period one report reads. */
    public const MAX_DAYS = 92;

    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 50;

    public function __construct(
        public string $requiredDateFrom,
        public string $requiredDateTo,
        public ?int $branchId = null,
        public ?string $purchaseRequestNumber = null,
        public ?OutstandingStage $stage = null,
        public bool $overdueOnly = false,
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
    ) {}

    /** The same query at another page and page size (the CSV export). */
    public function at(int $page, int $limit): self
    {
        return new self(
            requiredDateFrom: $this->requiredDateFrom,
            requiredDateTo: $this->requiredDateTo,
            branchId: $this->branchId,
            purchaseRequestNumber: $this->purchaseRequestNumber,
            stage: $this->stage,
            overdueOnly: $this->overdueOnly,
            page: $page,
            limit: $limit,
        );
    }
}
