<?php

namespace App\Http\Requests\SalesPushes;

use App\Authorization\Permission;
use App\Models\SalesPush;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSalesPushesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ViewSalesPushes->value) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'salesNum' => ['sometimes', 'nullable', 'string', 'max:20'],
            'branchCode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(SalesPush::STATUSES)],
        ];
    }

    /**
     * The filters actually supplied, with blanks dropped.
     *
     * @return array<string, string>
     */
    public function filters(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return array_filter(
            array_map(static fn (mixed $value): string => is_scalar($value) ? (string) $value : '', $validated),
            static fn (string $value): bool => $value !== '',
        );
    }
}
