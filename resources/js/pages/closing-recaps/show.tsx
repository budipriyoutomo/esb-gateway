import { Head, Link } from '@inertiajs/react';
import { ClosingItemJournals } from '@/components/closing-item-journals';
import type { ClosingJournal } from '@/components/closing-item-journals';
import { ClosingReadinessBadge } from '@/components/closing-readiness-badge';
import { ClosingSalesPush } from '@/components/closing-sales-push';
import type { ClosingSales } from '@/components/closing-sales-push';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DetailSummary } from '@/components/detail-fields';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatDateTime } from '@/lib/format';
import { items, outlets, purposes } from '@/routes/closing-mappings';
import { index as closingRecapsIndex } from '@/routes/closing-recaps';
import type {
    ClosingGap,
    ClosingItemSource,
    ClosingOutletMapping,
    ClosingRecapStatus,
} from '@/types/closing';
import { SOURCE_LABELS } from '@/types/closing';

type RecapItem = {
    source: ClosingItemSource;
    key: string;
    code: string | null;
    name: string;
    colorplateSold: string;
    posQty: string;
    totalSold: string;
    waste: string;
    adjustment: string;
    compensation: string;
    esbMenu: string | null;
    bom: string | null;
};

type Submission = {
    id: string;
    sequence: number;
    messageId: string;
    sentAt: string;
    receivedAt: string | null;
    client: string | null;
    current: boolean;
};

type Props = {
    recap: {
        id: string;
        outletCode: string;
        productionDate: string;
        sequence: number;
        items: RecapItem[];
        status: ClosingRecapStatus;
        lastReceivedAt: string | null;
        ready: boolean;
        missing: ClosingGap[];
        outlet: ClosingOutletMapping | null;
        submissions: Submission[];
    };
    canProcess?: boolean;
    actions?: {
        prepare: boolean;
        send: boolean;
        prepareSales?: boolean;
        sendSales?: boolean;
    };
    journals?: ClosingJournal[];
    sales?: ClosingSales;
};

/** Where each kind of gap is fixed. */
function gapHref(gap: ClosingGap): string {
    if (gap.type === 'outlet') return outlets().url;
    if (gap.type === 'purpose') return purposes().url;

    return items({ query: { search: gap.label } }).url;
}

const quantity = (value: string) => (value === '0' ? '—' : value);

const itemColumns: DataTableColumn<RecapItem>[] = [
    {
        key: 'item',
        header: 'Item',
        cell: (item) => (
            <div className="grid">
                <span className="font-medium">{item.name}</span>
                <span className="text-muted-foreground text-xs">
                    {SOURCE_LABELS[item.source]}
                    {item.code ? ` · ${item.code}` : ''}
                </span>
            </div>
        ),
    },
    {
        key: 'colorplate',
        header: 'Colorplate',
        align: 'right',
        cell: (item) => quantity(item.colorplateSold),
    },
    {
        key: 'pos',
        header: 'POS',
        align: 'right',
        cell: (item) => quantity(item.posQty),
    },
    {
        key: 'sold',
        header: 'Total sold',
        align: 'right',
        className: 'font-medium',
        cell: (item) => quantity(item.totalSold),
    },
    {
        key: 'waste',
        header: 'Waste',
        align: 'right',
        cell: (item) => quantity(item.waste),
    },
    {
        key: 'adjustment',
        header: 'Adjustment',
        align: 'right',
        cell: (item) => quantity(item.adjustment),
    },
    {
        key: 'compensation',
        header: 'Compensation',
        align: 'right',
        cell: (item) => quantity(item.compensation),
    },
    {
        key: 'menu',
        header: 'ERP menu',
        className: 'text-xs',
        cell: (item) => item.esbMenu ?? '—',
    },
    {
        key: 'bom',
        header: 'Bill of material',
        className: 'text-xs',
        cell: (item) =>
            item.source === 'closing' ? (item.bom ?? '—') : 'Not used',
    },
];

const submissionColumns: DataTableColumn<Submission>[] = [
    {
        key: 'sequence',
        header: 'Sequence',
        align: 'right',
        cell: (submission) => (
            <span className="inline-flex items-center gap-2">
                {submission.current && <Badge variant="outline">Current</Badge>}
                {submission.sequence}
            </span>
        ),
    },
    {
        key: 'received',
        header: 'Received',
        cell: (submission) => formatDateTime(submission.receivedAt),
    },
    {
        key: 'sent',
        header: 'Sent by sync-api',
        className: 'text-muted-foreground text-xs',
        cell: (submission) => formatDateTime(submission.sentAt),
    },
    {
        key: 'message',
        header: 'Message ID',
        className: 'font-mono text-xs',
        cell: (submission) => submission.messageId,
    },
    {
        key: 'client',
        header: 'Client',
        className: 'text-muted-foreground text-xs',
        cell: (submission) => submission.client ?? '—',
    },
];

export default function ClosingRecapShow({
    recap,
    canProcess = false,
    actions = { prepare: false, send: false },
    journals = [],
    sales = { hasData: false, grandTotal: null, payments: 0, draft: null },
}: Props) {
    const title = `${recap.outletCode} · ${formatDate(recap.productionDate)}`;

    return (
        <>
            <Head title={`Closing recap ${title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={`Closing recap ${title}`}
                    description={
                        journals.some((journal) => journal.status === 'created')
                            ? 'Held in the gateway. Its item journals below show what reached the ERP.'
                            : 'Held in the gateway. Nothing has been sent to the ERP yet.'
                    }
                    back={{
                        href: closingRecapsIndex(),
                        label: 'Closing recaps',
                    }}
                />

                <DetailSummary
                    label="Closing recap summary"
                    items={[
                        { label: 'Outlet', value: recap.outletCode },
                        {
                            label: 'Production date',
                            value: formatDate(recap.productionDate),
                        },
                        { label: 'Sequence', value: recap.sequence },
                        { label: 'Status', value: recap.status },
                        {
                            label: 'ERP branch',
                            value: recap.outlet
                                ? `${recap.outlet.branchCode} · ${recap.outlet.branchName}`
                                : '—',
                        },
                        {
                            label: 'Stock location',
                            value: recap.outlet?.locationName ?? '—',
                        },
                        {
                            label: 'Mappings',
                            value: (
                                <ClosingReadinessBadge
                                    ready={recap.ready}
                                    missing={recap.missing.length}
                                />
                            ),
                        },
                    ]}
                />

                {recap.missing.length > 0 && (
                    <section
                        aria-labelledby="closing-gaps"
                        className="grid gap-2 rounded-xl border border-dashed p-4"
                    >
                        <h2 id="closing-gaps" className="text-sm font-medium">
                            Before this recap can be prepared
                        </h2>
                        <ul className="grid gap-1 text-sm">
                            {recap.missing.map((gap) => (
                                <li
                                    key={`${gap.type}:${gap.key}`}
                                    className="flex flex-wrap items-center gap-2"
                                >
                                    <span>{gap.message}</span>
                                    <Link
                                        href={gapHref(gap)}
                                        className="text-xs underline underline-offset-4"
                                    >
                                        Map it
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <ClosingSalesPush
                    recapId={recap.id}
                    sales={sales}
                    canProcess={canProcess}
                    actions={{
                        prepareSales: actions.prepareSales ?? false,
                        sendSales: actions.sendSales ?? false,
                    }}
                />

                <ClosingItemJournals
                    recapId={recap.id}
                    journals={journals}
                    canProcess={canProcess}
                    actions={actions}
                />

                <section aria-labelledby="closing-items" className="grid gap-2">
                    <h2 id="closing-items" className="text-sm font-medium">
                        Items
                    </h2>
                    <DataTable
                        caption="Closing recap items"
                        columns={itemColumns}
                        rows={recap.items}
                        rowKey={(item) => `${item.source}:${item.key}`}
                        empty="This recap has no items."
                    />
                </section>

                <section
                    aria-labelledby="closing-submissions"
                    className="grid gap-2"
                >
                    <h2
                        id="closing-submissions"
                        className="text-sm font-medium"
                    >
                        Received from sync-api
                    </h2>
                    <DataTable
                        caption="Closing recap submissions"
                        columns={submissionColumns}
                        rows={recap.submissions}
                        rowKey={(submission) => submission.id}
                        empty="No submissions."
                    />
                </section>
            </div>
        </>
    );
}
