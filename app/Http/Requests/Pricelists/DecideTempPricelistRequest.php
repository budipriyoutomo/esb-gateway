<?php

namespace App\Http\Requests\Pricelists;

use App\Authorization\Permission;
use App\Domain\Canonical\ApprovalDecision;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * A decision on a temporary pricelist ([ESB-027], S2). The vendor takes no
 * reason for a rejection.
 */
class DecideTempPricelistRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ApprovePricelists);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::enum(ApprovalDecision::class)],
        ];
    }

    public function decision(): ApprovalDecision
    {
        return ApprovalDecision::from((string) $this->validated('decision'));
    }
}
