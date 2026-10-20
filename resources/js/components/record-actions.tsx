import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Operation = 'deactivate' | 'restore' | 'delete';

type Props = {
    /** Lower-case kind, e.g. "product category". */
    kind: string;
    /** The record's name, shown in the dialog and the result message. */
    name: string;
    /** Current status; null when the ERP did not say. */
    active: boolean | null;
    /** esb.invoke + the module's manage permission, checked by the page. */
    canManage: boolean;
    /** The routes this module supports; an absent one is not offered. */
    urls: Partial<Record<Operation, string>>;
    /** Extra sentence for a confirmation, e.g. what else the ERP changes. */
    warnings?: Partial<Record<Operation, string>>;
    /** The record's edit form (S3), when the module has one. */
    editUrl?: string;
};

function copy(operation: Operation, kind: string, name: string) {
    switch (operation) {
        case 'deactivate':
            return {
                action: 'Deactivate',
                title: `Deactivate ${name}?`,
                description: `The ${kind} is set inactive in the ERP straight away and can no longer be chosen on new documents. It can be restored later.`,
            };
        case 'restore':
            return {
                action: 'Restore',
                title: `Restore ${name}?`,
                description: `The ${kind} is made active again in the ERP straight away.`,
            };
        case 'delete':
            return {
                action: 'Delete',
                title: `Delete ${name}?`,
                description: `The ${kind} is deleted in the ERP straight away. The ERP refuses if it is still in use. This cannot be undone from the gateway.`,
            };
    }
}

/**
 * Edit, then deactivate / restore / delete for a master data record, each
 * state change behind a confirmation. Writes are not idempotent: every control is disabled while
 * one is in flight, and the server holds a lock per record too. The outcome
 * arrives as a flash toast after the redirect.
 */
export function RecordActions({
    kind,
    name,
    active,
    canManage,
    urls,
    warnings = {},
    editUrl,
}: Props) {
    const [pending, setPending] = useState<Operation | null>(null);
    const [processing, setProcessing] = useState(false);

    if (!canManage) {
        return null;
    }

    const offered: Operation[] = (
        ['deactivate', 'restore', 'delete'] as Operation[]
    ).filter((operation) => {
        if (urls[operation] === undefined) {
            return false;
        }

        if (operation === 'deactivate') {
            return active !== false;
        }

        if (operation === 'restore') {
            return active !== true;
        }

        return true;
    });

    if (offered.length === 0) {
        return null;
    }

    function submit() {
        const url = pending === null ? undefined : urls[pending];

        if (pending === null || url === undefined) {
            return;
        }

        const options = {
            preserveScroll: true,
            onStart: () => {
                setProcessing(true);
            },
            onFinish: () => {
                setProcessing(false);
                setPending(null);
            },
        };

        if (pending === 'delete') {
            router.delete(url, { ...options, data: { name } });
        } else {
            router.patch(url, { name }, options);
        }
    }

    const current = pending === null ? null : copy(pending, kind, name);

    return (
        <>
            {editUrl !== undefined && (
                <Button size="sm" variant="outline" asChild>
                    <Link href={editUrl}>Edit</Link>
                </Button>
            )}
            {offered.map((operation) => (
                <Button
                    key={operation}
                    size="sm"
                    variant={operation === 'delete' ? 'destructive' : 'outline'}
                    disabled={processing}
                    onClick={() => {
                        setPending(operation);
                    }}
                >
                    {copy(operation, kind, name).action}
                </Button>
            ))}

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
                        <DialogTitle>{current?.title}</DialogTitle>
                        <DialogDescription>
                            {current?.description}
                            {pending !== null && warnings[pending] && (
                                <> {warnings[pending]}</>
                            )}
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
                                pending === 'delete' ? 'destructive' : 'default'
                            }
                            disabled={processing}
                            onClick={submit}
                        >
                            {processing ? 'Sending…' : current?.action}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
