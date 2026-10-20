<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\Support\CreatedRecord;
use App\Actions\Support\RecordOperation;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns the outcome of a create or update of a master data record
 * (TODO-modules.md §2, S3) into a toast and a redirect, or into errors on the
 * form.
 *
 * - success: the record -- or, after a create the ERP returned no id for,
 *   the list;
 * - a definite refusal: back to the form, with the ERP's field errors on the
 *   fields they name, so nothing typed is lost;
 * - an unconfirmed write: the list after a create, the record after an update,
 *   where the person can see what actually happened.
 *
 * Use from a Controller subclass (it calls toast()).
 */
trait HandlesRecordForms
{
    /**
     * @param  string  $label  Sentence-case kind, e.g. "Unit".
     * @param  string  $name  The record's name, as the person entered it.
     * @param  string  $routePrefix  e.g. "units" (needs .index and .show).
     * @param  int|string|null  $id  null for a create.
     * @param  Closure(): (CreatedRecord|int|string|null|void)  $write  A create returns the new id, if the ERP gave one.
     * @param  array<string, string>  $fieldMap  Vendor request field => form field.
     */
    protected function recordSaveOutcome(
        string $label,
        string $name,
        string $routePrefix,
        int|string|null $id,
        Closure $write,
        array $fieldMap,
    ): RedirectResponse {
        $operation = $id === null ? RecordOperation::Create : RecordOperation::Update;
        $landing = $id === null
            ? redirect()->route($routePrefix.'.index')
            : redirect()->route($routePrefix.'.show', $id);

        try {
            $newId = $write();
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', $id === null
                ? sprintf('Your previous new %s is still being created. Check the list before trying again.', Str::lower($label))
                : sprintf('Another change to %s is still being processed. Check it before trying again.', $name));

            return $landing;
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if (! $exception->mayHaveBeenApplied()) {
                throw $this->asRecordFormErrors($exception, $fieldMap);
            }

            $this->toast('warning', sprintf(
                'The ERP did not confirm the request to %s %s. It may or may not have been applied -- check it before trying again. (reference: %s)',
                $operation->value,
                $name,
                $exception->context->correlationId,
            ));

            return $landing;
        }

        if ($newId instanceof CreatedRecord && $newId->pendingApproval) {
            // Not in effect yet, and its own page may not show it: the list.
            $this->toast('success', sprintf('%s %s was created and is waiting for approval in the ERP.', $label, $name));

            return $landing;
        }

        $this->toast('success', sprintf('%s %s was %s.', $label, $name, $operation->pastTense()));

        $newId = $newId instanceof CreatedRecord ? $newId->id : $newId;

        if ($id === null && (is_int($newId) || is_string($newId))) {
            return redirect()->route($routePrefix.'.show', $newId);
        }

        return $landing;
    }

    /**
     * A definite refusal as validation errors on the form. The ERP's
     * per-field messages are meant for whoever filled the form; without any,
     * the safe summary is shown for the form as a whole.
     *
     * @param  array<string, string>  $fieldMap
     */
    private function asRecordFormErrors(EsbException $exception, array $fieldMap): ValidationException
    {
        $messages = [];

        foreach ($exception->fieldErrors as $fieldError) {
            $key = $fieldMap[$fieldError->field ?? ''] ?? 'form';
            $messages[$key][] = $fieldError->message;
        }

        if ($messages === []) {
            $messages['form'] = [$exception->safeMessage()];
        }

        // Inertia shares one message per field, so several for the same
        // field are joined rather than all but the first dropped.
        return ValidationException::withMessages(array_map(
            static fn (array $forField): string => implode(' ', array_unique($forField)),
            $messages,
        ));
    }
}
