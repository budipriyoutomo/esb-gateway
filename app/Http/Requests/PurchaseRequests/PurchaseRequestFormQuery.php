<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * The branch and template picked on the create/edit screen, which decide
 * which lookups the screen asks the ESB for.
 */
class PurchaseRequestFormQuery extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManagePurchaseRequests);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            // A template belongs to a branch. On the create screen a template
            // alone means nothing; on the edit screen the branch is the
            // document's. No "sometimes": it would skip requiredIf when the
            // key is absent.
            'branchId' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(fn (): bool => $this->route('number') === null && $this->filled('templateId')),
            ],
            'templateId' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function branchId(): ?int
    {
        return $this->validatedInt('branchId');
    }

    public function templateId(): ?int
    {
        return $this->validatedInt('templateId');
    }
}
