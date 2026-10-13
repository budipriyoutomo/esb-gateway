import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
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
    destroy as destroyPurchaseRequest,
    edit as editPurchaseRequest,
    index as purchaseRequestsIndex,
} from '@/routes/purchase-requests';
import { store as storeDecision } from '@/routes/purchase-requests/decisions';

type PurchaseRequestLine = {
    productId: number | null;
    productDetailId: number | null;
    productCode: string;
    productName: string;
    uomName: string | null;
    qty: number;
    notes: string | null;
    stock: number | null;
    suggestionQty: number | null;
    minQty: number | null;
    maxQty: number | null;
    requestProcessId: number | null;
};

type PurchaseRequest = {
    provider: string;
    number: string;
    date: string | null;
    requiredDate: string | null;
    branchName: string | null;
    costCenterName: string | null;
    projectName: string | null;
    isTemplate: boolean;
    statusName: string | null;
    notes: string | null;
    createdByName: string | null;
    createdAt: string | null;
    editedByName: string | null;
    editedAt: string | null;
    authorizedByName: string | null;
    authorizedAt: string | null;
    lines: PurchaseRequestLine[];
    purchaseNumbers: string[];
    transferNumbers: string[];
};

type Props = {
    purchaseRequest: PurchaseRequest;
};

const quantity = new Intl.NumberFormat(undefined, {
    maximumFractionDigits: 4,
});

function formatQty(value: number | null): string {
    return value === null ? '—' : quantity.format(value);
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
        title: 'Authorize this purchase request?',
        description:
            'The request is authorized in the ERP straight away. The ERP records the gateway as the approver; the gateway records you.',
    },
    reject: {
        label: 'Reject',
        title: 'Reject this purchase request?',
        description:
            'The request is rejected in the ERP straight away. The ERP records the gateway as the approver; the gateway records you.',
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
        router.delete(destroyPurchaseRequest(number).url, {
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
                            The purchase request is deleted in the ERP straight
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

export default function PurchaseRequestShow({ purchaseRequest: pr }: Props) {
    const { can } = usePermissions();
    const canManage = can('esb.invoke') && can('purchase-requests.manage');

    return (
        <>
            <Head title={pr.number} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={purchaseRequestsIndex()}>
                            <ArrowLeft className="size-4" />
                            Purchase requests
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title={pr.number}
                        description="Read live from the ERP."
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        {pr.isTemplate && (
                            <Badge variant="outline">Template</Badge>
                        )}
                        {pr.statusName && (
                            <Badge variant="secondary">{pr.statusName}</Badge>
                        )}
                        {/* Offered on every request: which statuses accept a
                            decision is not documented, so the ERP is left to
                            refuse one that does not. */}
                        {can('esb.invoke') &&
                            can('purchase-requests.approve') && (
                                <DecisionControls number={pr.number} />
                            )}
                        {/* Offered on every request for the same reason: the
                            ERP refuses a change it does not accept. */}
                        {canManage && (
                            <>
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={editPurchaseRequest(pr.number)}>
                                        <Pencil className="size-4" />
                                        Edit
                                    </Link>
                                </Button>
                                <DeleteControl number={pr.number} />
                            </>
                        )}
                    </div>
                </div>

                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Field label="Request date" value={pr.date} />
                    <Field label="Required date" value={pr.requiredDate} />
                    <Field label="Branch" value={pr.branchName} />
                    <Field label="Cost center" value={pr.costCenterName} />
                    <Field label="Project" value={pr.projectName} />
                    <Field
                        label="Created"
                        value={byline(pr.createdByName, pr.createdAt)}
                    />
                    <Field
                        label="Last edited"
                        value={byline(pr.editedByName, pr.editedAt)}
                    />
                    <Field
                        label="Authorized"
                        value={byline(pr.authorizedByName, pr.authorizedAt)}
                    />
                </dl>

                {pr.notes && (
                    <div className="grid gap-0.5">
                        <p className="text-muted-foreground text-xs">Notes</p>
                        <p className="text-sm whitespace-pre-line">
                            {pr.notes}
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
                                    Stock
                                </TableHead>
                                <TableHead className="text-right">
                                    Suggested
                                </TableHead>
                                <TableHead>Notes</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pr.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        This purchase request has no lines.
                                    </TableCell>
                                </TableRow>
                            )}

                            {pr.lines.map((line, index) => (
                                // A product can appear on more than one line,
                                // so its id is not a unique key on its own.
                                <TableRow
                                    key={`${line.productDetailId ?? 'x'}-${index}`}
                                >
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
                                    <TableCell className="text-muted-foreground text-right tabular-nums">
                                        {formatQty(line.stock)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-right tabular-nums">
                                        {formatQty(line.suggestionQty)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {line.notes ?? '—'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {(pr.purchaseNumbers.length > 0 ||
                    pr.transferNumbers.length > 0) && (
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <Field
                            label="Purchase documents"
                            value={
                                pr.purchaseNumbers.length > 0
                                    ? pr.purchaseNumbers.join(', ')
                                    : null
                            }
                        />
                        <Field
                            label="Transfer documents"
                            value={
                                pr.transferNumbers.length > 0
                                    ? pr.transferNumbers.join(', ')
                                    : null
                            }
                        />
                    </dl>
                )}
            </div>
        </>
    );
}

PurchaseRequestShow.layout = {
    breadcrumbs: [
        {
            title: 'Purchase requests',
            href: purchaseRequestsIndex(),
        },
    ],
};
