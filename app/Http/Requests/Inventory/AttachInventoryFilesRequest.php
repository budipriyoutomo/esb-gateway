<?php

namespace App\Http\Requests\Inventory;

use App\Authorization\InventoryPermissions;
use App\Authorization\Permission;
use App\Domain\Canonical\InventoryKind;
use App\Http\Concerns\ResolvesInventoryKind;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Concerns\ForwardsAttachments;

/**
 * Files to attach to an inventory document ([ESB-036]): the types the vendor
 * names for the kind (InventoryKind::attachmentExtensions()), up to five at a
 * time, each within the kind's size.
 */
class AttachInventoryFilesRequest extends BaseFormRequest
{
    use ForwardsAttachments;
    use ResolvesInventoryKind;

    public const MAX_FILES = 5;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, InventoryPermissions::manage($this->kind()));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $extensions = implode(',', $this->kind()->attachmentExtensions());

        return [
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'max:'.$this->kind()->attachmentMaxKilobytes(), 'mimes:'.$extensions, 'extensions:'.$extensions],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $types = strtoupper(implode(', ', $this->kind()->attachmentExtensions()));

        return [
            'files.required' => 'Choose at least one file.',
            'files.max' => 'Attach at most '.self::MAX_FILES.' files at a time.',
            'files.*.max' => 'Each file may be at most '.intdiv($this->kind()->attachmentMaxKilobytes(), 1024).' MB.',
            'files.*.mimes' => 'Attach '.$types.' files.',
            'files.*.extensions' => 'Attach '.$types.' files.',
        ];
    }

    public function kind(): InventoryKind
    {
        return $this->inventoryKind($this);
    }
}
