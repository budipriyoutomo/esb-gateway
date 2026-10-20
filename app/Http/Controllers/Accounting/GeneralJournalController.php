<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ListGeneralJournalsRequest;
use App\Http\Requests\Accounting\ListJournalLinesRequest;
use App\Integrations\Contracts\GeneralJournalSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The general journal ([ESB-041]): the entries, and one journal's lines.
 * Authorization (esb.invoke) is on the form requests; the route holds the
 * head id to a positive integer.
 */
class GeneralJournalController extends Controller
{
    public function index(ListGeneralJournalsRequest $request, GeneralJournalSource $journal): Response
    {
        $page = $journal->listEntries($request->toQuery());

        return Inertia::render('general-journals/index', [
            'entries' => $page->entries,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $journalHeadId, ListJournalLinesRequest $request, GeneralJournalSource $journal): Response
    {
        $page = $journal->journalLines($journalHeadId, $request->page());

        return Inertia::render('general-journals/show', [
            'journalHeadId' => $journalHeadId,
            'lines' => $page->lines,
            'pagination' => $page->pagination,
        ]);
    }
}
