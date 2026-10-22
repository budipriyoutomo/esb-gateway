<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\MenuCategory;
use App\Domain\Canonical\MenuCategoryDraft;
use App\Domain\Canonical\MenuCategoryPage;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads POS menu categories ([ESB-047]) and creates and replaces them
 * ([ESB-048]).
 */
interface MenuCategorySource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listCategories(int $page = 1): MenuCategoryPage;

    /**
     * The category and the page it is on, looked for on $page first: the
     * provider has no detail operation. Null when it is not found within
     * the bound.
     *
     * @return array{MenuCategory, int}|null
     *
     * @throws EsbException
     */
    public function findCategory(int $id, int $page = 1): ?array;

    /**
     * The new category's id, or null when the provider does not say it.
     *
     * @throws EsbException
     */
    public function createCategory(MenuCategoryDraft $draft): ?int;

    /**
     * @throws EsbException
     */
    public function updateCategory(int $id, MenuCategoryDraft $draft): void;
}
