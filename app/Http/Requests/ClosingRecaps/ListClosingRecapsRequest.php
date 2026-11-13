<?php

namespace App\Http\Requests\ClosingRecaps;

use App\Authorization\Permission;
use App\Models\ClosingRecap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListClosingRecapsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ViewClosingRecaps->value) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'outletCode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'productionDate' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(ClosingRecap::STATUSES)],
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
            array_map(static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '', $validated),
            static fn (string $value): bool => $value !== '',
        );
    }
}
