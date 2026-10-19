<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\PurchaseOrder;
use App\Domain\Canonical\PurchaseOrderPage;
use App\Domain\Canonical\PurchaseOrderQuery;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;

/**
 * A provider that can be asked for purchase orders.
 *
 * Reads, the approval decision, and delete. Create, update, close/unclose,
 * print, email and the uploads are not here yet (TODO "Module — Purchase
 * Order").
 *
 * Every write is non-idempotent: implementations make exactly one attempt. On
 * failure, EsbException::mayHaveBeenApplied() says whether the caller may
 * report that nothing happened, or must treat the outcome as unknown.
 *
 * App\Integrations\ESB\Clients\PurchaseOrderClient is the reference
 * implementation; callers depend on this contract (ADR-016, ADR-019).
 */
interface PurchaseOrderSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     *
     * TBD (ADR-019): the thrown type is still the ESB's, as on ProductSource.
     */
    public function listPurchaseOrders(PurchaseOrderQuery $query): PurchaseOrderPage;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function findPurchaseOrder(string $number): PurchaseOrder;

    /**
     * Authorize or reject a purchase order.
     *
     * @param  string  $number  A document number already validated by the caller.
     * @param  string|null  $reason  Required for a rejection, ignored for an
     *                               authorization.
     *
     * @throws EsbException
     */
    public function decidePurchaseOrder(string $number, ApprovalDecision $decision, ?string $reason = null): void;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function deletePurchaseOrder(string $number): void;
}
