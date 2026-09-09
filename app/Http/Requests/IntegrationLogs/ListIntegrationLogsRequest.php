<?php

namespace App\Http\Requests\IntegrationLogs;

use App\Authorization\Permission;
use App\Integrations\ESB\EsbFamily;
use App\Support\IntegrationOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListIntegrationLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ViewIntegrationLogs->value) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'correlationId' => ['sometimes', 'string', 'max:64'],
            'operation' => ['sometimes', 'string', 'max:255'],
            'outcome' => ['sometimes', 'string', Rule::in(IntegrationOutcome::all())],
            'family' => ['sometimes', 'string', Rule::in(array_column(EsbFamily::cases(), 'value'))],
            'dateFrom' => ['sometimes', 'date_format:Y-m-d'],
            'dateTo' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dateFrom.date_format' => 'Use the format YYYY-MM-DD.',
            'dateTo.date_format' => 'Use the format YYYY-MM-DD.',
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
