import { Head, Link } from '@inertiajs/react';
import { ActiveStatusField } from '@/components/active-status-field';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import {
    index as bomsIndex,
    show as bomsShow,
} from '@/routes/bills-of-material';
import type { BillOfMaterialSummary } from '@/types/bill-of-material';
import type { Pagination, PagingFilters } from '@/types/pagination';

type Filters = PagingFilters & {
    bomName?: string;
    bomCode?: string;
    productName?: string;
    flagActive?: number;
};

type Props = {
    boms: BillOfMaterialSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const columns: DataTableColumn<BillOfMaterialSummary>[] = [
    {
        key: 'code',
        header: 'Code',
        className: 'font-mono text-xs',
        cell: (bom) => bom.code ?? '—',
    },
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (bom) =>
            bom.id !== null ? (
                <Link
                    href={bomsShow(bom.id)}
                    className="underline-offset-4 hover:underline"
                >
                    {bom.name || `#${String(bom.id)}`}
                </Link>
            ) : (
                bom.name || '—'
            ),
    },
    { key: 'type', header: 'Type', cell: (bom) => bom.typeName ?? '—' },
    {
        key: 'product',
        header: 'Product',
        cell: (bom) => bom.productName ?? '—',
    },
    { key: 'unit', header: 'Unit', cell: (bom) => bom.uomName ?? '—' },
    {
        key: 'status',
        header: 'Status',
        cell: (bom) => (
            <StatusBadge
                status={
                    bom.active === null
                        ? null
                        : bom.active
                          ? 'Active'
                          : 'Inactive'
                }
            />
        ),
    },
];

export default function BillsOfMaterialIndex({
    boms,
    pagination,
    filters,
}: Props) {
    const form = useFilterForm(bomsIndex().url, filters, [
        { key: 'bomName', chip: (value) => `“${value}”` },
        { key: 'bomCode', chip: (value) => `Code ${value}` },
        { key: 'productName', chip: (value) => `Product “${value}”` },
        {
            key: 'flagActive',
            chip: (value) => (value === '1' ? 'Active' : 'Inactive'),
        },
    ]);

    return (
        <>
            <Head title="Bill of material" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Bill of material"
                    description="Recipes and their materials, from ESB Core."
                />

                <FilterBar
                    id="boms"
                    search={{
                        label: 'BOM name',
                        value: form.values.bomName!,
                        onChange: (value) => form.set('bomName', value),
                        placeholder: 'BOM name',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={
                        Boolean(filters.bomCode || filters.productName) ||
                        filters.flagActive !== undefined
                    }
                >
                    <div className="grid w-full gap-1.5 sm:w-40">
                        <Label htmlFor="boms-code">BOM code</Label>
                        <Input
                            id="boms-code"
                            value={form.values.bomCode}
                            onChange={(event) => {
                                form.set('bomCode', event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid w-full gap-1.5 sm:w-56">
                        <Label htmlFor="boms-product">Product name</Label>
                        <Input
                            id="boms-product"
                            value={form.values.productName}
                            onChange={(event) => {
                                form.set('productName', event.target.value);
                            }}
                        />
                    </div>
                    <ActiveStatusField
                        id="boms-status"
                        value={form.values.flagActive!}
                        onChange={(flagActive) => {
                            // A status applies at once, as it did before.
                            form.set('flagActive', flagActive);
                            form.apply({ ...form.values, flagActive });
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Bills of material"
                    columns={columns}
                    rows={boms}
                    rowKey={(bom, index) =>
                        `${String(bom.id)}-${String(index)}`
                    }
                    rowHref={(bom) =>
                        bom.id !== null ? bomsShow(bom.id).url : null
                    }
                    empty="No bills of material matched."
                />

                <ListPagination
                    pagination={pagination}
                    noun="bills of material"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
