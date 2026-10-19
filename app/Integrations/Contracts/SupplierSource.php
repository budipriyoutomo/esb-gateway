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
}
