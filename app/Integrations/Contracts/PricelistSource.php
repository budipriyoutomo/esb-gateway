<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\PricelistDraft;
use App\Domain\Canonical\PricelistPage;
use App\Domain\Canonical\PricelistQuery;
use App\Domain\Canonical\PurchasePrice;
use App\Domain\Canonical\TempPricelist;
use App\Domain\Canonical\TempPricelistPage;
use App\Domain\Canonical\TempPricelistQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for supplier prices. Read-only ([ESB-027]).
 */
interface PricelistSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listPrices(PricelistQuery $query): PricelistPage;

    /**
     * @throws EsbException
     */
    public function listTempPricelists(TempPricelistQuery $query): TempPricelistPage;

    /**
     * The temporary pricelist with this number, or null when the provider
     * answers successfully with none. Callers pass a number already held to
     * the gateway's pattern.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findTempPricelist(string $number): ?TempPricelist;

    /**
     * Authorize (its prices become active and the proposal is removed) or
     * reject a temporary pricelist. Only "Waiting for Approval" ones are
     * accepted by the ERP. One attempt.
     *
     * @throws EsbException
     */
    public function decideTempPricelist(string $number, ApprovalDecision $decision): void;

    /**
     * Delete a temporary pricelist. One attempt.
     *
     * @throws EsbException
     */
    public function deleteTempPricelist(string $number): void;

    /**
     * Propose a pricelist. Whether every create becomes a temporary pricelist
     * awaiting approval is not documented (TODO-modules §2.5). One attempt.
     *
     * @throws EsbException
     */
    public function createPricelist(PricelistDraft $draft): void;

    /**
     * Replace a proposed (temporary) pricelist. One attempt.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function updateTempPricelist(string $number, PricelistDraft $draft): void;

    /**
     * The active price of each product detail for a supplier and currency on
     * a date: productDetailId => price, null when it has none.
     *
     * @param  list<int>  $productDetailIds
     * @return array<int, int|float|null>
     *
     * @throws EsbException
     */
    public function lastPrices(int $supplierId, int $currencyId, string $priceDate, array $productDetailIds): array;

    /**
     * Up to five recent purchase invoice prices of a product detail; with
     * $otherSuppliers, other suppliers' invoices instead of this one's.
     *
     * @return list<PurchasePrice>
     *
     * @throws EsbException
     */
    public function purchasePrices(int $supplierId, int $currencyId, int $productDetailId, bool $otherSuppliers): array;
}
