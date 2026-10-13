<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One Push Sales Data V2 transaction from a POS (ADR-022, [ESB-011]).
 *
 * Deliberately shallow. The gateway checks what it needs to store and order
 * the transaction -- its identity -- and that the structure is the vendor's.
 * The ESB validates the rest and lists every attribute it refuses, which the
 * POS reads back from GET /api/v1/sales-data/{id}. Re-implementing the
 * vendor's rules here would drift from them: its own documentation already
 * disagrees with itself on several types (docs/05-api-integration.md).
 */
class PushSalesDataRequest extends FormRequest
{
    /**
     * The api-client middleware already authenticated the caller.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $maxBytes = max(1, (int) config('sales_push.max_body_kb', 512)) * 1024;

        abort_if(strlen((string) $this->getContent()) > $maxBytes, 413, 'The request body is too large.');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'salesHead' => ['required', 'array'],

            // Lengths are the vendor's documented column sizes.
            'salesHead.salesNum' => ['required', 'string', 'max:20'],
            'salesHead.branchCode' => ['required', 'string', 'max:20'],
            // "Mandatory, but can contain empty string."
            'salesHead.salesDate' => ['sometimes', 'nullable', 'string', 'date_format:Y-m-d'],
            'salesHead.salesDateIn' => ['required', 'string', 'date_format:Y-m-d H:i:s'],

            'salesHead.menu' => ['present', 'array', 'list', 'max:500'],
            'salesHead.menu.*' => ['array'],
            'salesHead.menu.*.packages' => ['sometimes', 'array', 'list', 'max:100'],
            'salesHead.menu.*.extras' => ['sometimes', 'array', 'list', 'max:100'],

            'salesHead.payment' => ['present', 'array', 'list', 'max:50'],
            'salesHead.payment.*' => ['array'],
        ];
    }

    /**
     * The transaction exactly as sent. Not validated(), which would drop every
     * attribute the rules above do not name.
     *
     * @return array{salesHead: array<string, mixed>}
     */
    public function salesData(): array
    {
        /** @var array<string, mixed> $head */
        $head = $this->json('salesHead');

        return ['salesHead' => $head];
    }
}
