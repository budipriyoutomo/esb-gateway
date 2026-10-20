<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\MasterDataImport;
use App\Integrations\Contracts\MasterDataImportSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Adapter for the ESB Core spreadsheet imports ([ESB-032]). Every one takes
 * a single multipart field "file"; the verb is the vendor's (product and
 * supplier changes are PUT / PATCH on the same path as their creates).
 */
class MasterDataImportClient implements MasterDataImportSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    public function __construct(private readonly EsbClient $client) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @return array<string, int>
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/units-import/post
     * @see https://developers.esb.co.id/esb-core/#/paths/product-category-upload/post
     * @see https://developers.esb.co.id/esb-core/#/paths/product-sub-category-upload/post
     * @see https://developers.esb.co.id/esb-core/#/paths/product-import/post
     * @see https://developers.esb.co.id/esb-core/#/paths/product-import/put
     * @see https://developers.esb.co.id/esb-core/#/paths/pricelist-import/post
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-upload/post
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-upload/patch
     * @see https://developers.esb.co.id/esb-core/#/paths/accounting-coa-upload/post
     * @see https://developers.esb.co.id/esb-core/#/paths/simple-transfer-import/post
     * @see https://developers.esb.co.id/esb-core/#/paths/finance-disbursement-import/post
     */
    public function import(MasterDataImport $kind, string $contents, string $filename): array
    {
        [$method, $path] = match ($kind) {
            MasterDataImport::Units => ['POST', '/units/import'],
            MasterDataImport::ProductCategories => ['POST', '/product/category/upload'],
            MasterDataImport::ProductSubCategories => ['POST', '/product/sub-category/upload'],
            MasterDataImport::NewProducts => ['POST', '/product/import'],
            MasterDataImport::ProductChanges => ['PUT', '/product/import'],
            MasterDataImport::Pricelists => ['POST', '/pricelist/import'],
            MasterDataImport::NewSuppliers => ['POST', '/supplier/upload'],
            MasterDataImport::SupplierChanges => ['PATCH', '/supplier/upload'],
            MasterDataImport::CoaAccounts => ['POST', '/accounting/coa/upload'],
            MasterDataImport::SimpleTransfers => ['POST', '/simple-transfer/import'],
            MasterDataImport::Disbursements => ['POST', '/finance/disbursement/import'],
        };

        $envelope = $this->client->upload(
            self::FAMILY,
            $path,
            field: 'file',
            contents: $contents,
            filename: $filename,
            operation: 'import.'.$kind->value,
            method: $method,
        );

        if ($kind !== MasterDataImport::CoaAccounts) {
            return [];
        }

        $result = $envelope->resultArray();

        return array_filter([
            'accounts' => is_int($result['coaCount'] ?? null) ? $result['coaCount'] : null,
            'mappings' => is_int($result['accountMappingCount'] ?? null) ? $result['accountMappingCount'] : null,
        ], static fn (?int $count): bool => $count !== null);
    }
}
