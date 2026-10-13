<?php

namespace App\Http\Controllers\SalesPushes;

use App\Actions\SalesPushes\RetrySalesPush;
use App\Http\Controllers\Controller;
use App\Models\SalesPush;
use Illuminate\Http\RedirectResponse;

class SalesPushRetryController extends Controller
{
    public function store(SalesPush $salesPush, RetrySalesPush $retry): RedirectResponse
    {
        if ($retry->handle($salesPush)) {
            $this->toast('success', sprintf('Sales %s was queued to be sent again.', $salesPush->sales_num));
        } else {
            $this->toast('warning', sprintf(
                'Sales %s cannot be sent again: it is not failed or rejected, or a newer push of it exists.',
                $salesPush->sales_num,
            ));
        }

        return redirect()->route('sales-pushes.show', $salesPush);
    }
}
