<?php

namespace App\Http\Requests\Units;

use App\Domain\Canonical\UnitOfMeasureQuery;
use App\Http\Requests\ListReferenceDataRequest;

/**
 * Validates the unit list filters before they reach the ESB ([ESB-020]).
 */
class ListUnitsRequest extends ListReferenceDataRequest
{
    public function toQuery(): UnitOfMeasureQuery
    {
        return new UnitOfMeasureQuery(
            page: $this->page(),
            limit: $this->limit(),
            search: $this->search(),
            activeOnly: $this->activeOnly(),
        );
    }
}
