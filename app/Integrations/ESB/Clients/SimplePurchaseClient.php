<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\SimplePurchase;
use App\Domain\Canonical\SimplePurchaseDecision;
use App\Domain\Canonical\SimplePurchasePage;
use App\Domain\Canonical\SimplePurchaseQuery;
use App\Integrations\Contracts\SimplePurchaseSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\SimplePurchaseMapper;

/**
 * Adapter for the ESB Simple Purchase operations: list, view, delete, and the
 * authorize/reject decision.
 *
 * The adapter only speaks the vendor's contract. What a failed write means
 * for the user lives in the Actions under App\Actions\SimplePurchases.
 */
class SimplePurchaseClient implements SimplePurchaseSource
{
    public const PROVIDER = 'esb';

    /**
     * The Simple Purchase group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/purchase/simple-purchase';

    public function __construct(
        private readonly EsbClient $client,
        private readonly SimplePurchaseMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase/get
     */
    public function listSimplePurchases(SimplePurchaseQuery $query): SimplePurchasePage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            $this->toQueryParameters($query),
            operation: 'simple-purchase.list',
        );

        return new SimplePurchasePage(
            simplePurchases: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum/get
     */
    public function findSimplePurchase(string $number): SimplePurchase
    {
        // The route already restricts the number to a plain document number;
        // encoding it as well means this adapter does not depend on that.
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'simple-purchase.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }

    /**
     * Authorize takes no body. Reject requires `{reason}` -- the vendor marks
     * that body as required. EsbClient makes a single attempt for any non-GET.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum--authorize/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum--reject/patch
     */
    public function decideSimplePurchase(string $number, SimplePurchaseDecision $decision, ?string $reason = null): void
    {
        [$segment, $body] = match ($decision) {
            SimplePurchaseDecision::Authorize => ['authorize', []],
            SimplePurchaseDecision::Reject => ['reject', ['reason' => (string) $reason]],
        };

        $this->client->patch(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number).'/'.$segment,
            $body,
            operation: 'simple-purchase.'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-simple-purchase-cashPurchaseNum/delete
     */
    public function deleteSimplePurchase(string $number): void
    {
        $this->client->delete(
            self::FAMILY,
            self::PATH.'/'.rawurlencode($number),
            operation: 'simple-purchase.delete',
        );
    }

    /**
     * Translate the canonical filters into the vendor's parameter names.
     *
     * @return array<string, int|string>
     */
    private function toQueryParameters(SimplePurchaseQuery $query): array
    {
        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'cashPurchaseNum' => $query->number,
            'supplierInvoiceNum' => $query->supplierInvoiceNumber,
            'dateFrom' => $query->dateFrom,
            'dateTo' => $query->dateTo,
            'branchID' => $query->branchId,
            'indexSearch' => $query->search,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
