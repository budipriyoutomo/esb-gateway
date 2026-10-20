<?php

namespace App\Http\Requests\Inventory;

use App\Authorization\InventoryPermissions;
use App\Authorization\Permission;
use App\Domain\Canonical\InventoryDraft;
use App\Domain\Canonical\InventoryDraftLine;
use App\Domain\Canonical\InventoryKind;
use App\Http\Concerns\ResolvesInventoryKind;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Carbon;

/**
 * An inventory document to create, or to replace one with ([ESB-035]).
 *
 * Mirrors the vendor schemas, per kind:
 *
 * - goods transfer request: date, origin branch, destination branch required,
 *   origin location optional; each product detail once, qty > 0, requested
 *   qty >= 0; notes up to 1000;
 * - item journal: date (not in the future), branch and location required;
 *   each product detail once, a qty, optional purpose and cost (HPP);
 *   notes up to 1000;
 * - simple transfer: date, origin and destination location required;
 *   qty > 0 with at most four decimals (decimal(20,4)). The branch only
 *   narrows the form's pickers. The vendor gives no notes length; the same
 *   1000 is applied.
 *
 * The gateway bounds the number of lines (the vendor documents none).
 */
class SaveInventoryDocumentRequest extends BaseFormRequest
{
    use ResolvesInventoryKind;

    public const MAX_LINES = 500;

    public const NOTES_MAX = 1000;

    /** A bound on quantities and costs; the vendor's columns are decimal(20,4). */
    private const AMOUNT_MAX = 9999999999999999;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, InventoryPermissions::manage($this->kind()));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $common = [
            'date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:'.self::NOTES_MAX],
            'lines' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*' => ['array'],
            'lines.*.productDetailId' => ['required', 'integer', 'min:1', 'distinct'],
        ];
        $positiveQty = ['required', 'numeric', 'gt:0', 'max:'.self::AMOUNT_MAX, 'decimal:0,4'];

        return match ($this->kind()) {
            InventoryKind::GoodsTransferRequests => [
                ...$common,
                'branchId' => ['required', 'integer', 'min:1'],
                'locationId' => ['nullable', 'integer', 'min:1'],
                'destinationBranchId' => ['required', 'integer', 'min:1'],
                'lines.*.qty' => $positiveQty,
                'lines.*.requestQty' => ['nullable', 'numeric', 'min:0', 'max:'.self::AMOUNT_MAX, 'decimal:0,4'],
            ],
            InventoryKind::ItemJournals => [
                ...$common,
                // "Must be after the active stock period and not in the
                // future": the period is the ERP's to check.
                'date' => [...$common['date'], 'before_or_equal:'.Carbon::now('Asia/Jakarta')->toDateString()],
                'branchId' => ['required', 'integer', 'min:1'],
                'locationId' => ['required', 'integer', 'min:1'],
                // An adjustment can take stock out, so a qty may be negative.
                'lines.*.qty' => ['required', 'numeric', 'min:-'.self::AMOUNT_MAX, 'max:'.self::AMOUNT_MAX, 'decimal:0,4'],
                'lines.*.purposeId' => ['nullable', 'integer', 'min:1'],
                'lines.*.cost' => ['nullable', 'numeric', 'min:0', 'max:'.self::AMOUNT_MAX, 'decimal:0,4'],
            ],
            InventoryKind::SimpleTransfers => [
                ...$common,
                // The vendor does not ask for each product detail once.
                'lines.*.productDetailId' => ['required', 'integer', 'min:1'],
                'branchId' => ['nullable', 'integer', 'min:1'],
                'locationId' => ['required', 'integer', 'min:1'],
                'destinationLocationId' => ['required', 'integer', 'min:1'],
                'lines.*.qty' => $positiveQty,
            ],
            default => abort(404),
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least one product.',
            'lines.min' => 'Add at least one product.',
            'lines.*.productDetailId.distinct' => 'This product is already on another line.',
            'date.before_or_equal' => 'The date cannot be in the future.',
        ];
    }

    public function kind(): InventoryKind
    {
        return $this->inventoryKind($this);
    }

    public function toDraft(): InventoryDraft
    {
        /** @var list<array<string, mixed>> $lines */
        $lines = $this->validated('lines');

        return new InventoryDraft(
            date: (string) $this->validatedString('date'),
            branchId: $this->kind() === InventoryKind::SimpleTransfers ? null : $this->validatedInt('branchId'),
            locationId: $this->validatedInt('locationId'),
            destinationBranchId: $this->validatedInt('destinationBranchId'),
            destinationLocationId: $this->validatedInt('destinationLocationId'),
            notes: $this->validatedString('notes'),
            lines: array_map(
                static fn (array $line): InventoryDraftLine => new InventoryDraftLine(
                    productDetailId: (int) $line['productDetailId'],
                    qty: self::number($line['qty'] ?? null) ?? 0,
                    requestQty: self::number($line['requestQty'] ?? null),
                    purposeId: is_numeric($line['purposeId'] ?? null) ? (int) $line['purposeId'] : null,
                    cost: self::number($line['cost'] ?? null),
                ),
                $lines,
            ),
        );
    }

    private static function number(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }
}
