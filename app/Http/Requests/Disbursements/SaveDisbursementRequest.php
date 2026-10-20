<?php

namespace App\Http\Requests\Disbursements;

use App\Authorization\Permission;
use App\Domain\Canonical\DisbursementDraft;
use App\Domain\Canonical\DisbursementDraftLine;
use App\Domain\Canonical\DisbursementPaymentMethod;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * A disbursement to create or replace ([ESB-039]), checked against the
 * vendor's schema before anything reaches the ESB:
 *
 * - cash (paymentId 1) needs the cash / bank account and takes no credit
 *   term or reference; credit (2) needs the supplier and takes no cash / bank
 *   account;
 * - credit term at most 99999 days, reference up to 50, notes up to 1000,
 *   each line's description up to 100 and amount decimal(20,4);
 * - rate > 0 (a currency rate of 0 converts nothing).
 *
 * The locked period, the supplier being active and every "must exist" are
 * the ERP's to check. The gateway bounds the number of lines (the vendor
 * documents none).
 */
class SaveDisbursementRequest extends BaseFormRequest
{
    public const MAX_LINES = 500;

    public const NOTES_MAX = 1000;

    public const REFERENCE_MAX = 50;

    public const DESCRIPTION_MAX = 100;

    public const CREDIT_TERM_MAX = 99999;

    /** A bound on amounts and rates; the vendor's columns are decimal(20,4). */
    private const AMOUNT_MAX = 9999999999999999;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageDisbursements);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $cash = (string) DisbursementPaymentMethod::Cash->value;
        $credit = (string) DisbursementPaymentMethod::Credit->value;

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'branchId' => ['required', 'integer', 'min:1'],
            'paymentId' => ['required', 'integer', Rule::enum(DisbursementPaymentMethod::class)],
            'currencyId' => ['required', 'integer', 'min:1'],
            'rate' => ['required', 'numeric', 'gt:0', 'max:'.self::AMOUNT_MAX, 'decimal:0,4'],
            'cashBankCoaNo' => ['nullable', 'string', 'max:32', 'required_if:paymentId,'.$cash, 'prohibited_if:paymentId,'.$credit],
            'supplierId' => ['nullable', 'integer', 'min:1', 'required_if:paymentId,'.$credit],
            'creditTerm' => ['nullable', 'integer', 'min:0', 'max:'.self::CREDIT_TERM_MAX, 'prohibited_if:paymentId,'.$cash],
            'reference' => ['nullable', 'string', 'max:'.self::REFERENCE_MAX, 'prohibited_if:paymentId,'.$cash],
            'invoiceDueDate' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:'.self::NOTES_MAX],
            'lines' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*' => ['array'],
            'lines.*.branchId' => ['required', 'integer', 'min:1'],
            'lines.*.coaNo' => ['required', 'string', 'max:32'],
            'lines.*.description' => ['required', 'string', 'max:'.self::DESCRIPTION_MAX],
            'lines.*.amount' => ['required', 'numeric', 'min:-'.self::AMOUNT_MAX, 'max:'.self::AMOUNT_MAX, 'decimal:0,4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least one account line.',
            'lines.min' => 'Add at least one account line.',
            'cashBankCoaNo.required_if' => 'Choose the cash or bank account the money goes out of.',
            'cashBankCoaNo.prohibited_if' => 'A credit disbursement is not paid from a cash or bank account.',
            'supplierId.required_if' => 'A credit disbursement needs the supplier it is owed to.',
            'creditTerm.prohibited_if' => 'A cash disbursement has no credit term.',
            'reference.prohibited_if' => 'A cash disbursement has no reference.',
            'rate.gt' => 'The rate must be more than 0.',
        ];
    }

    public function toDraft(): DisbursementDraft
    {
        /** @var list<array<string, mixed>> $lines */
        $lines = $this->validated('lines');

        return new DisbursementDraft(
            date: (string) $this->validatedString('date'),
            branchId: (int) $this->validatedInt('branchId'),
            payment: DisbursementPaymentMethod::from((int) $this->validatedInt('paymentId')),
            currencyId: (int) $this->validatedInt('currencyId'),
            rate: self::number($this->validated('rate')) ?? 1,
            cashBankCoaNo: $this->validatedString('cashBankCoaNo'),
            supplierId: $this->validatedInt('supplierId'),
            creditTerm: $this->validatedInt('creditTerm'),
            reference: $this->validatedString('reference'),
            invoiceDueDate: $this->validatedString('invoiceDueDate'),
            notes: $this->validatedString('notes'),
            lines: array_map(
                static fn (array $line): DisbursementDraftLine => new DisbursementDraftLine(
                    branchId: (int) $line['branchId'],
                    coaNo: (string) $line['coaNo'],
                    description: (string) $line['description'],
                    amount: self::number($line['amount'] ?? null) ?? 0,
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
