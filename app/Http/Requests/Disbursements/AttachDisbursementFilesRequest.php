<?php

namespace App\Http\Requests\Disbursements;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ForwardsAttachments;

/**
 * Files to attach to a disbursement ([ESB-040]): the types the vendor names,
 * 2 MB each, up to five at a time -- within the vendor's 10 MB total.
 */
class AttachDisbursementFilesRequest extends BaseFormRequest
{
    use ForwardsAttachments;

    public const MAX_FILES = 5;

    public const MAX_KILOBYTES = 2048;

    public const EXTENSIONS = ['pdf', 'bmp', 'gif', 'jpg', 'jpeg', 'png'];

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageDisbursements);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $extensions = implode(',', self::EXTENSIONS);

        return [
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'max:'.self::MAX_KILOBYTES, 'mimes:'.$extensions, 'extensions:'.$extensions],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $types = strtoupper(implode(', ', self::EXTENSIONS));

        return [
            'files.required' => 'Choose at least one file.',
            'files.max' => 'Attach at most '.self::MAX_FILES.' files at a time.',
            'files.*.max' => 'Each file may be at most 2 MB.',
            'files.*.mimes' => 'Attach '.$types.' files.',
            'files.*.extensions' => 'Attach '.$types.' files.',
        ];
    }
}
