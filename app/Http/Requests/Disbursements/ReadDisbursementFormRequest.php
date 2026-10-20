<?php

namespace App\Http\Requests\Disbursements;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ValidatesExcelUpload;

/**
 * The ERP's Excel disbursement form, to fill a form's lines from
 * ([ESB-040]): .xlsx or .xls, at most 2 MB (the vendor's limit). Needs what
 * the form it fills needs.
 */
class ReadDisbursementFormRequest extends BaseFormRequest
{
    use ValidatesExcelUpload;

    public const MAX_KILOBYTES = 2048;

    public const EXTENSIONS = ['xlsx', 'xls'];

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageDisbursements);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['file' => $this->excelFileRules(self::MAX_KILOBYTES, self::EXTENSIONS)];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'The ERP accepts files of at most 2 MB.',
            'file.extensions' => $this->excelTypeMessage(self::EXTENSIONS),
        ];
    }

    public function safeFilename(): string
    {
        return $this->safeExcelFilename('disbursement-form');
    }
}
