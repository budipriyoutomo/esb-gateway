<?php

namespace App\Http\Requests\Suppliers;

use App\Domain\Canonical\SupplierQuery;
use App\Http\Requests\ListReferenceDataRequest;

/**
 * Validates the supplier list filters before they reach the ESB ([ESB-024]).
 */
class ListSuppliersRequest extends ListReferenceDataRequest
{
    public function toQuery(): SupplierQuery
    {
        return new SupplierQuery(
            page: $this->page(),
            limit: $this->limit(),
            search: $this->search(),
            activeOnly: $this->activeOnly(),
        );
    }
}
