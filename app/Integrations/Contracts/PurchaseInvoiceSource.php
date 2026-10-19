<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\PurchaseInvoice;
use App\Domain\Canonical\PurchaseInvoicePage;
use App\Domain\Canonical\PurchaseInvoiceQuery;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;

/**
 * A provider that can be asked for purchase invoices.
 *
 * Reads, the approval decision, and delete. Create and update are not here:
 * their body carries supplier, currency, rate, goods-receipt references and
 * tax figures, and the lookups that feed them are not built yet.
 *
 * Every write is non-idempotent: implementations make exactly one attempt. On
 * failure, EsbException::mayHaveBeenApplied() says whether the caller may
 * report that nothing happened, or must treat the outcome as unknown.
 *
 * App\Integrations\ESB\Clients\PurchaseInvoiceClient is the reference
 * implementation; callers depend on this contract (ADR-016, ADR-019).
 */
interface PurchaseInvoiceSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     *
     * TBD (ADR-019): the thrown type is still the ESB's, as on ProductSource.
     */
    public function listPurchaseInvoices(PurchaseInvoiceQuery $query): PurchaseInvoicePage;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function findPurchaseInvoice(string $number): PurchaseInvoice;

    /**
     * Authorize or reject a purchase invoice.
     *
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbException
     */
    public function decidePurchaseInvoice(string $number, ApprovalDecision $decision): void;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function deletePurchaseInvoice(string $number): void;
}
