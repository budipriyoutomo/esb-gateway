<?php

namespace App\Services;

use App\Authorization\Permission;
use App\Domain\Canonical\DisbursementQuery;
use App\Domain\Canonical\InventoryDocumentQuery;
use App\Domain\Canonical\InventoryKind;
use App\Domain\Canonical\MemorialJournalQuery;
use App\Domain\Canonical\Pagination;
use App\Domain\Canonical\TempPricelistQuery;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\Contracts\PricelistSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\User;
use Closure;

/**
 * How many documents wait for approval, per module the person may approve
 * (TODO-modules.md UI-4).
 *
 * Only modules whose list filters by the documented status 38 (Waiting For
 * Approval) are counted: disbursements, memorial journals, item journals and
 * proposed prices. Purchasing documents are left out -- their lists take no
 * status filter, and which status is "waiting" for them is not documented.
 *
 * Each count is one read of a one-row page; the ERP's own total is the count.
 * A read that fails gives null ("unknown") for that module only, so one
 * unreachable list never empties the whole card.
 */
final readonly class PendingApprovals
{
    public const STATUS_WAITING_FOR_APPROVAL = 38;

    public function __construct(
        private DisbursementSource $disbursements,
        private MemorialJournalSource $memorialJournals,
        private InventoryDocumentSource $inventory,
        private PricelistSource $pricelists,
    ) {}

    /**
     * @return list<array{key: string, title: string, count: int|null, href: string}>
     */
    public function for(User $user): array
    {
        // Every list is read through esb.invoke; without it the links on the
        // card would only lead to a 403.
        if (! $user->can(Permission::InvokeEsbOperations->value)) {
            return [];
        }

        $waiting = self::STATUS_WAITING_FOR_APPROVAL;

        $modules = [
            [
                'key' => 'disbursements',
                'title' => 'Disbursements',
                'permission' => Permission::ApproveDisbursements,
                'route' => 'disbursements.index',
                'read' => fn (): ?Pagination => $this->disbursements
                    ->listDisbursements(new DisbursementQuery(limit: 1, statusId: $waiting))
                    ->pagination,
            ],
            [
                'key' => 'memorial-journals',
                'title' => 'Memorial journals',
                'permission' => Permission::ApproveMemorialJournals,
                'route' => 'memorial-journals.index',
                'read' => fn (): ?Pagination => $this->memorialJournals
                    ->listJournals(new MemorialJournalQuery(limit: 1, statusId: $waiting))
                    ->pagination,
            ],
            [
                'key' => 'item-journals',
                'title' => 'Item journals',
                'permission' => Permission::ApproveItemJournals,
                'route' => 'item-journals.index',
                'read' => fn (): ?Pagination => $this->inventory
                    ->listDocuments(InventoryKind::ItemJournals, new InventoryDocumentQuery(limit: 1, statusId: $waiting))
                    ->pagination,
            ],
            [
                'key' => 'pricelists',
                'title' => 'Proposed prices',
                'permission' => Permission::ApprovePricelists,
                'route' => 'pricelists.temp.index',
                'read' => fn (): ?Pagination => $this->pricelists
                    ->listTempPricelists(new TempPricelistQuery(limit: 1, statusId: $waiting))
                    ->pagination,
            ],
        ];

        $counts = [];

        foreach ($modules as $module) {
            if (! $user->can($module['permission']->value)) {
                continue;
            }

            $counts[] = [
                'key' => $module['key'],
                'title' => $module['title'],
                'count' => $this->count($module['read']),
                'href' => route($module['route'], ['statusId' => $waiting], absolute: false),
            ];
        }

        return $counts;
    }

    /**
     * @param  Closure(): ?Pagination  $read
     */
    private function count(Closure $read): ?int
    {
        try {
            return $read()?->count;
        } catch (EsbException) {
            // Already logged by the client with its correlation id; the card
            // says "unknown" and the module's own page shows the error.
            return null;
        }
    }
}
