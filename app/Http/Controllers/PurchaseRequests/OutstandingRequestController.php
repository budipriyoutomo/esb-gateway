<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Actions\PurchaseRequests\BuildOutstandingRequestReport;
use App\Actions\PurchaseRequests\TooManyOrdersForReport;
use App\Domain\Canonical\OutstandingStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\ListOutstandingRequestsRequest;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The PR outstanding report (ADR-034): request items not ordered, ordered
 * and not received, or received not as requested. Nothing is read until a
 * required-date period is given. Too many open orders for one report is
 * said on the page, where the filters can be narrowed; any other failure is
 * the usual error page.
 */
class OutstandingRequestController extends Controller
{
    public function index(ListOutstandingRequestsRequest $request, BuildOutstandingRequestReport $build): Response
    {
        $query = $request->toQuery();
        $report = null;
        $error = null;

        if ($query !== null) {
            try {
                $report = $build($query, CarbonImmutable::now());
            } catch (TooManyOrdersForReport $exception) {
                $error = $exception->getMessage();
            }
        }

        return Inertia::render('purchase-requests/outstanding', [
            'report' => $report,
            'filters' => $request->validatedFilters(),
            'stages' => array_map(
                static fn (OutstandingStage $stage): array => ['value' => $stage->value, 'label' => $stage->label()],
                OutstandingStage::cases(),
            ),
            'error' => $error,
        ]);
    }
}
