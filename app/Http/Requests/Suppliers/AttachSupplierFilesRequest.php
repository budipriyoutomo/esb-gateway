<?php

namespace App\Http\Requests\Suppliers;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ForwardsAttachments;

/**
 * Files to attach to a supplier ([ESB-032]). The vendor documents no type
 * or size limit; the gateway accepts a few documents or images (PDF, JPEG,
 * PNG) of at most 5 MB each -- an assumption, TBD with the ERP.
 */
class AttachSupplierFilesRequest extends BaseFormRequest
{
    use ForwardsAttachments;

    public const MAX_FILES = 5;

    public const MAX_KILOBYTES = 5120;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageSuppliers);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'max:'.self::MAX_KILOBYTES, 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.max' => 'Attach at most '.self::MAX_FILES.' files at a time.',
            'files.*.max' => 'Each file may be at most 5 MB.',
            'files.*.mimes' => 'Attach PDF, JPEG or PNG files.',
            'files.*.extensions' => 'Attach PDF, JPEG or PNG files.',
        ];
    }
}
