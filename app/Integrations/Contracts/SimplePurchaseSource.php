<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\SimplePurchase;
use App\Domain\Canonical\SimplePurchasePage;
use App\Domain\Canonical\SimplePurchaseQuery;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;

/**
 * A provider that can be asked for simple purchases: a purchase recorded in
 * one step, without a purchase order or goods receipt in front of it (the
 * ESB calls it a cash purchase, `cashPurchaseNum`).
 *
 * Reads, the approval decision, and delete. Create, update, validate and the
 * uploads are not here yet (TODO "Module — Simple Purchase").
 *
 * Every write is non-idempotent: implementations make exactly one attempt. On
 * failure, EsbException::mayHaveBeenApplied() says whether the caller may
 * report that nothing happened, or must treat the outcome as unknown.
 *
 * App\Integrations\ESB\Clients\SimplePurchaseClient is the reference
 * implementation; callers depend on this contract (ADR-016, ADR-019).
 */
interface SimplePurchaseSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     *
     * TBD (ADR-019): the thrown type is still the ESB's, as on ProductSource.
     */
    public function listSimplePurchases(SimplePurchaseQuery $query): SimplePurchasePage;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function findSimplePurchase(string $number): SimplePurchase;

    /**
     * Authorize or reject a simple purchase.
     *
     * @param  string  $number  A document number already validated by the caller.
     * @param  string|null  $reason  Required for a rejection, ignored for an
     *                               authorization.
     *
     * @throws EsbException
     */
    public function decideSimplePurchase(string $number, ApprovalDecision $decision, ?string $reason = null): void;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function deleteSimplePurchase(string $number): void;
}
