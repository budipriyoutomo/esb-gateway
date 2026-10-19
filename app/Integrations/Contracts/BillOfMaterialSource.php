<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\BillOfMaterial;
use App\Domain\Canonical\BillOfMaterialPage;
use App\Domain\Canonical\BillOfMaterialQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for bills of material (recipes). Read-only for
 * now; create, update, and delete are not built ([ESB-014]).
 *
 * App\Integrations\ESB\Clients\BillOfMaterialClient is the reference
 * implementation; callers depend on this contract (ADR-016, ADR-019).
 */
interface BillOfMaterialSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listBillsOfMaterial(BillOfMaterialQuery $query): BillOfMaterialPage;

    /**
     * The bill of material with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findBillOfMaterial(int $id): ?BillOfMaterial;
}
