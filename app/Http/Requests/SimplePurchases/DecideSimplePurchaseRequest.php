<?php

namespace App\Http\Requests\SimplePurchases;

use App\Authorization\Permission;
use App\Domain\Canonical\SimplePurchaseDecision;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class DecideSimplePurchaseRequest extends BaseFormRequest
{
    /**
     * The vendor documents no length for the reason. TBD with the ESB team;
     * until then the gateway's own ceiling keeps the request bounded.
     */
    public const REASON_MAX = 500;

    /**
     * Both permissions: the decision is sent to the ESB (esb.invoke), and it
     * is a purchase approval (simple-purchases.approve).
     */
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ApproveSimplePurchases);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::enum(SimplePurchaseDecision::class)],
            // The vendor marks the reject body as required. TrimStrings has
            // already turned a blank reason into null.
            'reason' => [
                Rule::requiredIf($this->input('decision') === SimplePurchaseDecision::Reject->value),
                'nullable',
                'string',
                'max:'.self::REASON_MAX,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Give a reason for rejecting this simple purchase.',
        ];
    }

    public function decision(): SimplePurchaseDecision
    {
        return SimplePurchaseDecision::from((string) $this->validated('decision'));
    }

    public function reason(): ?string
    {
        return $this->validatedString('reason');
    }
}
