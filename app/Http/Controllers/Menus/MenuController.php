<?php

namespace App\Http\Controllers\Menus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menus\ListMenusRequest;
use App\Integrations\Contracts\MasterMenuSource;
use App\Integrations\Contracts\MenuSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The one menu list, read-only, with a source tab (ADR-030): ESB Core
 * ([ESB-012]) or ESB OMS ([ESB-013]). Only the chosen source is called.
 *
 * Depends on the contracts, not on the ESB adapters (ADR-016).
 */
class MenuController extends Controller
{
    public function index(ListMenusRequest $request, MenuSource $coreMenus, MasterMenuSource $omsMenus): Response
    {
        $source = $request->source();

        $page = $source === ListMenusRequest::SOURCE_OMS
            ? $omsMenus->listMenus($request->toOmsQuery())
            : $coreMenus->listMenus($request->toQuery());

        return Inertia::render('menus/index', [
            'source' => $source,
            'menus' => $page->menus,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }
}
