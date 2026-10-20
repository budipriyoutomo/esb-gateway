import { Head, Link } from '@inertiajs/react';
import { ApprovalActions } from '@/components/approval-actions';
import { DataTable } from '@/components/data-table';
import {
    DetailField,
    DetailFields,
    DetailSummary,
} from '@/components/detail-fields';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/status-badge';
import { usePermissions } from '@/hooks/use-permissions';
import { branchScopeLabel } from '@/lib/branch-scope';
import { formatAmount, formatDate, formatDateTime } from '@/lib/format';
import { index as pricelistsIndex } from '@/routes/pricelists';
import {
    destroy as destroyTemp,
    edit as editTemp,
    index as tempIndex,
} from '@/routes/pricelists/temp';
import { store as storeDecision } from '@/routes/pricelists/temp/decisions';
import type { TempPricelist } from '@/types/master-data';

function byline(name: string | null, at: string | null): string | null {
    const parts = [name, at === null ? null : formatDateTime(at)].filter(
        (part) => part !== null,
    );

    return parts.length === 0 ? null : parts.join(' · ');
}

export default function TempPricelistShow({
    pricelist,
}: {
    pricelist: TempPricelist;
}) {
    const { can } = usePermissions();
    // The vendor accepts a decision only while the proposal is "Waiting
    // For Approval" (status 38, [ESB-027]).
    const waiting = pricelist.statusId === 38;

    return (
        <>
            <Head title={pricelist.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={pricelist.number}
                    description="Proposed prices, read live from the ERP."
                    back={{ href: tempIndex(), label: 'Proposed prices' }}
                    actions={
                        <>
                            <StatusBadge status={pricelist.statusName} />
                            {can('esb.invoke') && can('pricelists.manage') && (
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={editTemp(pricelist.number)}>
                                        Edit
                                    </Link>
                                </Button>
                            )}
                            <ApprovalActions
                                documentLabel="proposed pricelist"
                                number={pricelist.number}
                                decisionUrl={
                                    storeDecision(pricelist.number).url
                                }
                                deleteUrl={destroyTemp(pricelist.number).url}
                                canDecide={
                                    waiting &&
                                    can('esb.invoke') &&
                                    can('pricelists.approve')
                                }
                                canDelete={
                                    can('esb.invoke') &&
                                    can('pricelists.manage')
                                }
                            />
                        </>
                    }
                />

                <DetailSummary
                    label="Proposed pricelist summary"
                    items={[
                        { label: 'Supplier', value: pricelist.supplierName },
                        {
                            label: 'From',
                            value: formatDate(pricelist.priceDate),
                        },
                        {
                            label: 'Until',
                            value: formatDate(pricelist.expireDate),
                        },
                        { label: 'Currency', value: pricelist.currencyName },
                    ]}
                />

                <DetailFields>
                    <DetailField
                        label="Branches"
                        value={branchScopeLabel(pricelist.branches)}
                    />
                    <DetailField
                        label="Created"
                        value={byline(pricelist.createdBy, pricelist.createdAt)}
                    />
                    <DetailField
                        label="Last edited"
                        value={byline(pricelist.editedBy, pricelist.editedAt)}
                    />
                    <DetailField
                        label="Rejected"
                        value={byline(
                            pricelist.rejectedBy,
                            pricelist.rejectedAt,
                        )}
                    />
                </DetailFields>

                <DataTable
                    caption="Proposed prices"
                    columns={[
                        {
                            key: 'product',
                            header: 'Product',
                            cell: (line) => (
                                <>
                                    <div className="font-medium">
                                        {line.productName ?? '—'}
                                    </div>
                                    {line.productCode && (
                                        <div className="text-muted-foreground font-mono text-xs">
                                            {line.productCode}
                                        </div>
                                    )}
                                </>
                            ),
                        },
                        {
                            key: 'unit',
                            header: 'Unit',
                            cell: (line) => line.unitName ?? '—',
                        },
                        {
                            key: 'last',
                            header: 'Current price',
                            align: 'right',
                            className: 'text-muted-foreground',
                            cell: (line) =>
                                formatAmount(
                                    line.lastPrice,
                                    pricelist.currencyName,
                                ),
                        },
                        {
                            key: 'price',
                            header: 'Proposed price',
                            align: 'right',
                            cell: (line) =>
                                formatAmount(
                                    line.price,
                                    pricelist.currencyName,
                                ),
                        },
                    ]}
                    rows={pricelist.lines}
                    rowKey={(line, index) =>
                        `${line.productDetailId ?? 'x'}-${index}`
                    }
                    empty="This proposal has no prices."
                />
            </div>
        </>
    );
}

TempPricelistShow.layout = {
    breadcrumbs: [
        { title: 'Pricelist', href: pricelistsIndex() },
        { title: 'Proposed prices', href: tempIndex() },
    ],
};
