<?php

namespace App\Http\Controllers\ClosingRecaps;

use App\Actions\ClosingRecaps\ClosingRecapNotReady;
use App\Actions\ClosingRecaps\PrepareClosingItemJournals;
use App\Actions\ClosingRecaps\PrepareClosingSalesPush;
use App\Actions\ClosingRecaps\ResolveClosingItemJournal;
use App\Actions\ClosingRecaps\SendClosingItemJournals;
use App\Actions\ClosingRecaps\SendClosingSalesPush;
use App\Http\Controllers\Controller;
use App\Models\ClosingItemJournal;
use App\Models\ClosingRecap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Prepare a closing recap's item journals, send them to the ERP, and settle
 * one whose outcome was unknown (ADR-032, P28). Routes need esb.invoke and
 * closing-recaps.process.
 */
class ClosingRecapProcessController extends Controller
{
    public function prepare(Request $request, ClosingRecap $closingRecap, PrepareClosingItemJournals $prepare): RedirectResponse
    {
        try {
            $journals = $prepare->handle($closingRecap, $request->user()?->id);
        } catch (ClosingRecapNotReady $exception) {
            return back()->withErrors(['recap' => $exception->getMessage()]);
        }

        $this->toast('success', $journals === []
            ? 'Prepared: no waste, adjustment or compensation to journal.'
            : sprintf('Prepared %d item %s. Check them, then send.', count($journals), count($journals) === 1 ? 'journal' : 'journals'));

        return back();
    }

    public function send(ClosingRecap $closingRecap, SendClosingItemJournals $send): RedirectResponse
    {
        try {
            $outcomes = $send->handle($closingRecap);
        } catch (ClosingRecapNotReady $exception) {
            return back()->withErrors(['recap' => $exception->getMessage()]);
        }

        $created = $outcomes[ClosingItemJournal::STATUS_CREATED] ?? 0;
        $problems = array_sum($outcomes) - $created - ($outcomes[ClosingItemJournal::STATUS_DELETED] ?? 0);

        if ($problems === 0) {
            $this->toast('success', sprintf('Sent: %d item %s created in the ERP.', $created, $created === 1 ? 'journal' : 'journals'));
        } else {
            $this->toast('error', 'Not everything reached the ERP. Check the item journals below.');
        }

        return back();
    }

    public function prepareSales(Request $request, ClosingRecap $closingRecap, PrepareClosingSalesPush $prepare): RedirectResponse
    {
        try {
            $draft = $prepare->handle($closingRecap, $request->user()?->id);
        } catch (ClosingRecapNotReady $exception) {
            return back()->withErrors(['sales' => $exception->getMessage()]);
        }

        $this->toast('success', sprintf('Prepared sales %s. Check it, then send.', $draft->sales_num));

        return back();
    }

    public function sendSales(ClosingRecap $closingRecap, SendClosingSalesPush $send): RedirectResponse
    {
        try {
            $push = $send->handle($closingRecap);
        } catch (ClosingRecapNotReady $exception) {
            return back()->withErrors(['sales' => $exception->getMessage()]);
        }

        $this->toast('success', sprintf('Sales %s queued for the ERP.', $push->sales_num));

        return back();
    }

    public function resolve(Request $request, ClosingItemJournal $closingItemJournal, ResolveClosingItemJournal $resolve): RedirectResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'string', Rule::in(ResolveClosingItemJournal::OUTCOMES)],
            'itemJournalNum' => ['nullable', 'string', 'max:50'],
        ]);

        $resolve->handle(
            $closingItemJournal,
            $validated['outcome'],
            isset($validated['itemJournalNum']) ? trim($validated['itemJournalNum']) : null,
        );

        $this->toast('success', 'Item journal settled.');

        return back();
    }
}
