<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ListLedgerEntriesRequest;
use App\Http\Requests\Accounting\ListLedgerSummaryRequest;
use App\Integrations\Contracts\GeneralLedgerSource;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use Closure;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The general ledger ([ESB-042]): an account's entries over a period, and
 * the period's summary. Nothing is read until the person gives what the
 * read needs. When the ERP refuses the read itself -- an account it does
 * not know, a closed range -- its reason is shown on the page, where the
 * filters can be changed; any other failure is the usual error page.
 */
class GeneralLedgerController extends Controller
{
    /** How many of the ERP's own reasons a refusal shows. */
    private const MAX_REASONS = 3;

    public function index(ListLedgerEntriesRequest $request, GeneralLedgerSource $ledger): Response
    {
        $query = $request->toQuery();
        [$page, $error] = $query === null ? [null, null] : $this->read(fn () => $ledger->entries($query));

        return Inertia::render('general-ledger/index', [
            'entries' => $page?->entries,
            'pagination' => $page?->pagination,
            'filters' => $request->validatedFilters(),
            'error' => $error,
        ]);
    }

    public function summary(ListLedgerSummaryRequest $request, GeneralLedgerSource $ledger): Response
    {
        $query = $request->toQuery();
        [$page, $error] = $query === null ? [null, null] : $this->read(fn () => $ledger->summary($query));

        return Inertia::render('general-ledger/summary', [
            'rows' => $page?->rows,
            'pagination' => $page?->pagination,
            'filters' => $request->validatedFilters(),
            'error' => $error,
        ]);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $read
     * @return array{T|null, string|null}
     *
     * @throws EsbException for anything but the ERP refusing the read
     */
    private function read(Closure $read): array
    {
        try {
            return [$read(), null];
        } catch (EsbValidationException|EsbBusinessRejectionException $exception) {
            $reasons = array_map(
                static fn ($error): string => $error->message,
                array_slice($exception->fieldErrors, 0, self::MAX_REASONS),
            );

            return [null, trim(implode(' ', $reasons).' '.$exception->safeMessage())];
        }
    }
}
