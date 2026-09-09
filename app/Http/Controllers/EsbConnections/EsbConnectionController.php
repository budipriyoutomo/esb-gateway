<?php

namespace App\Http\Controllers\EsbConnections;

use App\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Services\EsbConnectionInspector;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EsbConnectionController extends Controller
{
    public function index(EsbConnectionInspector $inspector): Response
    {
        Gate::authorize(Permission::ViewEsbConnections->value);

        return Inertia::render('esb-connections/index', [
            'connections' => $inspector->connections(),
            'environment' => config('esb.environment'),
            // The mechanism is useful to an operator. The credential itself
            // never leaves the server (docs/10-security.md).
            'authMechanism' => config('esb.auth.mechanism'),
        ]);
    }
}
