import { Head, useForm } from '@inertiajs/react';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { update } from '@/routes/closing-mappings/purposes';
import { index as closingRecapsIndex } from '@/routes/closing-recaps';
import type { ClosingPurposeMapping, ClosingStockKind } from '@/types/closing';

type PurposeRow = {
    kind: ClosingStockKind;
    label: string;
    mapping: ClosingPurposeMapping | null;
};

type Props = {
    canManage: boolean;
    purposes: PurposeRow[];
};

const DIRECTION_LABELS: Record<'-1' | '1', string> = {
    '-1': 'Stock out (quantity × −1)',
    '1': 'Stock in (quantity as sent)',
};

export default function ClosingPurposeMappings({ canManage, purposes }: Props) {
    const [editing, setEditing] = useState<PurposeRow | null>(null);

    const columns: DataTableColumn<PurposeRow>[] = [
        {
            key: 'kind',
            header: 'Closing quantity',
            className: 'font-medium',
            cell: (row) => row.label,
        },
        {
            key: 'purpose',
            header: 'ERP purpose',
            cell: (row) =>
                row.mapping?.purposeName ?? (
                    <Badge variant="destructive">Not mapped</Badge>
                ),
        },
        {
            key: 'direction',
            header: 'Direction',
            cell: (row) =>
                row.mapping
                    ? DIRECTION_LABELS[
                          String(row.mapping.direction) as '-1' | '1'
                      ]
                    : '—',
        },
    ];

    if (canManage) {
        columns.push({
            key: 'actions',
            header: <span className="sr-only">Actions</span>,
            label: 'Actions',
            align: 'right',
            cell: (row) => (
                <Button
                    size="sm"
                    variant="outline"
                    aria-label={`${row.mapping ? 'Edit' : 'Map'} ${row.label}`}
                    onClick={() => {
                        setEditing(row);
                    }}
                >
                    {row.mapping ? 'Edit' : 'Map'}
                </Button>
            ),
        });
    }

    return (
        <>
            <Head title="Closing mappings · Purposes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Closing mappings"
                    description="The item journal purpose for each closing quantity, and whether it takes stock out or puts it in. Each becomes its own item journal."
                    back={{
                        href: closingRecapsIndex(),
                        label: 'Closing recaps',
                    }}
                />

                <ClosingMappingNav current="purposes" />

                <DataTable
                    caption="Purpose mappings"
                    columns={columns}
                    rows={purposes}
                    rowKey={(row) => row.kind}
                    empty="No closing quantities."
                />
            </div>

            {editing && (
                <PurposeDialog
                    row={editing}
                    onClose={() => {
                        setEditing(null);
                    }}
                />
            )}
        </>
    );
}

function PurposeDialog({
    row,
    onClose,
}: {
    row: PurposeRow;
    onClose: () => void;
}) {
    const form = useForm({
        purposeId: row.mapping?.purposeId ?? null,
        purposeName: row.mapping?.purposeName ?? '',
        direction: row.mapping?.direction ?? -1,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.put(update(row.kind).url, {
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
                        <DialogTitle>Map {row.label.toLowerCase()}</DialogTitle>
                        <DialogDescription>
                            An adjustment keeps its own sign from the recap; the
                            direction multiplies it.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="purpose-purpose"
                                label="ERP purpose"
                                kind="purposes"
                                value={
                                    form.data.purposeId === null
                                        ? null
                                        : String(form.data.purposeId)
                                }
                                selectedLabel={
                                    form.data.purposeName === ''
                                        ? null
                                        : form.data.purposeName
                                }
                                onChange={(option) => {
                                    form.setData((data) => ({
                                        ...data,
                                        purposeId: option
                                            ? Number(option.value)
                                            : null,
                                        purposeName: option?.label ?? '',
                                    }));
                                }}
                            />
                            <InputError
                                message={
                                    form.errors.purposeId ??
                                    form.errors.purposeName
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="purpose-direction">Direction</Label>
                            <Select
                                value={String(form.data.direction)}
                                onValueChange={(value) => {
                                    form.setData(
                                        'direction',
                                        value === '1' ? 1 : -1,
                                    );
                                }}
                            >
                                <SelectTrigger id="purpose-direction">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="-1">
                                        {DIRECTION_LABELS['-1']}
                                    </SelectItem>
                                    <SelectItem value="1">
                                        {DIRECTION_LABELS['1']}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.direction} />
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
