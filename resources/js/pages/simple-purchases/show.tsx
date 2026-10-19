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
import {
    destroy as destroySimplePurchase,
    index as simplePurchasesIndex,
} from '@/routes/simple-purchases';
import { store as storeDecision } from '@/routes/simple-purchases/decisions';
import type { SimplePurchase } from '@/types/simple-purchase';
import {
    formatAmount,
    formatDate,
    formatDateTime,
    formatQuantity,
} from '@/lib/format';

type Props = {
    simplePurchase: SimplePurchase;
};

// Mirrors DecideSimplePurchaseRequest::REASON_MAX.
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
        title: 'Authorize this simple purchase?',
        description:
            'The purchase is authorized in the ERP straight away. The ERP records the gateway as the approver; the gateway records you.',
    },
    reject: {
        label: 'Reject',
        title: 'Reject this simple purchase?',
        description:
            'The purchase is rejected in the ERP straight away, with the reason below. The ERP records the gateway as the approver; the gateway records you.',
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
                            <Label htmlFor="sp-reject-reason">Reason</Label>
                            <textarea
                                id="sp-reject-reason"
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
        router.delete(destroySimplePurchase(number).url, {
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
                            The simple purchase is deleted in the ERP straight
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

export default function SimplePurchaseShow({ simplePurchase: sp }: Props) {
    const { can } = usePermissions();
    const currencyParts = [
        sp.currencyName,
        sp.currencySign === null ? null : `(${sp.currencySign})`,
    ].filter((part) => part !== null);
    const currency = currencyParts.length > 0 ? currencyParts.join(' ') : null;

    return (
        <>
            <Head title={sp.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={simplePurchasesIndex()}>
                            <ArrowLeft className="size-4" />
                            Simple purchases
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title={sp.number}
                        description="Read live from the ERP."
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        {sp.statusName && (
                            <Badge variant="secondary">{sp.statusName}</Badge>
                        )}
                        {/* Offered on every purchase: which statuses accept a
                            decision or a delete is not documented, so the ERP
                            is left to refuse one it does not accept. */}
                        {can('esb.invoke') &&
                            can('simple-purchases.approve') && (
                                <DecisionControls number={sp.number} />
                            )}
                        {can('esb.invoke') &&
                            can('simple-purchases.manage') && (
                                <DeleteControl number={sp.number} />
                            )}
                    </div>
                </div>

                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Field label="Supplier" value={sp.supplierName} />
                    <Field
                        label="Supplier invoice no."
                        value={sp.supplierInvoiceNumber}
                    />
                    <Field label="Supplier contact" value={sp.contactName} />
                    <Field label="Supplier phone" value={sp.supplierPhone} />
                    <Field label="Branch" value={sp.branchName} />
                    <Field label="Location" value={sp.locationName} />
                    <Field label="Date" value={formatDate(sp.date)} />
                    <Field label="Due date" value={formatDate(sp.dueDate)} />
                    <Field label="Purchase type" value={sp.purchaseTypeName} />
                    <Field
                        label="Payment method"
                        value={sp.paymentMethodName}
                    />
                    <Field label="Currency" value={currency} />
                    <Field
                        label="Rate"
                        value={
                            sp.rate === null ? null : formatQuantity(sp.rate)
                        }
                    />
                    <Field label="Cost center" value={sp.costCenterName} />
                    <Field label="Project" value={sp.projectName} />
                    <Field
                        label="Created"
                        value={byline(sp.createdByName, sp.createdAt)}
                    />
                    <Field
                        label="Last edited"
                        value={byline(sp.editedByName, sp.editedAt)}
                    />
                    <Field
                        label="Authorized"
                        value={byline(sp.authorizedByName, sp.authorizedAt)}
                    />
                </dl>

                {sp.notes && (
                    <div className="grid gap-0.5">
                        <p className="text-muted-foreground text-xs">Notes</p>
                        <p className="text-sm whitespace-pre-line">
                            {sp.notes}
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
                            {sp.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        This simple purchase has no lines.
                                    </TableCell>
                                </TableRow>
                            )}

                            {sp.lines.map((line, index) => (
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

                {sp.costs.length > 0 && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Account</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Notes</TableHead>
                                    <TableHead className="text-right">
                                        Amount
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sp.costs.map((cost, index) => (
                                    <TableRow
                                        key={`${cost.accountNumber}-${index}`}
                                    >
                                        <TableCell className="font-mono text-xs">
                                            {cost.accountNumber}
                                        </TableCell>
                                        <TableCell>
                                            {cost.description ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {cost.notes ?? '—'}
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

                <div className="flex flex-wrap items-start justify-between gap-6">
                    {sp.payments.length > 0 ? (
                        <div className="grid gap-2">
                            <h2 className="text-sm font-medium">Payments</h2>
                            <ul className="grid gap-1 text-sm">
                                {sp.payments.map((payment) => (
                                    <li
                                        key={payment.number}
                                        className="flex flex-wrap items-center gap-2"
                                    >
                                        <span className="font-mono text-xs">
                                            {payment.number}
                                        </span>
                                        {payment.date && (
                                            <span className="text-muted-foreground">
                                                {formatDate(payment.date)}
                                            </span>
                                        )}
                                        {payment.accountName && (
                                            <span className="text-muted-foreground">
                                                {payment.accountName}
                                            </span>
                                        )}
                                        <span className="tabular-nums">
                                            {formatAmount(payment.amount)}
                                        </span>
                                        {payment.statusName && (
                                            <Badge variant="secondary">
                                                {payment.statusName}
                                            </Badge>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : (
                        <div />
                    )}

                    <dl className="ml-auto grid w-full max-w-xs gap-2 text-sm">
                        <div className="flex justify-between gap-4">
                            <dt className="text-muted-foreground">Costs</dt>
                            <dd className="tabular-nums">
                                {formatAmount(sp.costTotal)}
                            </dd>
                        </div>
                        <div className="flex justify-between gap-4 border-t pt-2 font-medium">
                            <dt>Total</dt>
                            <dd className="tabular-nums">
                                {sp.currencySign ? `${sp.currencySign} ` : ''}
                                {formatAmount(sp.total)}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </>
    );
}

SimplePurchaseShow.layout = {
    breadcrumbs: [
        {
            title: 'Simple purchases',
            href: simplePurchasesIndex(),
        },
    ],
};
