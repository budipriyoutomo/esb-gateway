<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ForwardsAttachments;

/**
 * One image to attach to a memorial journal ([ESB-046]). The vendor names
 * "e.g. jpg, png" and no size: the types and the 2 MB bound are the
 * gateway's (TBD with the ESB team).
 */
class AttachMemorialJournalImageRequest extends BaseFormRequest
{
    use ForwardsAttachments;

    public const MAX_KILOBYTES = 2048;

    public const EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageMemorialJournals);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $extensions = implode(',', self::EXTENSIONS);

        return [
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:'.$extensions, 'extensions:'.$extensions],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Choose an image.',
            'file.max' => 'The image may be at most 2 MB.',
            'file.mimes' => 'Attach a JPG or PNG image.',
            'file.extensions' => 'Attach a JPG or PNG image.',
        ];
    }
}
