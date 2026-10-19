<?php

namespace App\Http\Controllers\CoaAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\CoaAccounts\ListCoaAccountsRequest;
use App\Integrations\Contracts\CoaAccountSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Chart of accounts outline and account detail, read-only ([ESB-025]). The
 * account number reaching show() is held by the route to start with a digit,
 * so it cannot reach another vendor path. A not-found refusal is rendered as
 * a 404 by EsbExceptionRenderer.
 */
class CoaAccountController extends Controller
{
    public function index(ListCoaAccountsRequest $request, CoaAccountSource $accounts): Response
    {
        $page = $accounts->listAccounts($request->toQuery());

        return Inertia::render('coa-accounts/index', [
            'accounts' => $page->accounts,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(string $coaNo, CoaAccountSource $accounts): Response
    {
        $account = $accounts->findAccount($coaNo);

        abort_if($account === null, 404);

        return Inertia::render('coa-accounts/show', [
            'account' => $account,
        ]);
    }
}
