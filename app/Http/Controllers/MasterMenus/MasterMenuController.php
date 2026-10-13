<?php

namespace App\Http\Controllers\MasterMenus;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterMenus\ListMasterMenusRequest;
use App\Integrations\Contracts\MasterMenuSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master menu list and detail, read-only ([ESB-013]).
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The code
 * reaching show() has already been constrained by the route.
 */
class MasterMenuController extends Controller
{
    public function index(ListMasterMenusRequest $request, MasterMenuSource $menus): Response
    {
        $page = $menus->listMenus($request->toQuery());

        return Inertia::render('master-menus/index', [
            'menus' => $page->menus,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(string $code, MasterMenuSource $menus): Response
    {
        $menu = $menus->findMenu($code);

        abort_if($menu === null, 404);

        return Inertia::render('master-menus/show', [
            'menu' => $menu,
        ]);
    }
}
