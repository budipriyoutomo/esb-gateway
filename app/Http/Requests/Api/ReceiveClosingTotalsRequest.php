<?php

namespace App\Http\Requests\Api;

use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * One closing recap from sync-api Maharasa (ADR-032), validated against the
 * contract in docs/spesifikasi-penerima-kirim-closing.md §3-§4.
 *
 * Unlike the sales push (ADR-022), the gateway owns this shape: it is turned
 * into ESB documents here later, so every field is checked.
 */
class ReceiveClosingTotalsRequest extends FormRequest
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
        $maxBytes = max(1, (int) config('closing_totals.max_body_kb', 512)) * 1024;

        abort_if(strlen((string) $this->getContent()) > $maxBytes, 413, 'The request body is too large.');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $quantity = ['present', 'numeric', 'decimal:0,6', 'between:-999999999999,999999999999'];
        $closing = 'items.*.source,'.ClosingRecapItem::SOURCE_CLOSING;
        $posOnly = 'items.*.source,'.ClosingRecapItem::SOURCE_POS_ONLY;

        return [
            'messageId' => ['required', 'string', 'uuid'],
            'sentAt' => ['required', 'string', 'date_format:Y-m-d\TH:i:s\Z'],
            'sequence' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'outlet' => ['required', 'array'],
            'outlet.code' => ['required', 'string', 'max:20'],
            'productionDate' => ['required', 'string', 'date_format:Y-m-d'],

            'items' => ['required', 'array', 'list', 'min:1', 'max:'.max(1, (int) config('closing_totals.max_items', 2000))],
            'items.*' => ['array'],
            'items.*.source' => ['required', 'string', Rule::in(ClosingRecapItem::SOURCES)],

            'items.*.menuId' => ['nullable', "required_if:{$closing}", 'string', 'uuid'],
            'items.*.menuCode' => ['nullable', 'string', 'max:50'],
            'items.*.menuName' => ['nullable', 'string', 'max:255'],

            'items.*.productId' => ['nullable', "required_if:{$posOnly}", 'string', 'max:50'],
            'items.*.productCode' => ['nullable', "required_if:{$posOnly}", 'string', 'max:50'],
            'items.*.productName' => ['nullable', 'string', 'max:255'],

            'items.*.colorplateSold' => $quantity,
            'items.*.posQty' => $quantity,
            'items.*.totalSold' => $quantity,
            'items.*.waste' => $quantity,
            'items.*.adjustment' => $quantity,
            'items.*.compensation' => $quantity,

            // Contract revision 3 (TBD, docs/balasan-kontrak-kirim-closing.md
            // §2.1): optional, so revision 2 recaps stay valid. Sales and
            // payments come together; the gateway forwards the figures as
            // given and does not recompute tax.
            ...$this->salesRules(),
        ];
    }

    /** Numbers of one revision 3 price line. */
    public const PRICE_FIELDS = [
        'qty', 'originalPrice', 'price', 'discount', 'discountValue', 'otherTax',
        'otherTaxValue', 'vat', 'vatValue', 'otherVat', 'otherVatValue', 'otherTaxOnVat', 'total',
    ];

    /**
     * @return array<string, mixed>
     */
    private function salesRules(): array
    {
        $amount = ['required', 'numeric', 'between:-999999999999,999999999999'];

        $rules = [
            'sales' => ['nullable', 'array', 'required_with:payments'],
            'sales.visitPurposeName' => ['required_with:sales', 'string', 'max:100'],
            'payments' => ['nullable', 'array', 'list', 'min:1', 'max:50', 'required_with:sales'],
            'payments.*' => ['array'],
            'payments.*.paymentMethod' => ['required', 'string', 'max:100'],
            'payments.*.coaNo' => ['present', 'nullable', 'string', 'max:50'],
            'payments.*.paymentAmount' => $amount,
            'payments.*.fullPaymentAmount' => $amount,
            'payments.*.merchantDiscountRate' => ['present', 'numeric'],
            'payments.*.notes' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.prices' => ['sometimes', 'nullable', 'array', 'list', 'max:50'],
            'items.*.prices.*' => ['array'],
        ];

        foreach (ClosingRecap::SALES_TOTALS as $field) {
            $rules["sales.{$field}"] = ['required_with:sales', 'numeric', 'between:-999999999999,999999999999'];
        }

        foreach (self::PRICE_FIELDS as $field) {
            $rules["items.*.prices.*.{$field}"] = $amount;
        }

        return $rules;
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->checkIdempotencyKey($validator),
            fn (Validator $validator) => $this->checkItems($validator),
            fn (Validator $validator) => $this->checkPrices($validator),
        ];
    }

    /**
     * The sender repeats the messageId in this header.
     */
    private function checkIdempotencyKey(Validator $validator): void
    {
        $key = trim((string) $this->header('Idempotency-Key'));

        if ($key === '' || strcasecmp($key, (string) $this->input('messageId')) !== 0) {
            $validator->errors()->add('Idempotency-Key', 'The Idempotency-Key header must equal messageId.');
        }
    }

    /**
     * Each item key appears once, and totalSold is colorplateSold + posQty.
     */
    private function checkItems(Validator $validator): void
    {
        $items = $this->input('items');

        if (! is_array($items)) {
            return;
        }

        $seen = ['menuId' => [], 'productId' => [], 'productCode' => []];

        foreach ($items as $i => $item) {
            if (! is_array($item)) {
                continue;
            }

            $keys = ($item['source'] ?? null) === ClosingRecapItem::SOURCE_CLOSING
                ? ['menuId']
                : ['productId', 'productCode'];

            foreach ($keys as $field) {
                $value = $item[$field] ?? null;

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $normalized = mb_strtoupper(trim($value));

                if (isset($seen[$field][$normalized])) {
                    $validator->errors()->add("items.{$i}.{$field}", "The {$field} appears more than once in this recap.");
                }

                $seen[$field][$normalized] = true;
            }

            $this->checkTotalSold($validator, $i, $item);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function checkTotalSold(Validator $validator, int|string $i, array $item): void
    {
        $parts = [$item['colorplateSold'] ?? null, $item['posQty'] ?? null, $item['totalSold'] ?? null];

        foreach ($parts as $part) {
            if (! is_int($part) && ! is_float($part) && ! (is_string($part) && is_numeric($part))) {
                return; // The field rules already name it.
            }
        }

        try {
            [$colorplate, $pos, $total] = array_map(fn (int|float|string $v) => BigDecimal::of((string) $v), $parts);
        } catch (\Throwable) {
            return;
        }

        if (! $colorplate->plus($pos)->isEqualTo($total)) {
            $validator->errors()->add("items.{$i}.totalSold", 'The totalSold must equal colorplateSold + posQty.');
        }
    }

    /**
     * With sales data, every item sold has price lines; any price lines add
     * up to the item's totalSold.
     */
    private function checkPrices(Validator $validator): void
    {
        $items = $this->input('items');

        if (! is_array($items)) {
            return;
        }

        $hasSales = is_array($this->input('sales'));

        foreach ($items as $i => $item) {
            if (! is_array($item) || ! is_numeric($item['totalSold'] ?? null)) {
                continue;
            }

            $total = BigDecimal::of((string) $item['totalSold']);
            $prices = $item['prices'] ?? null;

            if (! is_array($prices)) {
                if ($hasSales && ! $total->isZero()) {
                    $validator->errors()->add("items.{$i}.prices", 'An item sold needs its price lines when sales data is sent.');
                }

                continue;
            }

            $sum = BigDecimal::zero();

            foreach ($prices as $price) {
                if (! is_array($price) || ! is_numeric($price['qty'] ?? null)) {
                    return; // The field rules already name it.
                }

                $sum = $sum->plus(BigDecimal::of((string) $price['qty']));
            }

            if (! $sum->isEqualTo($total)) {
                $validator->errors()->add("items.{$i}.prices", 'The price line quantities must add up to totalSold.');
            }
        }
    }

    /**
     * The outlet code as the gateway matches it: trimmed and upper-cased.
     */
    public function outletCode(): string
    {
        return mb_strtoupper(trim((string) $this->input('outlet.code')));
    }
}
