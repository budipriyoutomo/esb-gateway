<?php

namespace Tests\Fixtures;

/**
 * An ESB OMS Get Menu response, transcribed from the vendor's "Success -
 * Response" example (docs/05-api-integration.md, [ESB-013]).
 */
final class OmsGetMenu
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'menuID' => 5564,
            'categoryDetail' => 'Food - RX - Snack',
            'bomID' => 0,
            'bomName' => '',
            'menuCode' => 'PS01',
            'menuName' => 'Paket Separate',
            'flagActive' => 1,
            'menuShortName' => 'Paket Separate',
            'alternativeMenuName' => 'Paket Separate',
            'flagTax' => 'Yes, VAT',
            'flagOtherTax' => 'Yes',
            'zeroValueText' => '0',
            'salesAccount' => 'Sales - Food',
            'cogsAccount' => 'COGS - Food',
            'discountAccount' => 'Food Discount',
            'description' => '',
            'menuImage' => 'https://cdn.esb.example/menu/5564.png',
            'flagOpenPrice' => 'No',
            'printZeroValue' => 'Yes',
            'themeMenuOnPos' => '',
            'notes' => '',
            'menuTemplates' => [
                ['menuTemplateID' => 18, 'menuTemplateName' => 'RX Rest', 'flagActive' => 1, 'price' => '20000.0000'],
                ['menuTemplateID' => 307, 'menuTemplateName' => 'Coba Testing', 'flagActive' => 0, 'price' => '0.0000'],
            ],
            'menuPackages' => [
                'flagSeparatePrintPackage' => 'Yes',
                'flagSeparateTaxCalculation' => 'No',
                'menuGroup' => [
                    [
                        'menuGroupID' => 583,
                        'menuGroupName' => 'Burger',
                        'flagActive' => 1,
                        'orderID' => 0,
                        'minQty' => '0.0000',
                        'maxQty' => '999999.0000',
                        'notes' => '',
                        'menus' => [
                            [
                                'menuID' => 910,
                                'menuName' => 'Burger Open',
                                'flagActive' => 1,
                                'menuCode' => '',
                                'additionalPrice' => '5000.0000',
                                'defaultItem' => 'No',
                                'menuTemplatePackages' => [['menuTemplateID' => 18, 'price' => '5000.0000']],
                            ],
                            [
                                'menuID' => 4835,
                                'menuName' => 'Burger 1 Rupiah',
                                'flagActive' => 1,
                                'menuCode' => 'BGR1R',
                                'additionalPrice' => '7000.0000',
                                'defaultItem' => 'Yes',
                                'menuTemplatePackages' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'menuExtras' => [
                [
                    'menuExtraID' => 2649,
                    'menuID' => 5564,
                    'menuExtraName' => 'Beef Burger',
                    'flagActive' => 1,
                    'minExtraQty' => '0.0000',
                    'maxExtraQty' => '10.0000',
                    'price' => '1000.0000',
                ],
            ],
            'menuIcons' => [
                ['menuIconName' => 'Recommended', 'menuIconUrl' => 'https://esb.com/recommended.png'],
            ],
            'menuTags' => [
                ['tagName' => 'Package'],
            ],
            'relatedMenus' => [
                ['menuID' => 201, 'menuName' => 'Paket Snack', 'menuCode' => 'PSN01'],
            ],
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return array<string, mixed>
     */
    public static function response(?array $rows = null, int $count = 1): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu?page=1',
            'timestamp' => '2025-05-13 08:45:40',
            'code' => 'EC011200',
            'status' => 'ok',
            'message' => false,
            'result' => [
                'page' => '1',
                'limit' => 20,
                'count' => $count,
                'data' => $rows ?? [self::row()],
            ],
            'next' => null,
            'prev' => null,
        ];
    }
}
