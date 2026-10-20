<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\SupplierCategory;
use App\Domain\Canonical\SupplierCategoryDraft;
use App\Domain\Canonical\SupplierCategoryPage;
use App\Domain\Canonical\SupplierCategoryQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for supplier categories. Read-only
 * ([ESB-023]).
 */
interface SupplierCategorySource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listCategories(SupplierCategoryQuery $query): SupplierCategoryPage;

    /**
     * The category with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findCategory(int $id): ?SupplierCategory;

    /**
     * Whether the ERP would delete this category (it refuses while suppliers
     * use it). A read.
     *
     * @throws EsbException
     */
    public function canDeleteCategory(int $id): bool;

    /**
     * Delete a supplier category. One attempt.
     *
     * @throws EsbException
     */
    public function deleteCategory(int $id): void;

    /**
     * Reactivate a deactivated supplier category. One attempt.
     *
     * @throws EsbException
     */
    public function restoreCategory(int $id): void;

    /**
     * Create one. One attempt.
     * Returns the new id, or null when the response carries none.
     *
     * @throws EsbException
     */
    public function createCategory(SupplierCategoryDraft $draft): ?int;

    /**
     * Replace one with the draft. One attempt.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function updateCategory(int $id, SupplierCategoryDraft $draft): void;
}
