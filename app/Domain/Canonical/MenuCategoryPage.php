<?php

namespace App\Domain\Canonical;

/**
 * One page of menu categories as the ESB pages them ([ESB-047]).
 */
readonly class MenuCategoryPage
{
    /**
     * @param  list<MenuCategory>  $categories
     */
    public function __construct(
        public array $categories,
        public ?Pagination $pagination = null,
    ) {}

    public function find(int $id): ?MenuCategory
    {
        foreach ($this->categories as $category) {
            if ($category->id === $id) {
                return $category;
            }
        }

        return null;
    }
}
