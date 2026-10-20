<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\PricelistDraft;
use App\Domain\Canonical\PricelistPage;
use App\Domain\Canonical\PricelistQuery;
use App\Domain\Canonical\PurchasePrice;
use App\Domain\Canonical\TempPricelist;
use App\Domain\Canonical\TempPricelistPage;
use App\Domain\Canonical\TempPricelistQuery;
use App\Integrations\Contracts\PricelistSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\PricelistMapper;

/**
 * Adapter for ESB Core pricelists, read-only ([ESB-027]).
 */
class PricelistClient implements PricelistSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/pricelist';

    public function __construct(
        private readonly EsbClient $client,
        private readonly PricelistMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist/get
     */
    public function listPrices(PricelistQuery $query): PricelistPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'indexSearch' => $query->search,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'pricelist.list',
        );

        return new PricelistPage(
            entries: $this->mapper->entriesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-temp/get
     */
    public function listTempPricelists(TempPricelistQuery $query): TempPricelistPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/temp',
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'indexSearch' => $query->search,
                'statusID' => $query->statusId,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'pricelist.temp-list',
        );

        return new TempPricelistPage(
            pricelists: $this->mapper->tempSummariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum/get
     */
    public function findTempPricelist(string $number): ?TempPricelist
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/temp/'.rawurlencode($number),
            operation: 'pricelist.temp-view',
        );

        return $this->mapper->tempFromDetail($envelope->resultArray());
    }

    /**
     * Neither decision takes a body.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum--authorize/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum--reject/patch
     */
    public function decideTempPricelist(string $number, ApprovalDecision $decision): void
    {
        $segment = match ($decision) {
            ApprovalDecision::Authorize => 'authorize',
            ApprovalDecision::Reject => 'reject',
        };

        $this->client->patch(
            self::FAMILY,
            self::PATH.'/temp/'.rawurlencode($number).'/'.$segment,
            operation: 'pricelist.temp-'.$segment,
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum/delete
     */
    public function deleteTempPricelist(string $number): void
    {
        $this->client->delete(self::FAMILY, self::PATH.'/temp/'.rawurlencode($number), operation: 'pricelist.temp-delete');
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist/post
     */
    public function createPricelist(PricelistDraft $draft): void
    {
        $this->client->post(self::FAMILY, self::PATH, $this->mapper->requestFrom($draft), operation: 'pricelist.create');
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-temp-pricelistNum/put
     */
    public function updateTempPricelist(string $number, PricelistDraft $draft): void
    {
        $this->client->put(self::FAMILY, self::PATH.'/temp/'.rawurlencode($number), $this->mapper->requestFrom($draft), operation: 'pricelist.update');
    }

    /**
     * @param  list<int>  $productDetailIds
     * @return array<int, int|float|null>
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-last-price/get
     */
    public function lastPrices(int $supplierId, int $currencyId, string $priceDate, array $productDetailIds): array
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/last-price', [
            'supplierID' => $supplierId,
            'currencyID' => $currencyId,
            'productDetailIDs' => implode(',', $productDetailIds),
            'priceDate' => $priceDate,
        ], operation: 'pricelist.last-price');

        return $this->mapper->lastPricesFrom($envelope->resultArray());
    }

    /**
     * @return list<PurchasePrice>
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-history-purchase-invoice/get
     */
    public function purchasePrices(int $supplierId, int $currencyId, int $productDetailId, bool $otherSuppliers): array
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/history/purchase-invoice', [
            'supplierID' => $supplierId,
            'currencyID' => $currencyId,
            'productDetailID' => $productDetailId,
            'flagShowAll' => $otherSuppliers ? 1 : 0,
        ], operation: 'pricelist.purchase-history');

        return $this->mapper->purchasePricesFrom($envelope->resultArray());
    }
}
