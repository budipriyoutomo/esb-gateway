<?php

namespace App\Http\Requests\MenuCategories;

use App\Authorization\Permission;
use App\Domain\Canonical\MenuCategoryDraft;
use App\Domain\Canonical\MenuCategoryDraftDetail;
use App\Http\Requests\BaseFormRequest;

/**
 * A POS menu category to create or replace ([ESB-048]), checked against
 * the vendor's sizes before anything reaches the ESB: name and code up to
 * 50, the three accounts (account descriptions) required and up to 20, at
 * least one detail with a name, codes up to 50, max order >= 0 with four
 * decimals, colours #rrggbb or empty. Whether the accounts exist is the
 * ERP's to say.
 *
 * On an update, "page" is where the category was listed (a hint for the
 * read that finds it) and each detail's "id" the ERP detail it updates.
 */
class SaveMenuCategoryRequest extends BaseFormRequest
{
    public const NAME_MAX = 50;

    public const ACCOUNT_MAX = 20;

    public const MAX_DETAILS = 100;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageMenuCategories);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $color = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'page' => ['sometimes', 'integer', 'min:1', 'max:'.ListMenuCategoriesRequest::MAX_PAGE],
            'name' => ['required', 'string', 'max:'.self::NAME_MAX],
            'code' => ['nullable', 'string', 'max:'.self::NAME_MAX],
            'salesAccount' => ['required', 'string', 'max:'.self::ACCOUNT_MAX],
            'cogsAccount' => ['required', 'string', 'max:'.self::ACCOUNT_MAX],
            'discountAccount' => ['required', 'string', 'max:'.self::ACCOUNT_MAX],
            'description' => ['nullable', 'string', 'max:1000'],
            'buttonColor' => $color,
            'details' => ['required', 'array', 'min:1', 'max:'.self::MAX_DETAILS],
            'details.*' => ['array'],
            'details.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'details.*.name' => ['required', 'string', 'max:'.self::NAME_MAX],
            'details.*.code' => ['nullable', 'string', 'max:'.self::NAME_MAX],
            'details.*.description' => ['nullable', 'string', 'max:1000'],
            'details.*.maxOrderQty' => ['nullable', 'numeric', 'min:0', 'max:9999999999999999', 'decimal:0,4'],
            'details.*.buttonColor' => $color,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'details.required' => 'Add at least one category detail.',
            'details.min' => 'Add at least one category detail.',
            'buttonColor.regex' => 'Use a colour like #a4ff70, or leave it empty for the default.',
            'details.*.buttonColor.regex' => 'Use a colour like #a4ff70, or leave it empty for the default.',
            'salesAccount.max' => 'The ERP takes account descriptions of at most :max characters.',
            'cogsAccount.max' => 'The ERP takes account descriptions of at most :max characters.',
            'discountAccount.max' => 'The ERP takes account descriptions of at most :max characters.',
        ];
    }

    public function hintPage(): int
    {
        return $this->validatedInt('page') ?? 1;
    }

    public function toDraft(): MenuCategoryDraft
    {
        /** @var list<array<string, mixed>> $details */
        $details = $this->validated('details');

        return new MenuCategoryDraft(
            name: (string) $this->validatedString('name'),
            code: $this->validatedString('code') ?? '',
            salesAccount: (string) $this->validatedString('salesAccount'),
            cogsAccount: (string) $this->validatedString('cogsAccount'),
            discountAccount: (string) $this->validatedString('discountAccount'),
            description: $this->validatedString('description') ?? '',
            buttonColor: $this->validatedString('buttonColor') ?? '',
            details: array_map(
                static fn (array $detail): MenuCategoryDraftDetail => new MenuCategoryDraftDetail(
                    id: isset($detail['id']) ? (int) $detail['id'] : null,
                    name: (string) $detail['name'],
                    code: self::text($detail['code'] ?? null),
                    description: self::text($detail['description'] ?? null),
                    maxOrderQty: is_numeric($detail['maxOrderQty'] ?? null) ? $detail['maxOrderQty'] + 0 : null,
                    buttonColor: self::text($detail['buttonColor'] ?? null),
                ),
                $details,
            ),
        );
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
