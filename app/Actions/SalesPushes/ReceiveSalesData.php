<?php

namespace App\Actions\SalesPushes;

use App\Jobs\PushSalesData;
use App\Models\ApiClient;
use App\Models\SalesPush;

/**
 * Accept a transaction from a POS: store it, then queue the push to the ESB.
 */
class ReceiveSalesData
{
    /**
     * @param  array{salesHead: array<string, mixed>}  $salesData  Already validated.
     */
    public function handle(ApiClient $client, array $salesData): SalesPush
    {
        $push = SalesPush::receive($client, $salesData);

        PushSalesData::dispatch($push->id, $push->sales_num)->afterCommit();

        return $push;
    }
}
