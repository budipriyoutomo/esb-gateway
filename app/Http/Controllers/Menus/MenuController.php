<?php

namespace App\Http\Controllers\Menus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menus\ListMenusRequest;
use App\Integrations\Contracts\MenuSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master menu list, read-only ([ESB-012]).
 *
 * Depends on the contract, not on the ESB adapter (ADR-016).
 */
class MenuController extends Controller
{
    public function index(ListMenusRequest $request, MenuSource $menus): Response
    {
        $page = $menus->listMenus($request->toQuery());

        return Inertia::render('menus/index', [
            'menus' => $page->menus,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }
}
