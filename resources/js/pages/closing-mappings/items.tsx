import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ClosingMappingNav } from '@/components/closing-mapping-nav';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import InputError from '@/components/input-error';
import { ListPagination } from '@/components/list-pagination';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PageHeader } from '@/components/page-header';
import { SelectFilterField } from '@/components/select-filter-field';
import { Badge } from '@/components/ui/badge';
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
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import { fromPaginator } from '@/lib/pagination';
import type { LaravelPaginator } from '@/lib/pagination';
import { items as itemsIndex } from '@/routes/closing-mappings';
import { destroy, update } from '@/routes/closing-mappings/items';
import { index as closingRecapsIndex } from '@/routes/closing-recaps';
import type { ClosingItemMapping, ClosingItemSource } from '@/types/closing';
import { SOURCE_LABELS } from '@/types/closing';

type ItemRow = {
    source: ClosingItemSource;
    key: string;
    code: string | null;
    name: string | null;
    recaps: number;
    needsBom: boolean;
    mapping: ClosingItemMapping | null;
};

type Props = {
    canManage: boolean;
    filters: Record<string, string>;
    items: LaravelPaginator<ItemRow>;
};

function menuLabel(mapping: ClosingItemMapping | null): string | null {
    if (mapping?.esbMenuId == null) return null;

    return mapping.esbMenuCode
        ? `${mapping.esbMenuCode} · ${mapping.esbMenuName}`
        : mapping.esbMenuName;
}

export default function ClosingItemMappings({
    canManage,
    filters,
    items,
}: Props) {
    const [editing, setEditing] = useState<ItemRow | null>(null);
    const form = useFilterForm(itemsIndex().url, filters, [
        { key: 'search', chip: (value) => `“${value}”` },
        {
            key: 'source',
            chip: (value) => SOURCE_LABELS[value as ClosingItemSource] ?? value,
        },
        { key: 'unmapped', chip: () => 'Not mapped only' },
    ]);

    const columns: DataTableColumn<ItemRow>[] = [
        {
            key: 'item',
            header: 'Item',
            cell: (row) => (
                <div className="grid">
                    <span className="font-medium">
                        {row.name ?? row.code ?? row.key}
                    </span>
                    <span className="text-muted-foreground text-xs">
                        {SOURCE_LABELS[row.source]}
                        {row.code ? ` · ${row.code}` : ''}
                    </span>
                </div>
            ),
        },
        {
            key: 'menu',
            header: 'ERP menu (sales)',
            cell: (row) =>
                menuLabel(row.mapping) ?? (
                    <Badge variant="destructive">Not mapped</Badge>
                ),
        },
        {
            key: 'bom',
            header: 'Bill of material (item journals)',
            cell: (row) => {
                if (!row.needsBom) return 'Not used';

                return (
                    row.mapping?.bomName ?? (
                        <Badge variant="destructive">Not mapped</Badge>
                    )
                );
            },
        },
        {
            key: 'recaps',
            header: 'Recaps',
            align: 'right',
            cell: (row) => row.recaps,
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
                        aria-label={`${row.mapping ? 'Edit' : 'Map'} ${row.name ?? row.key}`}
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
                            aria-label={`Remove ${row.name ?? row.key}`}
                            onClick={() => {
                                if (
                                    window.confirm(
                                        `Remove the mapping of ${row.name ?? row.key}?`,
                                    )
                                ) {
                                    router.delete(
                                        destroy({
                                            source: row.source,
                                            key: row.key,
                                        }).url,
                                        { preserveScroll: true },
                                    );
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
            <Head title="Closing mappings · Menus & products" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Closing mappings"
                    description="Each closing menu's ERP menu and bill of material, and each POS product's ERP menu. Items appear here once a recap has carried them."
                    back={{
                        href: closingRecapsIndex(),
                        label: 'Closing recaps',
                    }}
                />

                <ClosingMappingNav current="items" />

                <FilterBar
                    id="closing-items"
                    search={{
                        label: 'Name or code',
                        value: form.values.search!,
                        onChange: (value) => form.set('search', value),
                        placeholder: 'Name or code',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={Boolean(
                        filters.source || filters.unmapped,
                    )}
                >
                    <SelectFilterField
                        id="closing-items-source"
                        label="Source"
                        anyLabel="Any source"
                        options={(['closing', 'pos_only'] as const).map(
                            (source) => ({
                                value: source,
                                label: SOURCE_LABELS[source],
                            }),
                        )}
                        value={form.values.source ?? ''}
                        onChange={(source) => {
                            form.set('source', source);
                            form.apply({ ...form.values, source });
                        }}
                    />
                    <div className="flex items-center gap-2 self-end pb-2">
                        <Checkbox
                            id="closing-items-unmapped"
                            checked={filters.unmapped === '1'}
                            onCheckedChange={(checked) => {
                                const unmapped = checked === true ? '1' : '';
                                form.set('unmapped', unmapped);
                                form.apply({ ...form.values, unmapped });
                            }}
                        />
                        <Label htmlFor="closing-items-unmapped">
                            Not mapped only
                        </Label>
                    </div>
                </FilterBar>

                <DataTable
                    caption="Menu and product mappings"
                    columns={columns}
                    rows={items.data}
                    rowKey={(row) => `${row.source}:${row.key}`}
                    empty="No item matched these filters."
                />

                <ListPagination
                    pagination={fromPaginator(items)}
                    noun="items"
                    onPageChange={form.goToPage}
                />
            </div>

            {editing && (
                <ItemDialog
                    row={editing}
                    onClose={() => {
                        setEditing(null);
                    }}
                />
            )}
        </>
    );
}

function ItemDialog({ row, onClose }: { row: ItemRow; onClose: () => void }) {
    const form = useForm({
        esbMenuId: row.mapping?.esbMenuId ?? null,
        esbMenuCode: row.mapping?.esbMenuCode ?? '',
        esbMenuName: row.mapping?.esbMenuName ?? '',
        bomId: row.mapping?.bomId ?? null,
        bomName: row.mapping?.bomName ?? '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) =>
            row.needsBom ? data : { ...data, bomId: null, bomName: '' },
        );
        form.put(update({ source: row.source, key: row.key }).url, {
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
                        <DialogTitle>Map {row.name ?? row.key}</DialogTitle>
                        <DialogDescription>
                            {row.needsBom
                                ? 'The ERP menu receives its sales. Its waste, adjustment and compensation are expanded through the bill of material, one level deep.'
                                : 'The ERP menu receives its sales. A POS product has no item journal.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="item-menu"
                                label="ERP menu"
                                kind="pos-menus"
                                value={
                                    form.data.esbMenuId === null
                                        ? null
                                        : String(form.data.esbMenuId)
                                }
                                selectedLabel={
                                    form.data.esbMenuName === ''
                                        ? null
                                        : form.data.esbMenuName
                                }
                                onChange={(option) => {
                                    form.setData((data) => ({
                                        ...data,
                                        esbMenuId: option
                                            ? Number(option.value)
                                            : null,
                                        esbMenuCode: option?.code ?? '',
                                        esbMenuName: option?.label ?? '',
                                    }));
                                }}
                            />
                            <InputError
                                message={
                                    form.errors.esbMenuId ??
                                    form.errors.esbMenuCode ??
                                    form.errors.esbMenuName
                                }
                            />
                        </div>

                        {row.needsBom && (
                            <div className="grid gap-2">
                                <LookupCombobox
                                    id="item-bom"
                                    label="Bill of material"
                                    kind="bills-of-material"
                                    value={
                                        form.data.bomId === null
                                            ? null
                                            : String(form.data.bomId)
                                    }
                                    selectedLabel={
                                        form.data.bomName === ''
                                            ? null
                                            : form.data.bomName
                                    }
                                    onChange={(option) => {
                                        form.setData((data) => ({
                                            ...data,
                                            bomId: option
                                                ? Number(option.value)
                                                : null,
                                            bomName: option?.label ?? '',
                                        }));
                                    }}
                                />
                                <InputError
                                    message={
                                        form.errors.bomId ?? form.errors.bomName
                                    }
                                />
                            </div>
                        )}
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
