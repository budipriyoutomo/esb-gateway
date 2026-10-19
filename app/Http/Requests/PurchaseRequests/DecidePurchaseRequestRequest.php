<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Authorization\Permission;
use App\Domain\Canonical\ApprovalDecision;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class DecidePurchaseRequestRequest extends BaseFormRequest
{
    /**
     * Both permissions: the decision is sent to the ESB (esb.invoke), and it
     * is a purchasing approval (purchase-requests.approve).
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can(Permission::InvokeEsbOperations->value)
            && $user->can(Permission::ApprovePurchaseRequests->value);
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
