import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import AlertError from '@/components/alert-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { resolve } from '@/routes/closing-item-journals';
import { prepare, send } from '@/routes/closing-recaps';
import type { ClosingStockKind } from '@/types/closing';

export type ClosingJournalStatus =
    | 'prepared'
    | 'superseded'
    | 'created'
    | 'failed'
    | 'unconfirmed'
    | 'deleted'
    | 'delete_refused'
    | 'delete_unconfirmed';

export type ClosingJournal = {
    id: string;
    kind: ClosingStockKind;
    label: string;
    sequence: number;
    current: boolean;
    purposeName: string;
    direction: number;
    status: ClosingJournalStatus;
    number: string | null;
    notes: string;
    lines: {
        productDetailId: number;
        productCode: string | null;
        productName: string;
        uomName: string | null;
        qty: string;
    }[];
    breakdown: {
        item: string;
        quantity: string;
        bomId: number;
        bomName: string;
    }[];
    errors: { field: string | null; message: string }[];
    reference: string | null;
    sentAt: string | null;
    canResolve: boolean;
};

const STATUS_TEXT: Record<
    ClosingJournalStatus,
    {
        label: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
    }
> = {
    prepared: { label: 'Prepared', variant: 'secondary' },
    superseded: { label: 'Superseded', variant: 'outline' },
    created: { label: 'In the ERP', variant: 'default' },
    failed: { label: 'Refused', variant: 'destructive' },
    unconfirmed: { label: 'Check in the ERP', variant: 'destructive' },
    deleted: { label: 'Deleted (revised)', variant: 'outline' },
    delete_refused: { label: 'Delete refused', variant: 'destructive' },
    delete_unconfirmed: {
        label: 'Check delete in the ERP',
        variant: 'destructive',
    },
};

type Props = {
    recapId: string;
    journals: ClosingJournal[];
    canProcess: boolean;
    actions: { prepare: boolean; send: boolean };
};

/**
 * A closing recap's item journals (ADR-032, P28): prepared from the current
 * version, sent by an operator, and those of an earlier version that a
 * revision deletes.
 */
export function ClosingItemJournals({
    recapId,
    journals,
    canProcess,
    actions,
}: Props) {
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [resolving, setResolving] = useState<ClosingJournal | null>(null);

    function post(url: string) {
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => {
                    setBusy(true);
                    setError(null);
                },
                onError: (errors) => {
                    setError(errors.recap ?? Object.values(errors)[0] ?? null);
                },
                onFinish: () => {
                    setBusy(false);
                },
            },
        );
    }

    return (
        <section aria-labelledby="closing-journals" className="grid gap-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 id="closing-journals" className="text-sm font-medium">
                    Item journals
                </h2>
                {canProcess && (
                    <div className="flex gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={busy || !actions.prepare}
                            onClick={() => {
                                post(prepare(recapId).url);
                            }}
                        >
                            Prepare
                        </Button>
                        <Button
                            size="sm"
                            disabled={busy || !actions.send}
                            onClick={() => {
                                if (
                                    window.confirm(
                                        'Send the prepared item journals to the ERP? Journals of an earlier version are deleted there first.',
                                    )
                                ) {
                                    post(send(recapId).url);
                                }
                            }}
                        >
                            Send to ERP
                        </Button>
                    </div>
                )}
            </div>

            {error && <AlertError errors={[error]} />}

            {journals.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    Nothing prepared yet. Waste, adjustment and compensation
                    become one item journal each, expanded through each
                    menu&apos;s bill of material.
                </p>
            ) : (
                <ul className="grid gap-3">
                    {journals.map((journal) => (
                        <JournalCard
                            key={journal.id}
                            journal={journal}
                            onResolve={() => {
                                setResolving(journal);
                            }}
                        />
                    ))}
                </ul>
            )}

            {resolving && (
                <ResolveDialog
                    journal={resolving}
                    onClose={() => {
                        setResolving(null);
                    }}
                />
            )}
        </section>
    );
}

function JournalCard({
    journal,
    onResolve,
}: {
    journal: ClosingJournal;
    onResolve: () => void;
}) {
    const status = STATUS_TEXT[journal.status];

    return (
        <li
            aria-label={`${journal.label} item journal, sequence ${journal.sequence}`}
            className="grid gap-2 rounded-xl border p-4"
        >
            <div className="flex flex-wrap items-center gap-2">
                <span className="font-medium">{journal.label}</span>
                <Badge variant={status.variant}>{status.label}</Badge>
                {!journal.current && (
                    <Badge variant="outline">Sequence {journal.sequence}</Badge>
                )}
                <span className="text-muted-foreground text-xs">
                    {journal.purposeName}
                    {journal.direction < 0 ? ' · stock out' : ' · stock in'}
                    {journal.number ? ` · ${journal.number}` : ''}
                    {journal.sentAt
                        ? ` · sent ${formatDateTime(journal.sentAt)}`
                        : ''}
                </span>
                {journal.canResolve && (
                    <Button
                        size="sm"
                        variant="outline"
                        className="ml-auto"
                        onClick={onResolve}
                    >
                        Settle
                    </Button>
                )}
            </div>

            {journal.errors.length > 0 && (
                <ul className="text-destructive grid gap-0.5 text-xs">
                    {journal.errors.map((error, index) => (
                        <li key={index}>
                            {error.field ? `${error.field}: ` : ''}
                            {error.message}
                        </li>
                    ))}
                    {journal.reference && (
                        <li className="text-muted-foreground">
                            Reference {journal.reference}
                        </li>
                    )}
                </ul>
            )}

            <table className="w-full text-sm">
                <caption className="sr-only">
                    {journal.label} lines, sequence {journal.sequence}
                </caption>
                <thead className="text-muted-foreground text-xs">
                    <tr>
                        <th className="text-left font-normal">Material</th>
                        <th className="text-right font-normal">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    {journal.lines.map((line) => (
                        <tr key={line.productDetailId}>
                            <td>
                                {line.productName}
                                {line.productCode && (
                                    <span className="text-muted-foreground text-xs">
                                        {' '}
                                        · {line.productCode}
                                    </span>
                                )}
                            </td>
                            <td className="text-right tabular-nums">
                                {line.qty} {line.uomName ?? ''}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {journal.breakdown.length > 0 && (
                <p className="text-muted-foreground text-xs">
                    From{' '}
                    {journal.breakdown
                        .map(
                            (part) =>
                                `${part.item} × ${part.quantity} (${part.bomName})`,
                        )
                        .join(', ')}
                </p>
            )}
        </li>
    );
}

function ResolveDialog({
    journal,
    onClose,
}: {
    journal: ClosingJournal;
    onClose: () => void;
}) {
    const isDelete = journal.status === 'delete_unconfirmed';
    const [number, setNumber] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    function settle(outcome: string) {
        router.post(
            resolve(journal.id).url,
            { outcome, itemJournalNum: number },
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                },
                onError: (next) => {
                    setErrors(next);
                },
                onSuccess: onClose,
                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        settle('created');
    }

    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !processing) onClose();
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Settle the {journal.label.toLowerCase()} item journal
                    </DialogTitle>
                    <DialogDescription>
                        {isDelete
                            ? `The ERP did not confirm deleting ${journal.number}. Look it up in the ERP, then say what you found.`
                            : 'The ERP did not confirm this item journal. Look for it in the ERP (its notes start with “Closing”), then say what you found.'}
                    </DialogDescription>
                </DialogHeader>

                {isDelete ? (
                    <DialogFooter>
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => {
                                settle('not_deleted');
                            }}
                        >
                            It is still there
                        </Button>
                        <Button
                            disabled={processing}
                            onClick={() => {
                                settle('deleted');
                            }}
                        >
                            It is gone
                        </Button>
                    </DialogFooter>
                ) : (
                    <form onSubmit={submit} className="grid gap-4" noValidate>
                        <div className="grid gap-2">
                            <Label htmlFor="resolve-number">
                                Item journal number in the ERP
                            </Label>
                            <Input
                                id="resolve-number"
                                value={number}
                                onChange={(event) => {
                                    setNumber(event.target.value);
                                }}
                            />
                            {(errors.itemJournalNum ?? errors.outcome) && (
                                <p className="text-destructive text-xs">
                                    {errors.itemJournalNum ?? errors.outcome}
                                </p>
                            )}
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={processing}
                                onClick={() => {
                                    settle('not_created');
                                }}
                            >
                                It was not created
                            </Button>
                            <Button type="submit" disabled={processing}>
                                It was created
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}
