<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Closure;
use Illuminate\Http\RedirectResponse;

/**
 * Turns the outcome of a decision or delete on an approvable document into a
 * toast and a redirect.
 *
 * A decision always lands back on the detail page, which re-reads the
 * document from the ESB -- the reconciliation step after an unconfirmed
 * write. An unknown document is left to the 404 handler.
 *
 * Use from a Controller subclass (it calls toast()).
 */
trait HandlesDocumentWrites
{
    /**
     * @param  string  $label  Sentence-case document name, e.g. "Purchase order".
     * @param  string  $routePrefix  Route name prefix, e.g. "purchase-orders".
     * @param  Closure(): void  $decide
     * @param  bool  $authorizeConsumesDocument  The provider removes the document
     *                                           when it is authorized (e.g. a temporary
     *                                           pricelist), so an authorization -- or an
     *                                           unconfirmed one -- lands on the list.
     */
    protected function decisionOutcome(
        string $label,
        string $routePrefix,
        string $number,
        ApprovalDecision $decision,
        Closure $decide,
        bool $authorizeConsumesDocument = false,
    ): RedirectResponse {
        $verb = $decision === ApprovalDecision::Authorize ? 'authorize' : 'reject';
        $consumed = false;

        try {
            $decide();

            $consumed = $authorizeConsumesDocument && $decision === ApprovalDecision::Authorize;

            $this->toast('success', sprintf(
                '%s %s was %s.',
                $label,
                $number,
                $decision === ApprovalDecision::Authorize ? 'authorized' : 'rejected',
            ));
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf(
                'Another change to %s is still being processed. Check its status before trying again.',
                $number,
            ));
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the request to %s %s. It may or may not have been applied -- check the status below before trying again. (reference: %s)',
                    $verb,
                    $number,
                    $exception->context->correlationId,
                ));
                $consumed = $authorizeConsumesDocument && $decision === ApprovalDecision::Authorize;
            } else {
                $this->toast('error', sprintf('Could not %s %s. %s', $verb, $number, $exception->safeMessage()));
            }
        }

        return $consumed
            ? redirect()->route($routePrefix.'.index')
            : redirect()->route($routePrefix.'.show', $number);
    }

    /**
     * @param  string  $label  Sentence-case document name, e.g. "Purchase order".
     * @param  string  $routePrefix  Route name prefix, e.g. "purchase-orders".
     * @param  Closure(): void  $delete
     */
    protected function deleteOutcome(string $label, string $routePrefix, string $number, Closure $delete): RedirectResponse
    {
        try {
            $delete();
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Another change to %s is still being processed. Check it before trying again.', $number));

            return redirect()->route($routePrefix.'.show', $number);
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm deleting %s. It may or may not be gone -- check the list before trying again. (reference: %s)',
                    $number,
                    $exception->context->correlationId,
                ));

                return redirect()->route($routePrefix.'.index');
            }

            $this->toast('error', sprintf('Could not delete %s. %s', $number, $exception->safeMessage()));

            return redirect()->route($routePrefix.'.show', $number);
        }

        $this->toast('success', sprintf('%s %s was deleted.', $label, $number));

        return redirect()->route($routePrefix.'.index');
    }

    /**
     * A one-shot state change other than a decision or delete, e.g.
     * finishing a goods transfer request. It always lands back on the detail
     * page, which re-reads the document.
     *
     * @param  string  $label  Sentence-case document name, e.g. "Goods transfer request".
     * @param  string  $routePrefix  Route name prefix, e.g. "goods-transfer-requests".
     * @param  string  $verb  e.g. "finish".
     * @param  string  $done  e.g. "finished".
     * @param  Closure(): void  $write
     * @param  string|null  $success  Replaces "<label> <number> was <done>."
     */
    protected function transitionOutcome(
        string $label,
        string $routePrefix,
        string $number,
        string $verb,
        string $done,
        Closure $write,
        ?string $success = null,
    ): RedirectResponse {
        try {
            $write();

            $this->toast('success', $success ?? sprintf('%s %s was %s.', $label, $number, $done));
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf(
                'Another change to %s is still being processed. Check its status before trying again.',
                $number,
            ));
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the request to %s %s. It may or may not have been applied -- check the status below before trying again. (reference: %s)',
                    $verb,
                    $number,
                    $exception->context->correlationId,
                ));
            } else {
                $this->toast('error', sprintf('Could not %s %s. %s', $verb, $number, $exception->safeMessage()));
            }
        }

        return redirect()->route($routePrefix.'.show', $number);
    }
}
