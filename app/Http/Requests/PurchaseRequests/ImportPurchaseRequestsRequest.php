<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ValidatesExcelUpload;

/**
 * An Excel file of purchase requests to hand to the ERP.
 *
 * The vendor accepts xlsx or xls up to 2 MB. The gateway does not parse the
 * sheet (its layout is undocumented), so the checks here are the ones that
 * stop a pointless upload (ValidatesExcelUpload).
 */
class ImportPurchaseRequestsRequest extends BaseFormRequest
{
    use ValidatesExcelUpload;

    /** The vendor's documented limit. */
    public const MAX_KILOBYTES = 2048;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManagePurchaseRequests);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['file' => $this->excelFileRules(self::MAX_KILOBYTES, ['xlsx', 'xls'])];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'The ERP accepts files of at most 2 MB.',
            'file.extensions' => 'The file must be an Excel workbook (.xlsx or .xls).',
        ];
    }

    public function safeFilename(): string
    {
        return $this->safeExcelFilename('purchase-requests');
    }
}
