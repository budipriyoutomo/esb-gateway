import { Head } from '@inertiajs/react';
import { ApprovalActions } from '@/components/approval-actions';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import {
    destroy as destroyPurchaseInvoice,
    index as purchaseInvoicesIndex,
} from '@/routes/purchase-invoices';
import { store as storeDecision } from '@/routes/purchase-invoices/decisions';
import {
    formatAmount,
    formatDate,
    formatDateTime,
    formatQuantity,
} from '@/lib/format';

type PurchaseInvoiceLine = {
    productDetailId: number | null;
    productCode: string;
    productName: string;
    referenceNumber: string | null;
    referenceDate: string | null;
    purchaseOrderNumber: string | null;
    qty: number | null;
    uomName: string | null;
    price: number | null;
    discount: number | null;
    discountPercent: number | null;
    taxName: string | null;
    total: number | null;
    costCenterName: string | null;
    projectName: string | null;
};

type PurchaseInvoiceCost = {
    accountNumber: string;
    accountName: string | null;
    amount: number | null;
};

type PurchaseInvoice = {
    provider: string;
    number: string;
    supplierInvoiceNumber: string | null;
    taxInvoiceNumber: string | null;
    date: string | null;
    dueDate: string | null;
    dueDays: number | null;
    referenceDate: string | null;
    branchName: string | null;
    supplierName: string | null;
    currencyName: string | null;
    currencySign: string | null;
    rate: number | null;
    referenceTotal: number | null;
    costTotal: number | null;
    total: number | null;
    statusName: string | null;
    notes: string | null;
    createdByName: string | null;
    createdAt: string | null;
    editedByName: string | null;
    editedAt: string | null;
    authorizedByName: string | null;
    authorizedAt: string | null;
    lines: PurchaseInvoiceLine[];
    costs: PurchaseInvoiceCost[];
};

type Props = {
    purchaseInvoice: PurchaseInvoice;
};

function byline(name: string | null, at: string | null): string {
    if (name === null && at === null) {
        return '—';
    }

    return [name, at === null ? null : formatDateTime(at)]
        .filter((part) => part !== null)
        .join(' · ');
}

function Field({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm">{value ?? '—'}</dd>
        </div>
    );
}

export default function PurchaseInvoiceShow({ purchaseInvoice: pi }: Props) {
    const { can } = usePermissions();
    const currencyParts = [
        pi.currencyName,
        pi.currencySign === null ? null : `(${pi.currencySign})`,
    ].filter((part) => part !== null);
    const currency = currencyParts.length > 0 ? currencyParts.join(' ') : null;

    return (
        <>
            <Head title={pi.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={pi.number}
                    description="Read live from the ERP."
                    back={{
                        href: purchaseInvoicesIndex(),
                        label: 'Purchase invoices',
                    }}
                    actions={
                        <>
                            <StatusBadge status={pi.statusName} />
                            {/* Offered on every document: which statuses accept a
                                decision or a delete is not documented, so the ERP
                                is left to refuse one it does not accept. */}
                            <ApprovalActions
                                documentLabel="purchase invoice"
                                number={pi.number}
                                decisionUrl={storeDecision(pi.number).url}
                                deleteUrl={
                                    destroyPurchaseInvoice(pi.number).url
                                }
                                canDecide={
                                    can('esb.invoke') &&
                                    can('purchase-invoices.approve')
                                }
                                canDelete={
                                    can('esb.invoke') &&
                                    can('purchase-invoices.manage')
                                }
                            />
                        </>
                    }
                />

                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Field label="Supplier" value={pi.supplierName} />
                    <Field
                        label="Supplier invoice no."
                        value={pi.supplierInvoiceNumber}
                    />
                    <Field
                        label="Tax invoice no."
                        value={pi.taxInvoiceNumber}
                    />
                    <Field label="Branch" value={pi.branchName} />
                    <Field label="Invoice date" value={formatDate(pi.date)} />
                    <Field label="Due date" value={formatDate(pi.dueDate)} />
                    <Field
                        label="Payment terms"
                        value={
                            pi.dueDays === null ? null : `${pi.dueDays} days`
                        }
                    />
                    <Field
                        label="Reference date"
                        value={formatDate(pi.referenceDate)}
                    />
                    <Field label="Currency" value={currency} />
                    <Field
                        label="Rate"
                        value={
                            pi.rate === null ? null : formatQuantity(pi.rate)
                        }
                    />
                    <Field
                        label="Created"
                        value={byline(pi.createdByName, pi.createdAt)}
                    />
                    <Field
                        label="Last edited"
                        value={byline(pi.editedByName, pi.editedAt)}
                    />
                    <Field
                        label="Authorized"
                        value={byline(pi.authorizedByName, pi.authorizedAt)}
                    />
                </dl>

                {pi.notes && (
                    <div className="grid gap-0.5">
                        <p className="text-muted-foreground text-xs">Notes</p>
                        <p className="text-sm whitespace-pre-line">
                            {pi.notes}
                        </p>
                    </div>
                )}

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Reference</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead className="text-right">
                                    Qty
                                </TableHead>
                                <TableHead>UoM</TableHead>
                                <TableHead className="text-right">
                                    Price
                                </TableHead>
                                <TableHead className="text-right">
                                    Discount
                                </TableHead>
                                <TableHead>Tax</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pi.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={9}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        This purchase invoice has no lines.
                                    </TableCell>
                                </TableRow>
                            )}

                            {pi.lines.map((line, index) => (
                                // A product can appear on more than one line,
                                // so its id is not a unique key on its own.
                                <TableRow
                                    key={`${line.productDetailId ?? 'x'}-${index}`}
                                >
                                    <TableCell className="font-mono text-xs">
                                        <div>{line.referenceNumber ?? '—'}</div>
                                        {line.purchaseOrderNumber && (
                                            <div className="text-muted-foreground">
                                                {line.purchaseOrderNumber}
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {line.productCode || '—'}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {line.productName || '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatQuantity(line.qty)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {line.uomName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(line.price)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-right tabular-nums">
                                        {line.discount
                                            ? formatAmount(line.discount)
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {line.taxName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(line.total)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {pi.costs.length > 0 && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Account</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pi.costs.map((cost, index) => (
                                    <TableRow
                                        key={`${cost.accountNumber}-${index}`}
                                    >
                                        <TableCell className="font-mono text-xs">
                                            {cost.accountNumber}
                                        </TableCell>
                                        <TableCell>
                                            {cost.accountName ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatAmount(cost.amount)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <dl className="ml-auto grid w-full max-w-xs gap-2 text-sm">
                    <div className="flex justify-between gap-4">
                        <dt className="text-muted-foreground">
                            Reference total
                        </dt>
                        <dd className="tabular-nums">
                            {formatAmount(pi.referenceTotal)}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-muted-foreground">Costs</dt>
                        <dd className="tabular-nums">
                            {formatAmount(pi.costTotal)}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4 border-t pt-2 font-medium">
                        <dt>Invoice total</dt>
                        <dd className="tabular-nums">
                            {pi.currencySign ? `${pi.currencySign} ` : ''}
                            {formatAmount(pi.total)}
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}
