import { Head, Link } from '@inertiajs/react';
import { ApprovalActions } from '@/components/approval-actions';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import {
    DetailField,
    DetailFields,
    DetailSummary,
} from '@/components/detail-fields';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { usePermissions } from '@/hooks/use-permissions';
import {
    formatAmount,
    formatDate,
    formatDateTime,
    formatQuantity,
} from '@/lib/format';
import { show as purchaseInvoicesShow } from '@/routes/purchase-invoices';
import {
    destroy as destroyPurchaseOrder,
    index as purchaseOrdersIndex,
} from '@/routes/purchase-orders';
import { store as storeDecision } from '@/routes/purchase-orders/decisions';
import type {
    PurchaseOrder,
    PurchaseOrderLine,
    PurchaseOrderLinkedDocument,
} from '@/types/purchase-order';

type Props = {
    purchaseOrder: PurchaseOrder;
};

// Mirrors DecidePurchaseOrderRequest::REASON_MAX.
const REASON_MAX = 500;

function byline(name: string | null, at: string | null): string {
    if (name === null && at === null) {
        return '—';
    }

    return [name, at === null ? null : formatDateTime(at)]
        .filter((part) => part !== null)
        .join(' · ');
}

const lineColumns: DataTableColumn<PurchaseOrderLine>[] = [
    {
        key: 'code',
        header: 'Code',
        cell: (line) => line.productCode || '—',
        className: 'font-mono text-xs',
    },
    {
        key: 'product',
        header: 'Product',
        cell: (line) => (
            <>
                <div className="font-medium">{line.productName || '—'}</div>
                {line.notes && (
                    <div className="text-muted-foreground text-xs">
                        {line.notes}
                    </div>
                )}
            </>
        ),
    },
    {
        key: 'requested',
        header: 'Requested',
        cell: (line) => formatQuantity(line.requestedQty),
        align: 'right',
        className: 'text-muted-foreground',
    },
    {
        key: 'qty',
        header: 'Qty',
        cell: (line) => formatQuantity(line.qty),
        align: 'right',
    },
    {
        key: 'uom',
        header: 'UoM',
        cell: (line) => line.uomName ?? '—',
        className: 'text-muted-foreground',
    },
    {
        key: 'price',
        header: 'Price',
        cell: (line) => formatAmount(line.price),
        align: 'right',
    },
    {
        key: 'discount',
        header: 'Discount',
        cell: (line) => (line.discount ? formatAmount(line.discount) : '—'),
        align: 'right',
        className: 'text-muted-foreground',
    },
    {
        key: 'total',
        header: 'Total',
        cell: (line) => formatAmount(line.total),
        align: 'right',
    },
];

function LinkedDocuments({
    title,
    documents,
    linkInvoices,
}: {
    title: string;
    documents: PurchaseOrderLinkedDocument[];
    linkInvoices?: boolean;
}) {
    if (documents.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-2">
            <h2 className="text-sm font-medium">{title}</h2>
            <ul className="grid gap-1 text-sm">
                {documents.map((document) => (
                    <li
                        key={document.number}
                        className="flex flex-wrap items-center gap-2"
                    >
                        {linkInvoices ? (
                            <Link
                                href={purchaseInvoicesShow(document.number)}
                                className="font-mono text-xs underline-offset-4 hover:underline"
                            >
                                {document.number}
                            </Link>
                        ) : (
                            <span className="font-mono text-xs">
                                {document.number}
                            </span>
                        )}
                        {document.date && (
                            <span className="text-muted-foreground">
                                {formatDate(document.date)}
                            </span>
                        )}
                        {document.total !== null && (
                            <span className="tabular-nums">
                                {formatAmount(document.total)}
                            </span>
                        )}
                        {document.statusName && (
                            <StatusBadge status={document.statusName} />
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}

export default function PurchaseOrderShow({ purchaseOrder: po }: Props) {
    const { can } = usePermissions();
    const currencyParts = [
        po.currencyName,
        po.currencySign === null ? null : `(${po.currencySign})`,
    ].filter((part) => part !== null);
    const currency = currencyParts.length > 0 ? currencyParts.join(' ') : null;
    const supplier = [po.supplierName, po.supplierBranchName]
        .filter((part) => part !== null)
        .join(' — ');

    return (
        <>
            <Head title={po.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={po.number}
                    description="Read live from the ERP."
                    back={{
                        href: purchaseOrdersIndex(),
                        label: 'Purchase orders',
                    }}
                    actions={
                        <>
                            <StatusBadge status={po.statusName} />
                            {/* Decisions are offered on every order: which
                                statuses accept one is not documented, so the
                                ERP is left to refuse one it does not accept.
                                Delete is hidden only when the ERP itself says
                                the order cannot be deleted. */}
                            <ApprovalActions
                                documentLabel="purchase order"
                                number={po.number}
                                decisionUrl={storeDecision(po.number).url}
                                deleteUrl={destroyPurchaseOrder(po.number).url}
                                canDecide={
                                    can('esb.invoke') &&
                                    can('purchase-orders.approve')
                                }
                                canDelete={
                                    can('esb.invoke') &&
                                    can('purchase-orders.manage') &&
                                    po.canDelete !== false
                                }
                                rejectReason={{ max: REASON_MAX }}
                            />
                        </>
                    }
                />

                <DetailSummary
                    label="Purchase order summary"
                    items={[
                        { label: 'Supplier', value: po.supplierName },
                        { label: 'Order date', value: formatDate(po.date) },
                        {
                            label: 'Required date',
                            value: formatDate(po.requiredDate),
                        },
                        {
                            label: 'Order total',
                            value: formatAmount(po.total, po.currencySign),
                            align: 'right',
                        },
                    ]}
                />

                <DetailFields>
                    <DetailField
                        label="Supplier"
                        value={supplier === '' ? null : supplier}
                    />
                    <DetailField
                        label="Supplier contact"
                        value={po.contactName}
                    />
                    <DetailField
                        label="Supplier phone"
                        value={po.supplierPhone}
                    />
                    <DetailField
                        label="Supplier address"
                        value={po.supplierAddress}
                    />
                    <DetailField label="Branch" value={po.branchName} />
                    <DetailField
                        label="Purchase type"
                        value={po.purchaseTypeName}
                    />
                    <DetailField
                        label="Payment terms"
                        value={
                            po.dueDays === null ? null : `${po.dueDays} days`
                        }
                    />
                    <DetailField label="Currency" value={currency} />
                    <DetailField
                        label="Rate"
                        value={
                            po.rate === null ? null : formatQuantity(po.rate)
                        }
                    />
                    <DetailField
                        label="Cost center"
                        value={po.costCenterName}
                    />
                    <DetailField label="Project" value={po.projectName} />
                    <DetailField
                        label="Purchase requests"
                        value={po.purchaseRequestNumbers}
                    />
                    <DetailField
                        label="Created"
                        value={byline(po.createdByName, po.createdAt)}
                    />
                    <DetailField
                        label="Last edited"
                        value={byline(po.editedByName, po.editedAt)}
                    />
                    <DetailField
                        label="Authorized"
                        value={byline(po.authorizedByName, po.authorizedAt)}
                    />
                </DetailFields>

                {po.notes && (
                    <div className="grid gap-0.5">
                        <p className="text-muted-foreground text-xs">Notes</p>
                        <p className="text-sm whitespace-pre-line">
                            {po.notes}
                        </p>
                    </div>
                )}

                <DataTable
                    caption="Purchase order lines"
                    columns={lineColumns}
                    rows={po.lines}
                    // A product can appear on more than one line, so its id
                    // is not a unique key on its own.
                    rowKey={(line, index) =>
                        `${line.productDetailId ?? 'x'}-${index}`
                    }
                    empty="This purchase order has no lines."
                />

                <div className="flex flex-wrap items-start justify-between gap-6">
                    <div className="grid gap-4">
                        <LinkedDocuments
                            title="Goods receipts"
                            documents={po.goodsReceipts}
                        />
                        <LinkedDocuments
                            title="Purchase invoices"
                            documents={po.purchaseInvoices}
                            linkInvoices
                        />
                        {po.footNote && (
                            <div className="grid gap-0.5">
                                <p className="text-muted-foreground text-xs">
                                    Footnote
                                </p>
                                <p className="text-sm whitespace-pre-line">
                                    {po.footNote}
                                </p>
                            </div>
                        )}
                    </div>

                    <dl className="ml-auto grid w-full max-w-xs gap-2 text-sm">
                        <div className="flex justify-between gap-4 border-t pt-2 font-medium">
                            <dt>Order total</dt>
                            <dd className="tabular-nums">
                                {formatAmount(po.total, po.currencySign)}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </>
    );
}
