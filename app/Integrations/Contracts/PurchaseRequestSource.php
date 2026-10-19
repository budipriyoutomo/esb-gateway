<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\Branch;
use App\Domain\Canonical\PurchaseRequest;
use App\Domain\Canonical\PurchaseRequestDraft;
use App\Domain\Canonical\PurchaseRequestPage;
use App\Domain\Canonical\PurchaseRequestQuery;
use App\Domain\Canonical\PurchaseRequestTemplateLine;
use App\Domain\Canonical\RequestTemplate;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;

/**
 * A provider that can be asked for purchase requests.
 *
 * Reads, the lookups a purchase request form needs, and the writes: create,
 * update, delete, file import, and the approval decision.
 *
 * Every write is non-idempotent: implementations make exactly one attempt. On
 * failure, EsbException::mayHaveBeenApplied() says whether the caller may
 * report that nothing happened, or must treat the outcome as unknown.
 *
 * App\Integrations\ESB\Clients\PurchaseRequestClient is the reference
 * implementation; callers depend on this contract (ADR-016, ADR-019).
 */
interface PurchaseRequestSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     *
     * TBD (ADR-019): the thrown type is still the ESB's, as on ProductSource.
     */
    public function listPurchaseRequests(PurchaseRequestQuery $query): PurchaseRequestPage;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function findPurchaseRequest(string $number): PurchaseRequest;

    /**
     * Authorize or reject a purchase request.
     *
     * A non-idempotent write: implementations make exactly one attempt. On
     * failure, EsbException::mayHaveBeenApplied() says whether the caller may
     * report that nothing happened, or must treat the outcome as unknown.
     *
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbException
     */
    public function decidePurchaseRequest(string $number, ApprovalDecision $decision): void;

    /**
     * Create a purchase request.
     *
     * @return string|null The new document number, or null when the provider
     *                     confirmed the create without returning a usable one.
     *
     * @throws EsbException
     */
    public function createPurchaseRequest(PurchaseRequestDraft $draft): ?string;

    /**
     * Replace a purchase request with the draft.
     *
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function updatePurchaseRequest(string $number, PurchaseRequestDraft $draft): void;

    /**
     * @param  string  $number  A document number already validated by the caller.
     *
     * @throws EsbNotFoundException when the provider has no such document
     * @throws EsbException
     */
    public function deletePurchaseRequest(string $number): void;

    /**
     * Hand the provider a spreadsheet of purchase requests to create.
     *
     * The sheet layout is the provider's own; the caller has only checked
     * that it is an Excel file of an acceptable size.
     *
     * @throws EsbException
     */
    public function importPurchaseRequests(string $contents, string $filename): void;

    /**
     * Active branches a purchase request can be raised for.
     *
     * @return list<Branch>
     *
     * @throws EsbException
     */
    public function listBranches(): array;

    /**
     * Active request templates available to a branch.
     *
     * @return list<RequestTemplate>
     *
     * @throws EsbException
     */
    public function listRequestTemplates(int $branchId): array;

    /**
     * The products a template offers a branch, each with the ids a line needs.
     *
     * @return list<PurchaseRequestTemplateLine>
     *
     * @throws EsbException
     */
    public function listTemplateLines(int $branchId, int $templateId): array;
}
