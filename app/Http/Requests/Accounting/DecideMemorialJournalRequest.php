<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Domain\Canonical\ApprovalDecision;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Authorize or reject a memorial journal ([ESB-044]). The vendor requires a
 * reason only while the journal is waiting for approval; the ERP knows the
 * status and refuses a rejection without one, so the gateway does not.
 */
class DecideMemorialJournalRequest extends BaseFormRequest
{
    /**
     * The vendor documents no length for the reason. TBD with the ESB team;
     * the gateway's own ceiling keeps the request bounded.
     */
    public const REASON_MAX = 500;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ApproveMemorialJournals);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::enum(ApprovalDecision::class)],
            // TrimStrings has already turned a blank reason into null.
            'reason' => ['nullable', 'string', 'max:'.self::REASON_MAX],
        ];
    }

    public function decision(): ApprovalDecision
    {
        return ApprovalDecision::from((string) $this->validated('decision'));
    }

    public function reason(): ?string
    {
        return $this->validatedString('reason');
    }
}
