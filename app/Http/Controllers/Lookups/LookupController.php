<?php

namespace App\Http\Controllers\Lookups;

use App\Domain\Canonical\LookupKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lookups\LookupRequest;
use App\Integrations\Contracts\LookupSource;
use Illuminate\Http\JsonResponse;

/**
 * JSON for searchable pickers ([ESB-019]). An ESB failure is rendered as
 * JSON by EsbExceptionRenderer.
 */
class LookupController extends Controller
{
    public function show(LookupKind $kind, LookupRequest $request, LookupSource $lookups): JsonResponse
    {
        return response()->json($lookups->lookup($kind, $request->toQuery()));
    }
}
