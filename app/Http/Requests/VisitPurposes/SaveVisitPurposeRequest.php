<?php

namespace App\Http\Requests\VisitPurposes;

use App\Authorization\Permission;
use App\Models\VisitPurpose;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A visit purpose kept in the gateway ([ESB-052]). Changing the list takes
 * closing-mappings.manage: it is part of how closing recaps meet the ERP.
 */
class SaveVisitPurposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageClosingMappings->value) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            // As long as the closing-totals API accepts a visitPurposeName.
            'name' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('name')) {
                    return;
                }

                $current = $this->route('visitPurpose');
                $currentId = $current instanceof VisitPurpose ? $current->id : null;
                $taken = VisitPurpose::query()
                    ->where('name_key', VisitPurpose::keyOf($this->string('name')->value()))
                    ->when($currentId !== null, fn ($query) => $query->whereKeyNot($currentId))
                    ->exists();

                if ($taken) {
                    $validator->errors()->add('name', 'This visit purpose is already on the list.');
                }
            },
        ];
    }

    /**
     * @return array{name: string, notes: string|null, active: bool}
     */
    public function values(): array
    {
        $notes = trim($this->string('notes')->value());

        return [
            'name' => $this->string('name')->value(),
            'notes' => $notes === '' ? null : $notes,
            'active' => $this->boolean('active'),
        ];
    }
}
