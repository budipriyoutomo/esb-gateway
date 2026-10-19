<?php

namespace App\Http\Requests\DocumentTemplates;

use App\Authorization\Permission;
use App\Domain\Canonical\DocumentTemplateDraft;
use App\Domain\Canonical\DocumentTemplateDraftLine;
use App\Domain\Canonical\DocumentTemplateRules;
use App\Domain\Canonical\DocumentTemplateType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * A document template to create ([ESB-016]).
 *
 * Mirrors the vendor's schema for POST /document-template so a bad value is a
 * field error here rather than an opaque refusal from the ESB: required name,
 * type 1-3, at least one branch, at least one line, unique productDetailID,
 * and "no emoji" on both names. Field names are the gateway's own; the mapper
 * translates them.
 */
class SaveDocumentTemplateRequest extends BaseFormRequest
{
    /**
     * A generous bound on lines per template. The vendor documents none; this
     * only stops a runaway payload.
     */
    public const MAX_LINES = 500;

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
            'name' => ['required', 'string', 'max:'.DocumentTemplateRules::MAX_NAME_LENGTH, 'not_regex:'.DocumentTemplateRules::EMOJI],
            'type' => ['required', 'integer', Rule::enum(DocumentTemplateType::class)],
            'active' => ['sometimes', 'boolean'],
            'branchIds' => ['required', 'array', 'min:1'],
            'branchIds.*' => ['required', 'integer', 'min:1', 'distinct'],
            'lines' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*.productDetailId' => ['required', 'integer', 'min:1', 'distinct'],
            'lines.*.name' => ['required', 'string', 'max:'.DocumentTemplateRules::MAX_NAME_LENGTH, 'not_regex:'.DocumentTemplateRules::EMOJI],
            'lines.*.purposeId' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.not_regex' => 'The ERP does not accept emoji in the name.',
            'branchIds.required' => 'Choose at least one branch.',
            'branchIds.*.distinct' => 'This branch is chosen twice.',
            'lines.required' => 'Add at least one product.',
            'lines.*.productDetailId.distinct' => 'This product is already on the template.',
            'lines.*.name.required' => 'Give this line a name.',
            'lines.*.name.not_regex' => 'The ERP does not accept emoji in the name.',
        ];
    }

    public function toDraft(): DocumentTemplateDraft
    {
        /** @var list<int|string> $branchIds */
        $branchIds = array_values($this->validated('branchIds'));

        /** @var list<array<string, mixed>> $lines */
        $lines = array_values($this->validated('lines'));

        return new DocumentTemplateDraft(
            name: (string) $this->validated('name'),
            type: DocumentTemplateType::from((int) $this->validated('type')),
            active: (bool) ($this->validated('active') ?? true),
            branchIds: array_map(intval(...), $branchIds),
            lines: array_map(
                static fn (array $line): DocumentTemplateDraftLine => new DocumentTemplateDraftLine(
                    productDetailId: (int) $line['productDetailId'],
                    name: (string) $line['name'],
                    purposeId: isset($line['purposeId']) ? (int) $line['purposeId'] : null,
                ),
                $lines,
            ),
        );
    }
}
