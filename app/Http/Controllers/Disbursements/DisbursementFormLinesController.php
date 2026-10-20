<?php

namespace App\Http\Controllers\Disbursements;

use App\Domain\Canonical\DisbursementLine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disbursements\ReadDisbursementFormRequest;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\ESB\Exceptions\EsbException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Read the lines of the ERP's Excel disbursement form ([ESB-040]) and hand
 * them back to the form that sent it, flashed as "disbursementLines". The
 * ERP saves nothing, so there is no lock and no audit; the lines reach the
 * ERP only when the person saves the form.
 */
class DisbursementFormLinesController extends Controller
{
    /** How many of the ERP's per-row reasons a refusal shows. */
    private const MAX_REASONS = 5;

    public function store(ReadDisbursementFormRequest $request, DisbursementSource $disbursements): RedirectResponse
    {
        $filename = $request->safeFilename();

        try {
            $lines = $disbursements->readFormLines($request->contents(), $filename);
        } catch (EsbException $exception) {
            $reasons = array_map(
                static fn ($error): string => ($error->row === null ? '' : 'Row '.$error->row.': ').$error->message,
                array_slice($exception->fieldErrors, 0, self::MAX_REASONS),
            );

            $this->toast('error', trim(sprintf('The ERP could not read %s. %s %s', $filename, implode(' ', $reasons), $exception->safeMessage())));

            return back();
        }

        Inertia::flash('disbursementLines', array_map(
            static fn (DisbursementLine $line): array => $line->jsonSerialize(),
            $lines,
        ));

        $this->toast($lines === [] ? 'warning' : 'success', $lines === []
            ? sprintf('The ERP found no lines in %s.', $filename)
            : sprintf(count($lines) === 1 ? '%d line was read from %s. Check it before saving.' : '%d lines were read from %s. Check them before saving.', count($lines), $filename));

        return back();
    }
}
