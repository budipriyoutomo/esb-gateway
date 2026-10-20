<?php

namespace App\Http\Controllers\Units;

use App\Actions\Units\UnitWrites;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Units\SaveUnitRequest;
use App\Integrations\Contracts\UnitOfMeasureSource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit units ([ESB-030], S3). Authorization
 * (esb.invoke + the module's manage permission) is on the routes and the
 * form request; the outcomes are HandlesRecordForms'.
 */
class UnitFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * The vendor's request field names, and the form fields they belong to.
     */
    private const FIELD_MAP = [
        'uomName' => 'name',
        'metricID' => 'metricId',
        'refTaxID' => 'taxId',
        'notes' => 'notes',
    ];

    public function create(): Response
    {
        return Inertia::render('units/form', ['unit' => null]);
    }

    public function edit(int $uomId, UnitOfMeasureSource $source): Response
    {
        $record = $source->findUnit($uomId);

        abort_if($record === null, 404);

        return Inertia::render('units/form', ['unit' => $record]);
    }

    public function store(SaveUnitRequest $request, UnitWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome('Unit', $draft->name, 'units', null, fn () => $writes->create($draft), self::FIELD_MAP);
    }

    public function update(int $uomId, SaveUnitRequest $request, UnitWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Unit',
            $draft->name,
            'units',
            $uomId,
            fn () => $writes->update($uomId, $draft),
            self::FIELD_MAP,
        );
    }
}
