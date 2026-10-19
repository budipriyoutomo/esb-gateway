<?php

namespace App\Http\Requests\DocumentTemplates;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;

/**
 * The product search typed on the create screen, which decides whether the
 * screen asks the ESB for product details ([ESB-016]).
 */
class DocumentTemplateFormQuery extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageDocumentTemplates);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'productSearch' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function productSearch(): ?string
    {
        $search = $this->validatedString('productSearch');

        return $search === null || trim($search) === '' ? null : trim($search);
    }
}
