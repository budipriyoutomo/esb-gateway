<?php

namespace App\Http\Requests\PurchaseInvoices;

use App\Authorization\Permission;
use App\Domain\Canonical\PurchaseInvoiceDecision;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class DecidePurchaseInvoiceRequest extends BaseFormRequest
{
    /**
     * Both permissions: the decision is sent to the ESB (esb.invoke), and it
     * is an invoice approval (purchase-invoices.approve).
     */
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ApprovePurchaseInvoices);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::enum(PurchaseInvoiceDecision::class)],
        ];
    }

    public function decision(): PurchaseInvoiceDecision
    {
        return PurchaseInvoiceDecision::from((string) $this->validated('decision'));
    }
}
