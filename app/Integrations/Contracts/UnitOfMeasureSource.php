<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\UnitOfMeasure;
use App\Domain\Canonical\UnitOfMeasurePage;
use App\Domain\Canonical\UnitOfMeasureQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for units of measure. Read-only ([ESB-020]).
 */
interface UnitOfMeasureSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listUnits(UnitOfMeasureQuery $query): UnitOfMeasurePage;

    /**
     * The unit with this id, or null when the provider answers successfully
     * with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findUnit(int $id): ?UnitOfMeasure;
}
