<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\PurchaseOrder;
use App\Domain\Canonical\PurchaseOrderPage;
use App\Domain\Canonical\PurchaseOrderQuery;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\PurchaseOrderMapper;

/**
 * Adapter for the ESB Purchase Order operations: list, view, delete, and the
 * authorize/reject decision.
 *
 * The adapter only speaks the vendor's contract. What a failed write means
 * for the user lives in the Actions under App\Actions\PurchaseOrders.
 */
class PurchaseOrderClient implements PurchaseOrderSource
{
    public const PROVIDER = 'esb';

    /**
     * The Purchase Order group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/purchase/purchase-order';

    public function __construct(
        private readonly EsbClient $client,
        private readonly PurchaseOrderMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order/get
     */
    public function listPurchaseOrders(PurchaseOrderQuery $query): PurchaseOrderPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            $this->toQueryParameters($query),
            operation: 'purchase-order.list',
        );

        return new PurchaseOrderPage(
            purchaseOrders: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum/get
     */
    public function findPurchaseOrder(string $number): PurchaseOrder
    {
        // The route already restricts the number to a plain document number;
        // encoding it as well means this adapter does not depend on that.
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'purchase-order.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }

    /**
     * Authorize takes no body. Reject requires `{reason}` -- the vendor marks
     * that body as required. EsbClient makes a single attempt for any non-GET.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--authorize/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum--reject/patch
     */
    public function decidePurchaseOrder(string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        [$segment, $body] = match ($decision) {
            ApprovalDecision::Authorize => ['authorize', []],
            ApprovalDecision::Reject => ['reject', ['reason' => (string) $reason]],
        };

        $this->client->patch(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number).'/'.$segment,
            $body,
            operation: 'purchase-order.'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-order-purchaseNum/delete
     */
    public function deletePurchaseOrder(string $number): void
    {
        $this->client->delete(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'purchase-order.delete',
        );
    }

    /**
     * Translate the canonical filters into the vendor's parameter names.
     *
     * @return array<string, int|string>
     */
    private function toQueryParameters(PurchaseOrderQuery $query): array
    {
        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'purchaseNum' => $query->number,
            'dateFrom' => $query->dateFrom,
            'dateTo' => $query->dateTo,
            'requiredDateFrom' => $query->requiredDateFrom,
            'requiredDateTo' => $query->requiredDateTo,
            // Documented as "comma-separated".
            'branchIDs' => $query->branchIds === [] ? null : implode(',', $query->branchIds),
            'indexSearch' => $query->search,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
