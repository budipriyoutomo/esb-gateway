<?php

namespace App\Http\Controllers\EsbConnections;

use App\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Integrations\ESB\EsbFamily;
use App\Services\EsbConnectionInspector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EsbConnectionProbeController extends Controller
{
    public function store(string $family, EsbConnectionInspector $inspector): RedirectResponse
    {
        Gate::authorize(Permission::TestEsbConnections->value);

        $esbFamily = EsbFamily::tryFrom($family);

        abort_if($esbFamily === null, 404);

        $result = $inspector->probe($esbFamily);

        return back()->with('probe', [
            'family' => $esbFamily->value,
            ...$result,
        ]);
    }
}
