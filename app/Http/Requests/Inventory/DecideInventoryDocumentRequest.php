<?php

namespace App\Http\Requests\Inventory;

use App\Authorization\InventoryPermissions;
use App\Authorization\Permission;
use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\InventoryKind;
use App\Http\Concerns\ResolvesInventoryKind;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Authorize or reject an inventory document ([ESB-034]). Only the item
 * journal's reject takes a reason, and the vendor does not require it; for
 * the other kinds a reason is ignored.
 */
class DecideInventoryDocumentRequest extends BaseFormRequest
{
    use ResolvesInventoryKind;

    /**
     * The vendor documents no length for the reason. TBD with the ESB team;
     * the gateway's own ceiling keeps the request bounded.
     */
    public const REASON_MAX = 500;

    /**
     * Both permissions: the decision is sent to the ESB (esb.invoke), and it
     * is this document's approval (<kind>.approve).
     */
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, InventoryPermissions::approve($this->kind()));
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

    public function kind(): InventoryKind
    {
        return $this->inventoryKind($this);
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
