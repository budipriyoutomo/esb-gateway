<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Authorization\Permission;
use App\Domain\Canonical\OutstandingRequestQuery;
use App\Domain\Canonical\OutstandingStage;
use App\Http\Requests\BaseFormRequest;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * The PR outstanding report's filters (ADR-034). Both ends of the required
 * date period come together and are at most OutstandingRequestQuery::MAX_DAYS
 * apart; without them the page only asks for them and nothing is read.
 */
class ListOutstandingRequestsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.OutstandingRequestQuery::MAX_LIMIT],
            // Not "sometimes": a missing end must still trip required_with.
            'requiredDateFrom' => ['nullable', 'date_format:Y-m-d', 'required_with:requiredDateTo'],
            'requiredDateTo' => [
                'nullable', 'date_format:Y-m-d', 'required_with:requiredDateFrom', 'after_or_equal:requiredDateFrom',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $from = $this->input('requiredDateFrom');

                    if (! is_string($value) || ! is_string($from)) {
                        return;
                    }

                    $start = Carbon::createFromFormat('Y-m-d', $from);
                    $end = Carbon::createFromFormat('Y-m-d', $value);

                    if ($start !== null && $end !== null && $start->diffInDays($end) >= OutstandingRequestQuery::MAX_DAYS) {
                        $fail('Choose a period of at most '.OutstandingRequestQuery::MAX_DAYS.' days.');
                    }
                },
            ],
            'branchId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'purchaseRequestNumber' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/'],
            'stage' => ['sometimes', 'nullable', 'string', Rule::enum(OutstandingStage::class)],
            'overdueOnly' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requiredDateFrom.required_with' => 'Choose the start of the period.',
            'requiredDateTo.required_with' => 'Choose the end of the period.',
            'requiredDateTo.after_or_equal' => 'The end date must be on or after the start date.',
            'purchaseRequestNumber.regex' => 'Enter a purchase request number.',
            'limit.max' => 'At most :max items per page.',
        ];
    }

    /**
     * The report to build, or null while the period is not given.
     */
    public function toQuery(): ?OutstandingRequestQuery
    {
        $from = $this->validatedString('requiredDateFrom');
        $to = $this->validatedString('requiredDateTo');

        if ($from === null || $to === null) {
            return null;
        }

        $stage = $this->validatedString('stage');

        return new OutstandingRequestQuery(
            requiredDateFrom: $from,
            requiredDateTo: $to,
            branchId: $this->validatedInt('branchId'),
            purchaseRequestNumber: $this->validatedString('purchaseRequestNumber'),
            stage: $stage === null ? null : OutstandingStage::from($stage),
            overdueOnly: $this->boolean('overdueOnly'),
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? OutstandingRequestQuery::DEFAULT_LIMIT,
        );
    }

    /**
     * The filters echoed back to the page.
     *
     * @return array<string, int|string|bool>
     */
    public function validatedFilters(): array
    {
        return array_filter([
            'page' => $this->validatedInt('page'),
            'requiredDateFrom' => $this->validatedString('requiredDateFrom'),
            'requiredDateTo' => $this->validatedString('requiredDateTo'),
            'branchId' => $this->validatedInt('branchId'),
            'purchaseRequestNumber' => $this->validatedString('purchaseRequestNumber'),
            'stage' => $this->validatedString('stage'),
            'overdueOnly' => $this->boolean('overdueOnly') ? true : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
