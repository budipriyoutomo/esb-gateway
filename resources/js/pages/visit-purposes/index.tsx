import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { destroy, store, update } from '@/routes/visit-purposes';
import type { UnregisteredVisitPurpose, VisitPurpose } from '@/types/pos';

type Props = {
    canManage: boolean;
    visitPurposes: VisitPurpose[];
    unregistered: UnregisteredVisitPurpose[];
};

/** null: closed; `{ name }` only: adding; with id: editing. */
type Editing = Partial<VisitPurpose> & { name: string };

/**
 * Visit purposes kept in the gateway ([ESB-052]): the official list of the
 * visitPurposeName values closing recaps carry.
 */
export default function VisitPurposesIndex({
    canManage,
    visitPurposes,
    unregistered,
}: Props) {
    const [editing, setEditing] = useState<Editing | null>(null);

    const columns: DataTableColumn<VisitPurpose>[] = [
        {
            key: 'name',
            header: 'Name',
            className: 'font-medium',
            cell: (row) => row.name,
        },
        {
            key: 'notes',
            header: 'Notes',
            cell: (row) => row.notes ?? '—',
        },
        {
            key: 'recaps',
            header: 'Recaps',
            align: 'right',
            cell: (row) => row.recaps,
        },
        {
            key: 'status',
            header: 'Status',
            cell: (row) => (
                <StatusBadge status={row.active ? 'Active' : 'Inactive'} />
            ),
        },
    ];

    if (canManage) {
        columns.push({
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            label: 'Actions',
            align: 'right',
            cell: (row) => (
                <div className="flex justify-end gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        aria-label={`Edit ${row.name}`}
                        onClick={() => {
                            setEditing(row);
                        }}
                    >
                        Edit
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        aria-label={`Remove ${row.name}`}
                        onClick={() => {
                            if (
                                window.confirm(
                                    `Remove ${row.name} from the visit purposes?`,
                                )
                            ) {
                                router.delete(destroy(row.id).url, {
                                    preserveScroll: true,
                                });
                            }
                        }}
                    >
                        Remove
                    </Button>
                </div>
            ),
        });
    }

    return (
        <>
            <Head title="Visit purposes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Visit purposes"
                    description="The official list of visit purposes (dine in, take away…) closing recaps name. Kept in the gateway."
                    actions={
                        canManage && (
                            <Button
                                onClick={() => {
                                    setEditing({ name: '' });
                                }}
                            >
                                Add visit purpose
                            </Button>
                        )
                    }
                />

                <DataTable
                    caption="Visit purposes"
                    columns={columns}
                    rows={visitPurposes}
                    rowKey={(row) => String(row.id)}
                    empty="No visit purposes yet."
                />

                {unregistered.length > 0 && (
                    <section
                        aria-labelledby="unregistered-heading"
                        className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
                    >
                        <h2
                            id="unregistered-heading"
                            className="mb-1 text-sm font-medium"
                        >
                            Not on the list
                        </h2>
                        <p className="text-muted-foreground mb-3 text-xs">
                            Visit purposes closing recaps name that are not
                            registered.
                        </p>
                        <ul className="divide-y text-sm">
                            {unregistered.map((row) => (
                                <li
                                    key={row.name}
                                    className="flex items-center justify-between gap-2 py-2"
                                >
                                    <span>
                                        <span className="font-medium">
                                            {row.name}
                                        </span>
                                        <span className="text-muted-foreground ml-2 text-xs">
                                            {row.recaps}{' '}
                                            {row.recaps === 1
                                                ? 'recap'
                                                : 'recaps'}
                                        </span>
                                    </span>
                                    {canManage && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            aria-label={`Register ${row.name}`}
                                            onClick={() => {
                                                setEditing({ name: row.name });
                                            }}
                                        >
                                            Register
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>

            {editing && (
                <VisitPurposeDialog
                    purpose={editing}
                    onClose={() => {
                        setEditing(null);
                    }}
                />
            )}
        </>
    );
}

function VisitPurposeDialog({
    purpose,
    onClose,
}: {
    purpose: Editing;
    onClose: () => void;
}) {
    const form = useForm({
        name: purpose.name,
        notes: purpose.notes ?? '',
        active: purpose.active ?? true,
    });
    const id = purpose.id;

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (id === undefined) {
            form.post(store().url, options);
        } else {
            form.put(update(id).url, options);
        }
    }

    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open) onClose();
            }}
        >
            <DialogContent>
                <form onSubmit={submit} noValidate>
                    <DialogHeader>
                        <DialogTitle>
                            {id === undefined
                                ? 'Add visit purpose'
                                : `Edit ${purpose.name}`}
                        </DialogTitle>
                        <DialogDescription>
                            Matched to closing recaps by name, whatever its
                            case.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="visit-purpose-name">Name</Label>
                            <Input
                                id="visit-purpose-name"
                                value={form.data.name}
                                maxLength={100}
                                onChange={(event) => {
                                    form.setData('name', event.target.value);
                                }}
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="visit-purpose-notes">Notes</Label>
                            <Input
                                id="visit-purpose-notes"
                                value={form.data.notes}
                                maxLength={255}
                                onChange={(event) => {
                                    form.setData('notes', event.target.value);
                                }}
                            />
                            <InputError message={form.errors.notes} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="visit-purpose-active"
                                checked={form.data.active}
                                onCheckedChange={(checked) => {
                                    form.setData('active', checked === true);
                                }}
                            />
                            <Label htmlFor="visit-purpose-active">Active</Label>
                            <InputError message={form.errors.active} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
