<?php

namespace App\Domain\Canonical;

/**
 * A menu category to create or replace ([ESB-048]), as validated by
 * SaveMenuCategoryRequest. Texts are "" when empty: the vendor's examples
 * send "" for "none".
 */
readonly class MenuCategoryDraft
{
    /**
     * @param  list<MenuCategoryDraftDetail>  $details
     */
    public function __construct(
        public string $name,
        public string $code,
        public string $salesAccount,
        public string $cogsAccount,
        public string $discountAccount,
        public string $description,
        public string $buttonColor,
        public array $details,
    ) {}

    /**
     * The ids of the current details this draft keeps.
     *
     * @return list<int>
     */
    public function detailIds(): array
    {
        $ids = [];

        foreach ($this->details as $detail) {
            if ($detail->id !== null) {
                $ids[] = $detail->id;
            }
        }

        return $ids;
    }
}
