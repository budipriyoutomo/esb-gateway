<?php

namespace App\Http\Controllers\Accounting;

use App\Actions\MemorialJournals\MemorialJournalForms;
use App\Domain\Canonical\MemorialJournal;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\SaveMemorialJournalRequest;
use App\Integrations\Contracts\MemorialJournalSource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit memorial journals ([ESB-045], S3). Authorization
 * (esb.invoke + memorial-journals.manage) is on the routes and the form
 * request.
 *
 * An update first reads the journal, outside the guarded write, for the
 * cost centers and projects its kept lines must send back -- so a failed
 * read is never mistaken for an unconfirmed update.
 */
class MemorialJournalFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * Vendor request field => form field, for the ERP's field errors
     * (ADR-021). Anything else lands on the form as a whole.
     */
    private const FIELD_MAP = [
        'memorialJournalDate' => 'date',
        'additionalInfo' => 'notes',
        'memorialJournalDetails' => 'lines',
    ];

    public function create(): Response
    {
        return Inertia::render('memorial-journals/form', ['journal' => null]);
    }

    public function store(SaveMemorialJournalRequest $request, MemorialJournalForms $forms): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Memorial journal',
            'of '.$draft->date,
            'memorial-journals',
            null,
            fn (): ?string => $forms->create($draft),
            self::FIELD_MAP,
        );
    }

    public function edit(string $number, MemorialJournalSource $journals): Response
    {
        return Inertia::render('memorial-journals/form', ['journal' => $this->current($number, $journals)]);
    }

    public function update(
        string $number,
        SaveMemorialJournalRequest $request,
        MemorialJournalSource $journals,
        MemorialJournalForms $forms,
    ): RedirectResponse {
        $draft = $request->toDraft();
        $current = $this->current($number, $journals);

        return $this->recordSaveOutcome(
            'Memorial journal',
            $number,
            'memorial-journals',
            $number,
            fn () => $forms->update($number, $draft, $current),
            self::FIELD_MAP,
        );
    }

    private function current(string $number, MemorialJournalSource $journals): MemorialJournal
    {
        MemorialJournalController::assertUsableNumber($number);

        $journal = $journals->findJournal($number);

        abort_if($journal === null, 404);

        return $journal;
    }
}
