<?php

namespace App\Http\Requests\Units;

use App\Authorization\Permission;
use App\Domain\Canonical\UnitOfMeasureDraft;
use App\Http\Requests\BaseFormRequest;

/**
 * A unit to create, or to replace an existing one with ([ESB-030]).
 *
 * Mirrors the vendor schema of POST /units (PUT takes the same): name
 * required, max 50; metric required; notes max 100.
 * Field names are the gateway's own; the mapper translates them.
 */
class SaveUnitRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageUnits);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'metricId' => ['required', 'integer', 'min:1'],
            'taxId' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDraft(): UnitOfMeasureDraft
    {
        return new UnitOfMeasureDraft(
            name: (string) $this->validatedString('name'),
            metricId: (int) $this->validatedInt('metricId'),
            taxId: $this->validatedInt('taxId'),
            notes: $this->validatedString('notes'),
        );
    }
}
