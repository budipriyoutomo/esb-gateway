import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import { show as purchaseInvoicesShow } from '@/routes/purchase-invoices';
import {
    destroy as destroyPurchaseOrder,
    index as purchaseOrdersIndex,
} from '@/routes/purchase-orders';
import { store as storeDecision } from '@/routes/purchase-orders/decisions';
import type {
    PurchaseOrder,
    PurchaseOrderLinkedDocument,
} from '@/types/purchase-order';
import {
    formatAmount,
    formatDate,
    formatDateTime,
    formatQuantity,
} from '@/lib/format';

type Props = {
    purchaseOrder: PurchaseOrder;
};

// Mirrors DecidePurchaseOrderRequest::REASON_MAX.
const REASON_MAX = 500;

const textareaClass =
    'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

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

type Decision = 'authorize' | 'reject';

const DECISION_COPY: Record<
    Decision,
    { label: string; title: string; description: string }
> = {
    authorize: {
        label: 'Authorize',
        title: 'Authorize this purchase order?',
        description:
            'The order is authorized in the ERP straight away. The ERP records the gateway as the approver; the gateway records you.',
    },
    reject: {
        label: 'Reject',
        title: 'Reject this purchase order?',
        description:
            'The order is rejected in the ERP straight away, with the reason below. The ERP records the gateway as the approver; the gateway records you.',
    },
};

function DecisionControls({ number }: { number: string }) {
    const [pending, setPending] = useState<Decision | null>(null);
    const [reason, setReason] = useState('');
    const [error, setError] = useState<string | undefined>(undefined);
    const [processing, setProcessing] = useState(false);

    function close() {
        setPending(null);
        setReason('');
        setError(undefined);
    }

    function submit() {
        if (pending === null) {
            return;
        }

        // The write is not idempotent. Buttons stay disabled until the
        // request finishes, and the server holds a lock per document too.
        router.post(
            storeDecision(number).url,
            pending === 'reject'
                ? { decision: pending, reason }
                : { decision: pending },
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                },
                onSuccess: () => {
                    close();
                },
                onError: (errors) => {
                    // A validation error keeps the dialog open so the reason
                    // can be corrected; nothing was sent to the ERP.
                    setError(errors.reason ?? errors.decision);
                },
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    }

    const copy = pending === null ? null : DECISION_COPY[pending];
    const reasonMissing = pending === 'reject' && reason.trim() === '';

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
                        close();
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

                    {pending === 'reject' && (
                        <div className="grid gap-1.5">
                            <Label htmlFor="po-reject-reason">Reason</Label>
                            <textarea
                                id="po-reject-reason"
                                className={textareaClass}
                                maxLength={REASON_MAX}
                                value={reason}
                                disabled={processing}
                                onChange={(event) => {
                                    setReason(event.target.value);
                                }}
                            />
                            <InputError message={error} />
                        </div>
                    )}

                    {pending === 'authorize' && <InputError message={error} />}

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={close}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant={
                                pending === 'reject' ? 'destructive' : 'default'
                            }
                            disabled={processing || reasonMissing}
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
        router.delete(destroyPurchaseOrder(number).url, {
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
                            The purchase order is deleted in the ERP straight
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
                            <Badge variant="secondary">
                                {document.statusName}
                            </Badge>
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
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={purchaseOrdersIndex()}>
                            <ArrowLeft className="size-4" />
                            Purchase orders
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title={po.number}
                        description="Read live from the ERP."
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        {po.statusName && (
                            <Badge variant="secondary">{po.statusName}</Badge>
                        )}
                        {/* Offered on every order: which statuses accept a
                            decision is not documented, so the ERP is left to
                            refuse one it does not accept. */}
                        {can('esb.invoke') &&
                            can('purchase-orders.approve') && (
                                <DecisionControls number={po.number} />
                            )}
                        {/* Hidden only when the ERP itself says the order
                            cannot be deleted; unknown is left to the ERP. */}
                        {can('esb.invoke') &&
                            can('purchase-orders.manage') &&
                            po.canDelete !== false && (
                                <DeleteControl number={po.number} />
                            )}
                    </div>
                </div>

                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Field
                        label="Supplier"
                        value={supplier === '' ? null : supplier}
                    />
                    <Field label="Supplier contact" value={po.contactName} />
                    <Field label="Supplier phone" value={po.supplierPhone} />
                    <Field
                        label="Supplier address"
                        value={po.supplierAddress}
                    />
                    <Field label="Branch" value={po.branchName} />
                    <Field label="Order date" value={formatDate(po.date)} />
                    <Field
                        label="Required date"
                        value={formatDate(po.requiredDate)}
                    />
                    <Field label="Purchase type" value={po.purchaseTypeName} />
                    <Field
                        label="Payment terms"
                        value={
                            po.dueDays === null ? null : `${po.dueDays} days`
                        }
                    />
                    <Field label="Currency" value={currency} />
                    <Field
                        label="Rate"
                        value={
                            po.rate === null ? null : formatQuantity(po.rate)
                        }
                    />
                    <Field label="Cost center" value={po.costCenterName} />
                    <Field label="Project" value={po.projectName} />
                    <Field
                        label="Purchase requests"
                        value={po.purchaseRequestNumbers}
                    />
                    <Field
                        label="Created"
                        value={byline(po.createdByName, po.createdAt)}
                    />
                    <Field
                        label="Last edited"
                        value={byline(po.editedByName, po.editedAt)}
                    />
                    <Field
                        label="Authorized"
                        value={byline(po.authorizedByName, po.authorizedAt)}
                    />
                </dl>

                {po.notes && (
                    <div className="grid gap-0.5">
                        <p className="text-muted-foreground text-xs">Notes</p>
                        <p className="text-sm whitespace-pre-line">
                            {po.notes}
                        </p>
                    </div>
                )}

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead className="text-right">
                                    Requested
                                </TableHead>
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
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {po.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        This purchase order has no lines.
                                    </TableCell>
                                </TableRow>
                            )}

                            {po.lines.map((line, index) => (
                                // A product can appear on more than one line,
                                // so its id is not a unique key on its own.
                                <TableRow
                                    key={`${line.productDetailId ?? 'x'}-${index}`}
                                >
                                    <TableCell className="font-mono text-xs">
                                        {line.productCode || '—'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium">
                                            {line.productName || '—'}
                                        </div>
                                        {line.notes && (
                                            <div className="text-muted-foreground text-xs">
                                                {line.notes}
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-right tabular-nums">
                                        {formatQuantity(line.requestedQty)}
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
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(line.total)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

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
                                {po.currencySign ? `${po.currencySign} ` : ''}
                                {formatAmount(po.total)}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </>
    );
}

PurchaseOrderShow.layout = {
    breadcrumbs: [
        {
            title: 'Purchase orders',
            href: purchaseOrdersIndex(),
        },
    ],
};
