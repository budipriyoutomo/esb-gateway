<?php

namespace App\Http\Requests\ClosingRecaps;

use App\Authorization\Permission;
use App\Models\ClosingOutletMapping;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Outlet -> ERP branch and location, as picked from the lookups (ADR-032).
 */
class SaveClosingOutletMappingRequest extends FormRequest
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
            'branchId' => ['required', 'integer', 'min:1'],
            'branchCode' => ['required', 'string', 'max:'.ClosingOutletMapping::MAX_BRANCH_CODE],
            'branchName' => ['required', 'string', 'max:255'],
            'locationId' => ['required', 'integer', 'min:1'],
            'locationName' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branchCode.max' => 'The branch code must be at most :max characters: the sales number is CL + branch code + date, at most 20.',
        ];
    }

    /**
     * @return array{branchId: int, branchCode: string, branchName: string, locationId: int, locationName: string}
     */
    public function values(): array
    {
        return [
            'branchId' => $this->integer('branchId'),
            'branchCode' => trim($this->string('branchCode')->value()),
            'branchName' => trim($this->string('branchName')->value()),
            'locationId' => $this->integer('locationId'),
            'locationName' => trim($this->string('locationName')->value()),
        ];
    }
}
