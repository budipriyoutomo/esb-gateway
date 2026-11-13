import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ClosingMappingNav } from '@/components/closing-mapping-nav';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import InputError from '@/components/input-error';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PageHeader } from '@/components/page-header';
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
import { formatDate } from '@/lib/format';
import { destroy, update } from '@/routes/closing-mappings/outlets';
import { index as closingRecapsIndex } from '@/routes/closing-recaps';
import type { ClosingOutletMapping } from '@/types/closing';

type OutletRow = {
    outletCode: string;
    recaps: number;
    lastProductionDate: string | null;
    mapping: ClosingOutletMapping | null;
};

type Props = {
    canManage: boolean;
    outlets: OutletRow[];
};

export default function ClosingOutletMappings({ canManage, outlets }: Props) {
    const [editing, setEditing] = useState<OutletRow | null>(null);

    const columns: DataTableColumn<OutletRow>[] = [
        {
            key: 'outlet',
            header: 'Outlet code',
            className: 'font-medium',
            cell: (row) => row.outletCode,
        },
        {
            key: 'branch',
            header: 'ERP branch',
            cell: (row) =>
                row.mapping ? (
                    `${row.mapping.branchCode} · ${row.mapping.branchName}`
                ) : (
                    <Badge variant="destructive">Not mapped</Badge>
                ),
        },
        {
            key: 'location',
            header: 'Stock location',
            cell: (row) => row.mapping?.locationName ?? '—',
        },
        {
            key: 'recaps',
            header: 'Recaps',
            align: 'right',
            cell: (row) => row.recaps,
        },
        {
            key: 'last',
            header: 'Last production date',
            className: 'text-muted-foreground text-xs',
            cell: (row) => formatDate(row.lastProductionDate),
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
                        aria-label={`${row.mapping ? 'Edit' : 'Map'} ${row.outletCode}`}
                        onClick={() => {
                            setEditing(row);
                        }}
                    >
                        {row.mapping ? 'Edit' : 'Map'}
                    </Button>
                    {row.mapping && (
                        <Button
                            size="sm"
                            variant="ghost"
                            aria-label={`Remove ${row.outletCode}`}
                            onClick={() => {
                                if (
                                    window.confirm(
                                        `Remove the mapping of outlet ${row.outletCode}?`,
                                    )
                                ) {
                                    router.delete(destroy(row.outletCode).url, {
                                        preserveScroll: true,
                                    });
                                }
                            }}
                        >
                            Remove
                        </Button>
                    )}
                </div>
            ),
        });
    }

    return (
        <>
            <Head title="Closing mappings · Outlets" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Closing mappings"
                    description="Each outlet's ERP branch, used for sales, and its stock location, used for item journals."
                    back={{
                        href: closingRecapsIndex(),
                        label: 'Closing recaps',
                    }}
                />

                <ClosingMappingNav current="outlets" />

                <DataTable
                    caption="Outlet mappings"
                    columns={columns}
                    rows={outlets}
                    rowKey={(row) => row.outletCode}
                    empty="No outlet has sent a closing recap yet."
                />
            </div>

            {editing && (
                <OutletDialog
                    row={editing}
                    onClose={() => {
                        setEditing(null);
                    }}
                />
            )}
        </>
    );
}

function OutletDialog({
    row,
    onClose,
}: {
    row: OutletRow;
    onClose: () => void;
}) {
    const form = useForm({
        branchId: row.mapping?.branchId ?? null,
        branchCode: row.mapping?.branchCode ?? '',
        branchName: row.mapping?.branchName ?? '',
        locationId: row.mapping?.locationId ?? null,
        locationName: row.mapping?.locationName ?? '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.put(update(row.outletCode).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
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
                        <DialogTitle>Map outlet {row.outletCode}</DialogTitle>
                        <DialogDescription>
                            The branch code becomes part of the sales number (CL
                            + branch code + date), so it can be at most 10
                            characters.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="outlet-branch"
                                label="ERP branch"
                                kind="branches"
                                value={
                                    form.data.branchId === null
                                        ? null
                                        : String(form.data.branchId)
                                }
                                selectedLabel={
                                    form.data.branchName === ''
                                        ? null
                                        : form.data.branchName
                                }
                                onChange={(option) => {
                                    form.setData((data) => ({
                                        ...data,
                                        branchId: option
                                            ? Number(option.value)
                                            : null,
                                        branchCode: option?.code ?? '',
                                        branchName: option?.label ?? '',
                                        // A location belongs to one branch.
                                        locationId: null,
                                        locationName: '',
                                    }));
                                }}
                            />
                            <InputError
                                message={
                                    form.errors.branchId ??
                                    form.errors.branchCode ??
                                    form.errors.branchName
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <LookupCombobox
                                id="outlet-location"
                                label="Stock location"
                                kind="locations"
                                branchId={form.data.branchId}
                                disabled={form.data.branchId === null}
                                placeholder={
                                    form.data.branchId === null
                                        ? 'Choose a branch first'
                                        : undefined
                                }
                                value={
                                    form.data.locationId === null
                                        ? null
                                        : String(form.data.locationId)
                                }
                                selectedLabel={
                                    form.data.locationName === ''
                                        ? null
                                        : form.data.locationName
                                }
                                onChange={(option) => {
                                    form.setData((data) => ({
                                        ...data,
                                        locationId: option
                                            ? Number(option.value)
                                            : null,
                                        locationName: option?.label ?? '',
                                    }));
                                }}
                            />
                            <InputError
                                message={
                                    form.errors.locationId ??
                                    form.errors.locationName
                                }
                            />
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
