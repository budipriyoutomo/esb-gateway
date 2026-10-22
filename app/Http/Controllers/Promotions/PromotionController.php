<?php

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\ListPromotionsRequest;
use App\Integrations\Contracts\PromotionSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * POS promotions ([ESB-049], S1). Authorization (esb.invoke) is on the
 * form request.
 */
class PromotionController extends Controller
{
    public function index(ListPromotionsRequest $request, PromotionSource $promotions): Response
    {
        $page = $promotions->listPromotions($request->toQuery());

        return Inertia::render('promotions/index', [
            'promotions' => $page->promotions,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }
}
