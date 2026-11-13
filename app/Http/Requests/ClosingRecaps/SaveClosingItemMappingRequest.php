<?php

namespace App\Http\Requests\ClosingRecaps;

use App\Authorization\Permission;
use App\Models\ClosingRecapItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A closing menu or POS product -> ERP menu (for sales) and, for a closing
 * menu, a bill of material (for the item journals) (ADR-032). At least one
 * of the two is named; a POS product never has a BOM.
 */
class SaveClosingItemMappingRequest extends FormRequest
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
        $posOnly = $this->route('source') === ClosingRecapItem::SOURCE_POS_ONLY;

        return [
            'esbMenuId' => ['nullable', 'integer', 'min:1'],
            'esbMenuCode' => ['nullable', 'required_with:esbMenuId', 'string', 'max:50'],
            'esbMenuName' => ['nullable', 'required_with:esbMenuId', 'string', 'max:255'],
            'bomId' => $posOnly ? ['prohibited'] : ['nullable', 'integer', 'min:1'],
            'bomName' => $posOnly ? ['prohibited'] : ['nullable', 'required_with:bomId', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bomId.prohibited' => 'A POS product has no bill of material: only closing menus go to the item journals.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('esbMenuId') === null && $this->input('bomId') === null) {
                $validator->errors()->add('esbMenuId', 'Choose an ERP menu, a bill of material, or both.');
            }
        }];
    }

    /**
     * @return array{esbMenuId: int|null, esbMenuCode: string|null, esbMenuName: string|null, bomId: int|null, bomName: string|null}
     */
    public function values(): array
    {
        $text = fn (string $key): ?string => $this->filled($key) ? trim($this->string($key)->value()) : null;

        return [
            'esbMenuId' => $this->filled('esbMenuId') ? $this->integer('esbMenuId') : null,
            'esbMenuCode' => $text('esbMenuCode'),
            'esbMenuName' => $text('esbMenuName'),
            'bomId' => $this->filled('bomId') ? $this->integer('bomId') : null,
            'bomName' => $text('bomName'),
        ];
    }
}
