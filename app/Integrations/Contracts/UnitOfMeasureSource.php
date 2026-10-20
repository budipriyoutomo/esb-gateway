<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\UnitOfMeasure;
use App\Domain\Canonical\UnitOfMeasureDraft;
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

    /**
     * Delete a unit. The vendor says the ERP may deactivate instead of
     * removing it: returns true when removed permanently, false when only
     * deactivated, null when the response does not say. One attempt.
     *
     * @throws EsbException
     */
    public function deleteUnit(int $id): ?bool;

    /**
     * Reactivate a deactivated unit. One attempt.
     *
     * @throws EsbException
     */
    public function restoreUnit(int $id): void;

    /**
     * Create one. One attempt.
     *
     * @throws EsbException
     */
    public function createUnit(UnitOfMeasureDraft $draft): void;

    /**
     * Replace one with the draft. One attempt.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function updateUnit(int $id, UnitOfMeasureDraft $draft): void;
}
