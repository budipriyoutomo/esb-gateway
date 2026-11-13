<?php

namespace App\Http\Requests\ClosingRecaps;

use App\Authorization\Permission;
use App\Models\ClosingPurposeMapping;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A stock kind -> item journal purpose and the sign of its quantity (ADR-032).
 */
class SaveClosingPurposeMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageClosingMappings->value) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'purposeId' => ['required', 'integer', 'min:1'],
            'purposeName' => ['required', 'string', 'max:255'],
            'direction' => ['required', 'integer', Rule::in(ClosingPurposeMapping::DIRECTIONS)],
        ];
    }

    /**
     * @return array{purposeId: int, purposeName: string, direction: int}
     */
    public function values(): array
    {
        return [
            'purposeId' => $this->integer('purposeId'),
            'purposeName' => trim($this->string('purposeName')->value()),
            'direction' => $this->integer('direction'),
        ];
    }
}
