<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;

/**
 * Which page of a general journal's lines to read ([ESB-041]).
 */
class ListJournalLinesRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1']];
    }

    public function page(): int
    {
        return $this->validatedInt('page') ?? 1;
    }
}
