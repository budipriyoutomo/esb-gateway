<?php

namespace App\Http\Controllers\MasterMenus;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterMenus\ListMasterMenusRequest;
use App\Http\Requests\Menus\ListMenusRequest;
use App\Integrations\Contracts\MasterMenuSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master menu detail and extras, read-only ([ESB-013]). The list is the
 * oms tab of /menus (ADR-030).
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The code
 * reaching show() has already been constrained by the route.
 */
class MasterMenuController extends Controller
{
    /**
     * The OMS list is now the oms tab of the one menu list (ADR-030). Old
     * links keep their page and code; anything else is dropped.
     */
    public function index(Request $request): RedirectResponse
    {
        $query = array_filter([
            'source' => ListMenusRequest::SOURCE_OMS,
            'page' => is_scalar($page = $request->query('page')) ? (string) $page : null,
            'menuCode' => is_scalar($code = $request->query('menuCode')) ? (string) $code : null,
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return redirect()->route('menus.index', $query, 301);
    }

    /**
     * The extras of one page of menus, each with the menu it belongs to. The
     * catalog documents no extra endpoint, so this is what Get Menu already
     * returns -- read-only, paged like the menu list.
     */
    public function extras(ListMasterMenusRequest $request, MasterMenuSource $menus): Response
    {
        $page = $menus->listMenus($request->toQuery());

        $extras = [];

        foreach ($page->menus as $menu) {
            foreach ($menu->extras as $extra) {
                $extras[] = [
                    'menuCode' => $menu->code,
                    'menuName' => $menu->name,
                    ...$extra->jsonSerialize(),
                ];
            }
        }

        return Inertia::render('master-menus/extras', [
            'extras' => $extras,
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
