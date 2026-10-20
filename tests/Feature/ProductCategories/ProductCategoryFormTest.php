<?php

namespace Tests\Feature\ProductCategories;

use Tests\Feature\MasterData\MasterDataFormTestCase;
use Tests\Fixtures\CoreProductCategory;

/**
 * Creating and updating product categories ([ESB-030], S3). Every documented field is sent, empty ones as the vendor's own "none" ("" / 0), so an update replaces the whole record.
 */
class ProductCategoryFormTest extends MasterDataFormTestCase
{
    protected function resource(): string
    {
        return 'product-categories';
    }

    protected function prefix(): string
    {
        return 'product-category';
    }

    protected function pageComponent(): string
    {
        return 'product-categories/form';
    }

    protected function recordProp(): string
    {
        return 'category';
    }

    protected function sampleId(): int
    {
        return 3;
    }

    protected function createPath(): string
    {
        return '/product/category';
    }

    protected function updatePath(): string
    {
        return '/product/category/3';
    }

    protected function detailResponse(): array
    {
        return CoreProductCategory::detailResponse();
    }

    protected function validInput(): array
    {
        return [
            'name' => 'Bahan Baku',
            'code' => 'BB',
            'typeId' => 1,
            'notes' => 'Raw materials',
            'coaNo' => '1301',
            'cogsCoaNo' => '5101',
            'accrualBasisCoaNo' => null,
            'opnameCoaNo' => '5901',
            'productSalesCoaNo' => null,
            'depreciationCoaNo' => null,
            'depreciationExpenseCoaNo' => null,
            'depreciationMonths' => null,
            'fiscalDepreciationMonths' => 48,
            'depreciationTypeId' => null,
            'fiscalDepreciationTypeId' => 2,
            'assetTax' => true,
        ];
    }

    protected function expectedBody(): array
    {
        return [
            'categoryName' => 'Bahan Baku',
            'categoryCode' => 'BB',
            'categoryTypeID' => 1,
            'notes' => 'Raw materials',
            'coaNo' => '1301',
            'cogsCoaNo' => '5101',
            'accrualBasisCoaNo' => '',
            'opnameCoaNo' => '5901',
            'productSalesCoaNo' => '',
            'depCoaNo' => '',
            'depExpenseCoaNo' => '',
            'depLength' => 0,
            'depLengthFiscal' => 48,
            'depreciationTypeID' => 0,
            'depreciationTypeIDFiscal' => 2,
            'flagAssetTax' => 1,
        ];
    }

    protected function invalidInputs(): array
    {
        return [
            [['name' => ''], 'name'],
            [['code' => 'ABCD'], 'code'],
            [['typeId' => null], 'typeId'],
            [['coaNo' => str_repeat('1', 21)], 'coaNo'],
            [['cogsCoaNo' => '../x'], 'cogsCoaNo'],
            [['depreciationMonths' => -1], 'depreciationMonths'],
            [['assetTax' => 'maybe'], 'assetTax'],
        ];
    }

    protected function vendorFieldError(): array
    {
        return ['cogsCoaNo', 'cogsCoaNo'];
    }
}
