<?php

namespace Tests\Fixtures;

/**
 * ESB OMS Master Menu Category responses ([ESB-047], [ESB-048]),
 * transcribed from the vendor's "Success - Response" and error examples.
 */
final class OmsMenuCategory
{
    public const ID = 16;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'menuCategoryID' => self::ID,
            'menuCategoryName' => 'Food-Int',
            'menuCategoryCode' => 'FINT',
            'salesAccount' => 'Sales - Food',
            'cogsAccount' => 'COGS - Food',
            'discountAccount' => 'Food Discount',
            'notes' => '',
            'description' => 'Makanan Cita Rasa Indonesia',
            'status' => 'Active',
            'buttonColor' => '',
            'menuCategoryDetails' => [
                [
                    'menuCategoryDetailID' => 34,
                    'menuCategoryDetailName' => 'Main Course-Int',
                    'menuCategoryDetailCode' => '',
                    'maxOrderQty' => 1,
                    'status' => 'Active',
                    'orderID' => null,
                    'description' => '',
                    'buttonColor' => '#a4ff70',
                ],
            ],
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function list(array $rows, int $page = 1, int $count = 16, int $limit = 10): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu-category?page='.$page,
            'timeStamp' => '2023-11-13 17:19:45',
            'status' => 'ok',
            'code' => 'EC011200',
            'result' => [
                'page' => (string) $page,
                'limit' => $limit,
                'count' => $count,
                'data' => $rows,
            ],
            'next' => null,
            'prev' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu-category?page=1',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $result
     * @return array<string, mixed>
     */
    public static function saved(array $result): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/create-menu-category',
            'timeStamp' => '2023-11-13 17:06:03',
            'status' => 'ok',
            'code' => 'EC011200',
            'result' => $result,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function refused(string $message): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/create-menu-category',
            'timeStamp' => '2023-11-13 16:56:10',
            'status' => 'failed',
            'code' => 'EC0110',
            'message' => $message,
            'result' => [],
        ];
    }
}
