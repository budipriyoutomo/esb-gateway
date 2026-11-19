<?php

namespace App\Http\Requests\ClosingRecaps;

use App\Authorization\Permission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A POS payment method -> the ERP account and merchant discount rate Push
 * Sales records it with (ADR-032, P30).
 */
class SaveClosingPaymentMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageClosingMappings->value) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'coaNo' => ['required', 'string', 'max:50'],
            'coaName' => ['nullable', 'string', 'max:255'],
            // A percentage, as the ESB's merchantDiscountRate.
            'merchantDiscountRate' => ['required', 'numeric', 'decimal:0,4', 'between:0,100'],
        ];
    }

    /**
     * @return array{coaNo: string, coaName: string|null, merchantDiscountRate: string}
     */
    public function values(): array
    {
        $name = trim($this->string('coaName')->value());

        return [
            'coaNo' => trim($this->string('coaNo')->value()),
            'coaName' => $name === '' ? null : $name,
            'merchantDiscountRate' => (string) $this->input('merchantDiscountRate'),
        ];
    }
}
