<?php

namespace App\Http\Controllers\Pricelists;

use App\Domain\Canonical\TempPricelistQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pricelists\ListPricelistsRequest;
use App\Integrations\Contracts\PricelistSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Active pricelist, temporary pricelists and one temporary pricelist,
 * read-only ([ESB-027]). A not-found refusal is rendered as a 404 by
 * EsbExceptionRenderer.
 */
class PricelistController extends Controller
{
    /**
     * /pricelist/temp/{pricelistNum} shares its segment with the literal
     * /pricelist/temp/export, so a number must contain a digit.
     */
    public const TEMP_NUMBER = '/^(?=.*[0-9])[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/';

    public function index(ListPricelistsRequest $request, PricelistSource $pricelists): Response
    {
        $page = $pricelists->listPrices($request->toQuery());

        return Inertia::render('pricelists/index', [
            'entries' => $page->entries,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function tempIndex(ListPricelistsRequest $request, PricelistSource $pricelists): Response
    {
        $page = $pricelists->listTempPricelists($request->toTempQuery());

        return Inertia::render('pricelists/temp-index', [
            'pricelists' => $page->pricelists,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(temp: true),
            'statuses' => TempPricelistQuery::STATUSES,
        ]);
    }

    public function tempShow(string $pricelistNum, PricelistSource $pricelists): Response
    {
        abort_unless(preg_match(self::TEMP_NUMBER, $pricelistNum) === 1, 404);

        $pricelist = $pricelists->findTempPricelist($pricelistNum);

        abort_if($pricelist === null, 404);

        return Inertia::render('pricelists/temp-show', [
            'pricelist' => $pricelist,
        ]);
    }
}
