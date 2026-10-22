<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Domain\Canonical\MemorialJournalDraft;
use App\Domain\Canonical\MemorialJournalDraftLine;
use App\Http\Requests\BaseFormRequest;

/**
 * A memorial journal to create or replace ([ESB-045]), checked against the
 * vendor's schema before anything reaches the ESB:
 *
 * - at least two lines, each with a branch, an account and a currency;
 * - exactly one of debit or credit per line, >= 0 with at most four
 *   decimals (the vendor: one is "required when the other is empty"; a line
 *   with both is the gateway's refusal, TBD with the ESB team);
 * - rate > 0 (the vendor's "min 0" would convert nothing), notes up to 1000,
 *   each line's notes up to 100.
 *
 * Whether the lines balance, the locked period and every "must exist" are
 * the ERP's to judge. The gateway bounds the number of lines (the vendor
 * documents none). A line's id is the ERP's line it replaces, used only to
 * carry that line's cost center and project over.
 */
class SaveMemorialJournalRequest extends BaseFormRequest
{
    public const MIN_LINES = 2;

    public const MAX_LINES = 500;

    public const NOTES_MAX = 1000;

    public const LINE_NOTES_MAX = 100;

    /** A bound on amounts and rates; the vendor documents decimal(20,4) elsewhere. */
    private const AMOUNT_MAX = 9999999999999999;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageMemorialJournals);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $amount = ['numeric', 'min:0', 'max:'.self::AMOUNT_MAX, 'decimal:0,4'];

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:'.self::NOTES_MAX],
            'lines' => ['required', 'array', 'min:'.self::MIN_LINES, 'max:'.self::MAX_LINES],
            'lines.*' => ['array'],
            'lines.*.id' => ['nullable', 'integer', 'min:1'],
            'lines.*.branchId' => ['required', 'integer', 'min:1'],
            'lines.*.coaNo' => ['required', 'string', 'max:32'],
            'lines.*.currencyId' => ['required', 'integer', 'min:1'],
            'lines.*.rate' => ['required', 'numeric', 'gt:0', 'max:'.self::AMOUNT_MAX, 'decimal:0,4'],
            'lines.*.debit' => ['nullable', 'required_without:lines.*.credit', ...$amount],
            'lines.*.credit' => ['nullable', 'prohibits:lines.*.debit', ...$amount],
            'lines.*.notes' => ['nullable', 'string', 'max:'.self::LINE_NOTES_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least two lines.',
            'lines.min' => 'A journal needs at least two lines.',
            'lines.*.debit.required_without' => 'Enter a debit or a credit.',
            'lines.*.credit.prohibits' => 'A line is either a debit or a credit, not both.',
            'lines.*.rate.gt' => 'The rate must be more than 0.',
        ];
    }

    public function toDraft(): MemorialJournalDraft
    {
        /** @var list<array<string, mixed>> $lines */
        $lines = $this->validated('lines');

        return new MemorialJournalDraft(
            date: (string) $this->validatedString('date'),
            notes: $this->validatedString('notes'),
            lines: array_map(
                static fn (array $line): MemorialJournalDraftLine => new MemorialJournalDraftLine(
                    id: isset($line['id']) ? (int) $line['id'] : null,
                    branchId: (int) $line['branchId'],
                    coaNo: (string) $line['coaNo'],
                    currencyId: (int) $line['currencyId'],
                    rate: self::number($line['rate'] ?? null) ?? 1,
                    debit: self::number($line['debit'] ?? null),
                    credit: self::number($line['credit'] ?? null),
                    notes: isset($line['notes']) && is_string($line['notes']) && $line['notes'] !== '' ? $line['notes'] : null,
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
