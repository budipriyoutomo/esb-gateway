<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Authorization\Permission;
use App\Domain\Canonical\PurchaseRequestDraft;
use App\Domain\Canonical\PurchaseRequestDraftLine;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

/**
 * A purchase request to create, or to replace an existing one with.
 *
 * Mirrors the vendor's schema for POST /purchase/purchase-request (update
 * takes the same one) so a bad value is a field error here rather than an
 * opaque refusal from the ESB: required fields, the 1000/100-character limits,
 * at least one line, and `uniqueItems` on the lines. Field names are the
 * gateway's own; the mapper translates them.
 */
class SavePurchaseRequestRequest extends BaseFormRequest
{
    /**
     * A generous bound on lines per request. The vendor documents none; this
     * only stops a runaway payload.
     */
    public const MAX_LINES = 500;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManagePurchaseRequests);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'branchId' => ['required', 'integer', 'min:1'],
            // Assumed YYYY-MM-DD, the format the vendor's list filters take;
            // the create schema only says "string" (TBD).
            'date' => ['required', 'date_format:Y-m-d'],
            'requiredDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:date'],
            'templateId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            // No picker for these. An edit carries the document's values
            // back, because the update replaces the whole document.
            'costCenterId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'projectId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'isTemplate' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*.productDetailId' => ['required', 'integer', 'min:1'],
            'lines.*.requestProcessId' => ['required', 'integer', 'min:1'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'lines.*.notes' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requiredDate.after_or_equal' => 'The required date must be on or after the request date.',
            'lines.required' => 'Add at least one product.',
            'lines.*.qty.gt' => 'The quantity must be more than zero.',
            'lines.*.requestProcessId.required' => 'This line has no request process. Load it from a template again.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var mixed $lines */
                $lines = $this->input('lines');

                if (! is_array($lines)) {
                    return;
                }

                // The vendor declares the lines uniqueItems.
                $seen = [];

                foreach (array_values($lines) as $index => $line) {
                    if (! is_array($line)) {
                        continue;
                    }

                    $key = ($line['productDetailId'] ?? '').':'.($line['requestProcessId'] ?? '');

                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            "lines.{$index}.productDetailId",
                            sprintf('This product is already on line %d.', $seen[$key] + 1),
                        );

                        continue;
                    }

                    $seen[$key] = $index;
                }
            },
        ];
    }

    public function toDraft(): PurchaseRequestDraft
    {
        /** @var list<array<string, mixed>> $lines */
        $lines = array_values($this->validated('lines'));

        return new PurchaseRequestDraft(
            branchId: (int) $this->validated('branchId'),
            date: (string) $this->validated('date'),
            requiredDate: (string) $this->validated('requiredDate'),
            lines: array_map(
                static fn (array $line): PurchaseRequestDraftLine => new PurchaseRequestDraftLine(
                    productDetailId: (int) $line['productDetailId'],
                    requestProcessId: (int) $line['requestProcessId'],
                    qty: (float) $line['qty'],
                    notes: is_string($line['notes'] ?? null) && $line['notes'] !== '' ? $line['notes'] : null,
                ),
                $lines,
            ),
            templateId: $this->validatedInt('templateId'),
            notes: $this->validatedString('notes'),
            costCenterId: $this->validatedInt('costCenterId'),
            projectId: $this->validatedInt('projectId'),
            isTemplate: (bool) ($this->validated('isTemplate') ?? false),
        );
    }
}
