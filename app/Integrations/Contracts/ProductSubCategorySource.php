<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ProductSubCategory;
use App\Domain\Canonical\ProductSubCategoryDraft;
use App\Domain\Canonical\ProductSubCategoryPage;
use App\Domain\Canonical\ProductSubCategoryQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for product sub-categories. Read-only
 * ([ESB-022]).
 */
interface ProductSubCategorySource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listSubCategories(ProductSubCategoryQuery $query): ProductSubCategoryPage;

    /**
     * The sub-category with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findSubCategory(int $id): ?ProductSubCategory;

    /**
     * Delete a product sub-category. One attempt.
     *
     * @throws EsbException
     */
    public function deleteSubCategory(int $id): void;

    /**
     * Set a product sub-category inactive. One attempt.
     *
     * @throws EsbException
     */
    public function deactivateSubCategory(int $id): void;

    /**
     * Reactivate a deactivated product sub-category. One attempt.
     *
     * @throws EsbException
     */
    public function restoreSubCategory(int $id): void;

    /**
     * Create one. One attempt.
     *
     * @throws EsbException
     */
    public function createSubCategory(ProductSubCategoryDraft $draft): void;

    /**
     * Replace one with the draft. One attempt.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function updateSubCategory(int $id, ProductSubCategoryDraft $draft): void;
}
