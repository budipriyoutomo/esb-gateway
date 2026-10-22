<?php

namespace App\Http\Controllers\Accounting;

use App\Domain\Canonical\MemorialJournalQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ListMemorialJournalsRequest;
use App\Integrations\Contracts\MemorialJournalSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Memorial journal list and detail ([ESB-043]). Authorization (esb.invoke)
 * is on the form request for the list and on the route for the detail.
 */
class MemorialJournalController extends Controller
{
    /**
     * The detail path shares its segment with the vendor's literal /access,
     * /export, /form-upload and /upload, so a number must contain a digit.
     */
    public const NUMBER = '/^(?=.*[0-9])[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/';

    public function index(ListMemorialJournalsRequest $request, MemorialJournalSource $journals): Response
    {
        $page = $journals->listJournals($request->toQuery());

        return Inertia::render('memorial-journals/index', [
            'journals' => $page->journals,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
            'statuses' => array_map(
                static fn (int $id, string $name): array => ['id' => $id, 'name' => $name],
                array_keys(MemorialJournalQuery::STATUSES),
                MemorialJournalQuery::STATUSES,
            ),
        ]);
    }

    public function show(string $number, MemorialJournalSource $journals): Response
    {
        self::assertUsableNumber($number);

        $journal = $journals->findJournal($number);

        abort_if($journal === null, 404);

        return Inertia::render('memorial-journals/show', [
            'journal' => $journal,
        ]);
    }

    public static function assertUsableNumber(string $number): void
    {
        abort_unless(preg_match(self::NUMBER, $number) === 1, 404);
    }
}
