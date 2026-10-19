<?php

namespace App\Http\Requests\DocumentTemplates;

use App\Authorization\Permission;
use App\Domain\Canonical\DocumentTemplateQuery;
use App\Domain\Canonical\DocumentTemplateType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the document template list filters before they reach the ESB
 * ([ESB-016]).
 */
class ListDocumentTemplatesRequest extends BaseFormRequest
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
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.DocumentTemplateQuery::MAX_LIMIT],
            'requestTemplateName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'requestTemplateTypeID' => ['sometimes', 'nullable', 'integer', Rule::enum(DocumentTemplateType::class)],
            'flagActive' => ['sometimes', 'nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.max' => 'The ERP service accepts at most :max records per page.',
        ];
    }

    public function toQuery(): DocumentTemplateQuery
    {
        $typeId = $this->validatedInt('requestTemplateTypeID');
        $flagActive = $this->validatedInt('flagActive');

        return new DocumentTemplateQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? DocumentTemplateQuery::DEFAULT_LIMIT,
            name: $this->validatedString('requestTemplateName'),
            type: $typeId === null ? null : DocumentTemplateType::tryFrom($typeId),
            activeOnly: $flagActive === null ? null : $flagActive === 1,
        );
    }

    /**
     * The filters echoed back to the page, in the request's own spelling.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $query = $this->toQuery();

        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'requestTemplateName' => $query->name,
            'requestTemplateTypeID' => $query->type?->value,
            'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
