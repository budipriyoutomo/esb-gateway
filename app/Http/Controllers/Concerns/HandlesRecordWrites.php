<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\Support\RecordInUse;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Turns the outcome of a delete / deactivate / restore on a master data
 * record into a toast and a redirect. A deleted record goes back to the
 * list; everything else lands on the record, which re-reads it from the
 * ERP. The ERP's own reasons for a refusal (ADR-021) are shown.
 *
 * Use from a Controller subclass (it calls toast()).
 */
trait HandlesRecordWrites
{
    /** Reasons shown in a toast at most. */
    private int $maxReasons = 3;

    /**
     * The name the page sent for the toast, or "#id". Display only: it is
     * never sent upstream or audited.
     */
    protected function recordName(Request $request, int|string $id): string
    {
        $name = trim((string) $request->input('name', ''));

        return $name === '' ? '#'.$id : Str::limit($name, 120);
    }

    /**
     * @param  string  $label  Sentence-case kind, e.g. "Unit".
     * @param  string  $name  The record's name, as the person knows it.
     * @param  string  $routePrefix  e.g. "units" (needs .index and .show).
     * @param  Closure(): void  $write
     */
    protected function recordWriteOutcome(
        string $label,
        string $name,
        string $routePrefix,
        int|string $id,
        RecordOperation $operation,
        Closure $write,
    ): RedirectResponse {
        $show = redirect()->route($routePrefix.'.show', $id);
        $index = redirect()->route($routePrefix.'.index');

        try {
            $write();
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Another change to %s is still being processed. Check it before trying again.', $name));

            return $show;
        } catch (RecordInUse) {
            $this->toast('error', sprintf('%s is still in use, so the ERP will not delete it.', $name));

            return $show;
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the request to %s %s. It may or may not have been applied -- check it before trying again. (reference: %s)',
                    $operation->value,
                    $name,
                    $exception->context->correlationId,
                ));

                return $operation === RecordOperation::Delete ? $index : $show;
            }

            $reasons = array_map(
                static fn ($error): string => $error->message,
                array_slice($exception->fieldErrors, 0, $this->maxReasons),
            );

            $this->toast('error', trim(sprintf(
                'Could not %s %s. %s %s',
                $operation->value,
                $name,
                $exception->safeMessage(),
                implode(' ', $reasons),
            )));

            return $show;
        }

        $this->toast('success', sprintf('%s %s was %s.', $label, $name, $operation->pastTense()));

        return $operation === RecordOperation::Delete ? $index : $show;
    }
}
