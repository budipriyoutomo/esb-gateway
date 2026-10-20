<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ProductCategory;
use App\Domain\Canonical\ProductCategoryDraft;
use App\Domain\Canonical\ProductCategoryPage;
use App\Domain\Canonical\ProductCategoryQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for product categories. Read-only ([ESB-021]).
 */
interface ProductCategorySource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listCategories(ProductCategoryQuery $query): ProductCategoryPage;

    /**
     * The category with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findCategory(int $id): ?ProductCategory;

    /**
     * Delete a product category. One attempt.
     *
     * @throws EsbException
     */
    public function deleteCategory(int $id): void;

    /**
     * Set a product category inactive. One attempt.
     *
     * @throws EsbException
     */
    public function deactivateCategory(int $id): void;

    /**
     * Reactivate a deactivated product category. One attempt.
     *
     * @throws EsbException
     */
    public function restoreCategory(int $id): void;

    /**
     * Create one. One attempt.
     *
     * @throws EsbException
     */
    public function createCategory(ProductCategoryDraft $draft): void;

    /**
     * Replace one with the draft. One attempt.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function updateCategory(int $id, ProductCategoryDraft $draft): void;
}
