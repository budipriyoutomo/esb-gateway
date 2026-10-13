<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\PurchaseInvoice;
use App\Domain\Canonical\PurchaseInvoiceDecision;
use App\Domain\Canonical\PurchaseInvoicePage;
use App\Domain\Canonical\PurchaseInvoiceQuery;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\PurchaseInvoiceMapper;

/**
 * Adapter for the ESB Purchase Invoice operations: list, view, delete, and
 * the authorize/reject decision.
 *
 * The adapter only speaks the vendor's contract. What a failed write means
 * for the user lives in the Actions under App\Actions\PurchaseInvoices.
 */
class PurchaseInvoiceClient implements PurchaseInvoiceSource
{
    public const PROVIDER = 'esb';

    /**
     * The Purchase Invoice group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/purchase/purchase-invoices';

    public function __construct(
        private readonly EsbClient $client,
        private readonly PurchaseInvoiceMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices/get
     */
    public function listPurchaseInvoices(PurchaseInvoiceQuery $query): PurchaseInvoicePage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            $this->toQueryParameters($query),
            operation: 'purchase-invoice.list',
        );

        return new PurchaseInvoicePage(
            purchaseInvoices: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum/get
     */
    public function findPurchaseInvoice(string $number): PurchaseInvoice
    {
        // The route already restricts the number to a plain document number;
        // encoding it as well means this adapter does not depend on that.
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'purchase-invoice.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }

    /**
     * Neither operation takes a body. EsbClient makes a single attempt for
     * any non-GET.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--authorize/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum--reject/patch
     */
    public function decidePurchaseInvoice(string $number, PurchaseInvoiceDecision $decision): void
    {
        $segment = match ($decision) {
            PurchaseInvoiceDecision::Authorize => 'authorize',
            PurchaseInvoiceDecision::Reject => 'reject',
        };

        $this->client->patch(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number).'/'.$segment,
            operation: 'purchase-invoice.'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-purchase-invoices-purchaseInvoiceNum/delete
     */
    public function deletePurchaseInvoice(string $number): void
    {
        $this->client->delete(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'purchase-invoice.delete',
        );
    }

    /**
     * Translate the canonical filters into the vendor's parameter names.
     *
     * @return array<string, int|string>
     */
    private function toQueryParameters(PurchaseInvoiceQuery $query): array
    {
        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'purchaseInvoiceNum' => $query->number,
            'supplierInvoiceNum' => $query->supplierInvoiceNumber,
            'dateFrom' => $query->dateFrom,
            'dateTo' => $query->dateTo,
            // Documented as form style with explode false: one comma-separated value.
            'branchIDs' => $query->branchIds === [] ? null : implode(',', $query->branchIds),
            'indexSearch' => $query->search,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
