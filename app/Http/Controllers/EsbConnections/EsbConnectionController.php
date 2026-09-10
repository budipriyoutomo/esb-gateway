<?php

namespace App\Http\Controllers\EsbConnections;

use App\Actions\EsbConnections\RevertEsbConnection;
use App\Actions\EsbConnections\SaveEsbConnection;
use App\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEsbConnectionRequest;
use App\Integrations\ESB\EsbConnectionResolver;
use App\Integrations\ESB\EsbFamily;
use App\Services\EsbConnectionInspector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            'canManage' => Gate::allows(Permission::ManageEsbConnections->value),
        ]);
    }

    /**
     * Repoint one family, or replace its credential (ADR-018).
     *
     * Authorization is on the form request, so an unauthorized submission is
     * refused before validation ever reads the payload.
     */
    public function update(
        UpdateEsbConnectionRequest $request,
        string $family,
        SaveEsbConnection $save,
        EsbConnectionResolver $resolver,
    ): RedirectResponse {
        $connection = $save->handle(
            $this->family($family),
            $resolver->configuredEnvironment()->value,
            $request->connectionAttributes(),
        );

        return redirect()
            ->route('esb-connections.index')
            ->with('status', sprintf('The %s connection was updated.', $connection->family));
    }

    /**
     * Drop the override and hand the family back to configuration.
     */
    public function destroy(
        string $family,
        RevertEsbConnection $revert,
        EsbConnectionResolver $resolver,
    ): RedirectResponse {
        Gate::authorize(Permission::ManageEsbConnections->value);

        $reverted = $revert->handle($this->family($family), $resolver->configuredEnvironment()->value);

        return redirect()
            ->route('esb-connections.index')
            ->with('status', $reverted
                ? sprintf('The %s connection now follows configuration again.', $family)
                : sprintf('The %s connection already followed configuration.', $family));
    }

    /**
     * The family set is code, not data, so an unknown one is a 404 rather than
     * a validation error -- there is no such resource to edit.
     */
    private function family(string $family): EsbFamily
    {
        return EsbFamily::tryFrom($family) ?? throw new NotFoundHttpException(
            sprintf('Unknown ESB base URL family "%s".', $family),
        );
    }
}
