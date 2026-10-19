<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\PurchaseRequest;
use App\Domain\Canonical\PurchaseRequestDraft;
use App\Domain\Canonical\PurchaseRequestPage;
use App\Domain\Canonical\PurchaseRequestQuery;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\PurchaseRequestMapper;

/**
 * Adapter for the ESB Purchase Request operations: list, view, create,
 * update, delete, import, the authorize/reject decision, and the branch and
 * template lookups the create form needs.
 *
 * The adapter only speaks the vendor's contract. What a failed write means
 * for the user -- auditing, the double-submit guard, "check before retrying" --
 * lives in the Actions under App\Actions\PurchaseRequests.
 */
class PurchaseRequestClient implements PurchaseRequestSource
{
    public const PROVIDER = 'esb';

    /**
     * The Purchase Request group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/purchase/purchase-request';

    /**
     * What the gateway accepts as a document number; the same shape the
     * routes enforce.
     */
    public const NUMBER_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/';

    public function __construct(
        private readonly EsbClient $client,
        private readonly PurchaseRequestMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Purchase_Request-purchase-request-index
     */
    public function listPurchaseRequests(PurchaseRequestQuery $query): PurchaseRequestPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            $this->toQueryParameters($query),
            operation: 'purchase-request.list',
        );

        return new PurchaseRequestPage(
            purchaseRequests: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Purchase_Request-view-purchase-request
     */
    public function findPurchaseRequest(string $number): PurchaseRequest
    {
        // The route already restricts the number to a plain document number;
        // encoding it as well means this adapter does not depend on that.
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'purchase-request.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }

    /**
     * Neither operation takes a body; the document number is the whole
     * request. EsbClient makes a single attempt for any non-GET.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Purchase_Request-authorize-purchase-request
     * @see https://developers.esb.co.id/esb-core/#api-Purchase_Request-reject-purchase-request
     */
    public function decidePurchaseRequest(string $number, ApprovalDecision $decision): void
    {
        $segment = match ($decision) {
            ApprovalDecision::Authorize => 'authorize',
            ApprovalDecision::Reject => 'reject',
        };

        $this->client->patch(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number).'/'.$segment,
            operation: 'purchase-request.'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Purchase_Request-create-purchase-request
     */
    public function createPurchaseRequest(PurchaseRequestDraft $draft): ?string
    {
        $envelope = $this->client->post(
            self::FAMILY,
            self::PATH,
            $this->mapper->toRequestBody($draft),
            operation: 'purchase-request.create',
        );

        $number = $envelope->resultArray()['purchaseRequestNum'] ?? null;

        // The number is later put in a URL and an upstream path. One that does
        // not look like a document number is treated as absent, not trusted.
        return is_string($number) && preg_match(self::NUMBER_PATTERN, $number) === 1 ? $number : null;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Purchase_Request-update-purchase-request
     */
    public function updatePurchaseRequest(string $number, PurchaseRequestDraft $draft): void
    {
        $this->client->put(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            $this->mapper->toRequestBody($draft),
            operation: 'purchase-request.update',
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Purchase_Request-delete-purchase-request
     */
    public function deletePurchaseRequest(string $number): void
    {
        $this->client->delete(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'purchase-request.delete',
        );
    }

    /**
     * POST /purchase/purchase-request/import, multipart field `file`. The
     * operation is in the vendor's OpenAPI description but not in the
     * generated catalog, which predates it (docs/05-api-integration.md).
     *
     * @throws EsbException
     */
    public function importPurchaseRequests(string $contents, string $filename): void
    {
        $this->client->upload(
            self::FAMILY,
            self::PATH.'/import',
            field: 'file',
            contents: $contents,
            filename: $filename,
            operation: 'purchase-request.import',
        );
    }

    /**
     * GET /branch?flagActive=1.
     *
     * @throws EsbException
     */
    public function listBranches(): array
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/branch',
            ['flagActive' => 1],
            operation: 'branch.list',
        );

        return $this->mapper->branchesFrom($envelope->resultArray());
    }

    /**
     * GET /document-template/list. `templateTypeID` is not sent: which type
     * id means "purchase request" is not documented (TBD), and a guess would
     * silently hide every template.
     *
     * @throws EsbException
     */
    public function listRequestTemplates(int $branchId): array
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/document-template/list',
            // A boolean parameter; sent as the word, not PHP's "1".
            ['branchID' => $branchId, 'flagActive' => 'true'],
            operation: 'request-template.list',
        );

        return $this->mapper->templatesFrom($envelope->resultArray());
    }

    /**
     * @throws EsbException
     */
    public function listTemplateLines(int $branchId, int $templateId): array
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/template-details',
            ['branchID' => $branchId, 'requestTemplateID' => $templateId],
            operation: 'purchase-request.template-details',
        );

        return $this->mapper->templateLinesFrom($envelope->resultArray());
    }

    /**
     * Translate the canonical filters into the vendor's parameter names.
     *
     * @return array<string, int|string>
     */
    private function toQueryParameters(PurchaseRequestQuery $query): array
    {
        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'purchaseRequestNum' => $query->number,
            'purchaseRequestDateFrom' => $query->dateFrom,
            'purchaseRequestDateTo' => $query->dateTo,
            'requiredDateFrom' => $query->requiredFrom,
            'requiredDateTo' => $query->requiredTo,
            // The vendor takes branches as one comma-separated string.
            'branchIDs' => $query->branchIds === [] ? null : implode(',', $query->branchIds),
            'indexSearch' => $query->search,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
