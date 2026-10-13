import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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

const quantity = new Intl.NumberFormat(undefined, {
    maximumFractionDigits: 4,
});

const money = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function formatQty(value: number | null): string {
    return value === null ? '—' : quantity.format(value);
}

// Display only: the ERP is the authority on every amount.
function formatMoney(value: number | null): string {
    return value === null ? '—' : money.format(value);
}

function byline(name: string | null, at: string | null): string {
    if (name === null && at === null) {
        return '—';
    }

    return [name, at].filter((part) => part !== null).join(' · ');
}

function Field({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="grid gap-0.5">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="text-sm">{value ?? '—'}</dd>
        </div>
    );
}

type Decision = 'authorize' | 'reject';

const DECISION_COPY: Record<
    Decision,
    { label: string; title: string; description: string }
> = {
    authorize: {
        label: 'Authorize',
        title: 'Authorize this purchase invoice?',
        description:
            'The invoice is authorized in the ERP straight away. The ERP records the gateway as the approver; the gateway records you.',
    },
    reject: {
        label: 'Reject',
        title: 'Reject this purchase invoice?',
        description:
            'The invoice is rejected in the ERP straight away. The ERP records the gateway as the approver; the gateway records you.',
    },
};

function DecisionControls({ number }: { number: string }) {
    const [pending, setPending] = useState<Decision | null>(null);
    const [processing, setProcessing] = useState(false);

    function submit() {
        if (pending === null) {
            return;
        }

        // The write is not idempotent. Buttons stay disabled until the
        // request finishes, and the server holds a lock per document too.
        router.post(
            storeDecision(number).url,
            { decision: pending },
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                },
                onFinish: () => {
                    setProcessing(false);
                    setPending(null);
                },
            },
        );
    }

    const copy = pending === null ? null : DECISION_COPY[pending];

    return (
        <>
            <div className="flex gap-2">
                <Button
                    size="sm"
                    disabled={processing}
                    onClick={() => {
                        setPending('authorize');
                    }}
                >
                    Authorize
                </Button>
                <Button
                    size="sm"
                    variant="destructive"
                    disabled={processing}
                    onClick={() => {
                        setPending('reject');
                    }}
                >
                    Reject
                </Button>
            </div>

            <Dialog
                open={pending !== null}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        setPending(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{copy?.title}</DialogTitle>
                        <DialogDescription>
                            {copy?.description}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                setPending(null);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant={
                                pending === 'reject' ? 'destructive' : 'default'
                            }
                            disabled={processing}
                            onClick={submit}
                        >
                            {processing ? 'Sending…' : copy?.label}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function DeleteControl({ number }: { number: string }) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    function submit() {
        // Not idempotent either; the server holds the same per-document lock
        // as for a decision.
        router.delete(destroyPurchaseInvoice(number).url, {
            onStart: () => {
                setProcessing(true);
            },
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    }

    return (
        <>
            <Button
                size="sm"
                variant="outline"
                disabled={processing}
                onClick={() => {
                    setOpen(true);
                }}
            >
                Delete
            </Button>

            <Dialog
                open={open}
                onOpenChange={(next) => {
                    if (!processing) {
                        setOpen(next);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete {number}?</DialogTitle>
                        <DialogDescription>
                            The purchase invoice is deleted in the ERP straight
                            away. This cannot be undone from the gateway.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                setOpen(false);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={submit}
                        >
                            {processing ? 'Deleting…' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
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
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={purchaseInvoicesIndex()}>
                            <ArrowLeft className="size-4" />
                            Purchase invoices
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title={pi.number}
                        description="Read live from the ERP."
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        {pi.statusName && (
                            <Badge variant="secondary">{pi.statusName}</Badge>
                        )}
                        {/* Offered on every invoice: which statuses accept a
                            decision or a delete is not documented, so the ERP
                            is left to refuse one it does not accept. */}
                        {can('esb.invoke') &&
                            can('purchase-invoices.approve') && (
                                <DecisionControls number={pi.number} />
                            )}
                        {can('esb.invoke') &&
                            can('purchase-invoices.manage') && (
                                <DeleteControl number={pi.number} />
                            )}
                    </div>
                </div>

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
                    <Field label="Invoice date" value={pi.date} />
                    <Field label="Due date" value={pi.dueDate} />
                    <Field
                        label="Payment terms"
                        value={
                            pi.dueDays === null ? null : `${pi.dueDays} days`
                        }
                    />
                    <Field label="Reference date" value={pi.referenceDate} />
                    <Field label="Currency" value={currency} />
                    <Field
                        label="Rate"
                        value={
                            pi.rate === null ? null : quantity.format(pi.rate)
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
                                        {formatQty(line.qty)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {line.uomName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatMoney(line.price)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-right tabular-nums">
                                        {line.discount
                                            ? formatMoney(line.discount)
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {line.taxName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatMoney(line.total)}
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
                                            {formatMoney(cost.amount)}
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
                            {formatMoney(pi.referenceTotal)}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-muted-foreground">Costs</dt>
                        <dd className="tabular-nums">
                            {formatMoney(pi.costTotal)}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4 border-t pt-2 font-medium">
                        <dt>Invoice total</dt>
                        <dd className="tabular-nums">
                            {pi.currencySign ? `${pi.currencySign} ` : ''}
                            {formatMoney(pi.total)}
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

PurchaseInvoiceShow.layout = {
    breadcrumbs: [
        {
            title: 'Purchase invoices',
            href: purchaseInvoicesIndex(),
        },
    ],
};
