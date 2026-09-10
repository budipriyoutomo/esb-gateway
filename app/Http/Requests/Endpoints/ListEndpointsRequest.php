<?php

namespace App\Http\Requests\Endpoints;

use App\Authorization\Permission;
use App\Integrations\ESB\EsbFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListEndpointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ViewEndpointCatalog->value) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'product' => ['sometimes', 'string', Rule::in(['core', 'oms'])],
            'group' => ['sometimes', 'string', 'max:255'],
            'method' => ['sometimes', 'string', Rule::in(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])],
            'family' => ['sometimes', 'string', Rule::in(array_column(EsbFamily::cases(), 'value'))],
        ];
    }

    /**
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
