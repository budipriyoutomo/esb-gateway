<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\Supplier;
use App\Domain\Canonical\SupplierPage;
use App\Domain\Canonical\SupplierQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for suppliers. Read-only ([ESB-024]).
 */
interface SupplierSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listSuppliers(SupplierQuery $query): SupplierPage;

    /**
     * The supplier with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findSupplier(int $id): ?Supplier;

    /**
     * Deactivate a supplier. The ERP refuses while it has ongoing
     * transactions. One attempt.
     *
     * @throws EsbException
     */
    public function deactivateSupplier(int $id): void;

    /**
     * Reactivate a deactivated supplier. One attempt.
     *
     * @throws EsbException
     */
    public function restoreSupplier(int $id): void;

    /**
     * Add attachment files to a supplier. One attempt.
     *
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws EsbException
     */
    public function attachFiles(int $id, array $files): void;

    /**
     * Remove every attachment of a supplier (the vendor offers no single
     * delete). One attempt.
     *
     * @throws EsbException
     */
    public function deleteAttachments(int $id): void;
}
