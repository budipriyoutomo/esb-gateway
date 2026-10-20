<?php

namespace App\Http\Requests\Accounting\Concerns;

use App\Domain\Canonical\LedgerQuery;
use Closure;
use Illuminate\Support\Carbon;

/**
 * The period of a general ledger read ([ESB-042]): both ends or neither,
 * YYYY-MM-DD, at most LedgerQuery::MAX_DAYS long. The ERP answers a ledger
 * period unpaged, so the gateway bounds it.
 *
 * For a FormRequest with "dateFrom" / "dateTo".
 */
trait ValidatesLedgerPeriod
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function periodRules(): array
    {
        return [
            // Not "sometimes": a missing end must still trip required_with.
            'dateFrom' => ['nullable', 'date_format:Y-m-d', 'required_with:dateTo'],
            'dateTo' => [
                'nullable', 'date_format:Y-m-d', 'required_with:dateFrom', 'after_or_equal:dateFrom',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $from = $this->input('dateFrom');

                    if (! is_string($value) || ! is_string($from)) {
                        return;
                    }

                    $start = Carbon::createFromFormat('Y-m-d', $from);
                    $end = Carbon::createFromFormat('Y-m-d', $value);

                    if ($start !== null && $end !== null && $start->diffInDays($end) >= LedgerQuery::MAX_DAYS) {
                        $fail('Choose a period of at most '.LedgerQuery::MAX_DAYS.' days.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function periodMessages(): array
    {
        return [
            'dateFrom.required_with' => 'Choose the start of the period.',
            'dateTo.required_with' => 'Choose the end of the period.',
            'dateTo.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }
}
