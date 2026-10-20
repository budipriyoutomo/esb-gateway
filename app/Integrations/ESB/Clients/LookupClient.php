<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\LookupKind;
use App\Domain\Canonical\LookupOption;
use App\Domain\Canonical\LookupPage;
use App\Domain\Canonical\LookupQuery;
use App\Integrations\Contracts\LookupSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\LookupMapper;
use App\Integrations\ESB\Responses\EsbEnvelope;

/**
 * Adapter for the ESB Core reference lists behind the pickers ([ESB-019]).
 *
 * Most lists come back whole ("all, no pagination"); they are searched and
 * paged here. COA accounts and transfer products are paged by the ESB, so the
 * search and page are sent upstream.
 */
class LookupClient implements LookupSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    public function __construct(
        private readonly EsbClient $client,
        private readonly LookupMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/branch/get
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-list/get
     * @see https://developers.esb.co.id/esb-core/#/paths/location-list/get
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-coa-active-non-module/get
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-coa-cash-bank/get
     * @see https://developers.esb.co.id/esb-core/#/paths/common-status/get
     * @see https://developers.esb.co.id/esb-core/#/paths/product-browse-transfer/get
     * @see https://developers.esb.co.id/esb-core/#/paths/common-metrics/get
     * @see https://developers.esb.co.id/esb-core/#/paths/common-category-types-list/get
     * @see https://developers.esb.co.id/esb-core/#/paths/tax-list/get
     * @see https://developers.esb.co.id/esb-core/#/paths/product-category-all/get
     * @see https://developers.esb.co.id/esb-core/#/paths/product-sub-category-all/get
     * @see https://developers.esb.co.id/esb-core/#/paths/units-list/get
     * @see https://developers.esb.co.id/esb-core/#/paths/common-currency-active/get
     * @see https://developers.esb.co.id/esb-core/#/paths/product-detail-browse/get
     * @see https://developers.esb.co.id/esb-core/#/paths/purpose/get
     */
    public function lookup(LookupKind $kind, LookupQuery $query): LookupPage
    {
        return match ($kind) {
            LookupKind::Branches => $this->whole($kind, '/branch', ['flagActive' => 1], $query),
            LookupKind::Suppliers => $this->whole($kind, '/supplier/list', ['flagActive' => 1], $query),
            // No branch filter is sent: branchIDs is a multi-value parameter
            // whose wire format is undocumented. Rows carry their branch.
            LookupKind::Locations => $this->whole($kind, '/location/list', ['flagActive' => 1], $query),
            LookupKind::CashBankAccounts => $this->whole($kind, '/accounting/coa/cash-bank', [], $query),
            LookupKind::Statuses => $this->whole($kind, '/common/status', [], $query),
            LookupKind::Metrics => $this->whole($kind, '/common/metrics', [], $query),
            LookupKind::CategoryTypes => $this->whole($kind, '/common/category-types/list', [], $query),
            // Inactive taxes are dropped by the mapper: /tax/list documents no status filter.
            LookupKind::Taxes => $this->whole($kind, '/tax/list', [], $query),
            // No status filter is documented for /product/category/all; the
            // mapper drops inactive rows.
            LookupKind::ProductCategories => $this->whole($kind, '/product/category/all', [], $query),
            LookupKind::ProductSubCategories => $this->whole($kind, '/product/sub-category/all', ['flagActive' => 1], $query),
            LookupKind::Units => $this->whole($kind, '/units/list', ['flagActive' => 1], $query),
            LookupKind::Currencies => $this->whole($kind, '/common/currency/active', [], $query),
            LookupKind::ProductDetails => $this->paged($kind, '/product-detail/browse', [
                'productName' => $this->searchOrNull($query->search),
            ], $query),
            LookupKind::CoaAccounts => $this->paged($kind, '/accounting/coa/active-non-module', [
                ...$this->accountSearch($query->search),
                'sort' => 'coaNo',
            ], $query),
            // Where a purpose may be used (purposeAppliedTo) has undocumented
            // values, so every active purpose is offered.
            LookupKind::Purposes => $this->paged($kind, '/purpose', [
                'purposeName' => $this->searchOrNull($query->search),
                'flagActive' => 1,
            ], $query),
            LookupKind::TransferProducts => $this->paged($kind, '/product/browse-transfer', [
                'branchID' => $query->branchId,
                'productName' => $this->searchOrNull($query->search),
            ], $query),
        };
    }

    /**
     * @param  array<string, int|string>  $params
     */
    private function whole(LookupKind $kind, string $path, array $params, LookupQuery $query): LookupPage
    {
        $envelope = $this->client->get(self::FAMILY, $path, $params, operation: 'lookup.'.$kind->value);

        $options = $this->mapper->optionsFrom($kind, $envelope->resultArray());

        if ($query->branchId !== null && $kind->acceptsBranch()) {
            $options = array_values(array_filter(
                $options,
                static fn (LookupOption $option): bool => ($option->meta['branchId'] ?? null) === $query->branchId,
            ));
        }

        return LookupPage::fromAll($options, $query->search, $query->page, $query->limit);
    }

    /**
     * @param  array<string, int|string|null>  $params
     */
    private function paged(LookupKind $kind, string $path, array $params, LookupQuery $query): LookupPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            $path,
            array_filter(
                [...$params, 'page' => $query->page, 'limit' => $query->limit],
                static fn (mixed $value): bool => $value !== null,
            ),
            operation: 'lookup.'.$kind->value,
        );

        return new LookupPage(
            options: $this->mapper->optionsFrom($kind, $envelope->rows()),
            hasMore: $this->hasMore($envelope),
        );
    }

    private function hasMore(EsbEnvelope $envelope): bool
    {
        return $envelope->pagination()?->hasMorePages() ?? false;
    }

    /**
     * The vendor documents separate coaNo and description filters and no
     * combined search: text starting with a digit is taken as a number.
     *
     * @return array<string, string>
     */
    private function accountSearch(?string $search): array
    {
        $search = $this->searchOrNull($search);

        if ($search === null) {
            return [];
        }

        return ctype_digit($search[0]) ? ['coaNo' => $search] : ['description' => $search];
    }

    private function searchOrNull(?string $search): ?string
    {
        $search = trim((string) $search);

        return $search === '' ? null : $search;
    }
}
