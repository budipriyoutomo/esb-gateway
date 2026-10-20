import { Head, Link } from '@inertiajs/react';
import { usePermissions } from '@/hooks/use-permissions';
import { ImportLinks } from '@/components/import-links';
import { ReferenceDataIndex } from '@/components/reference-data-index';
import type { ReferenceDataFilters } from '@/components/reference-data-index';
import {
    index as suppliersIndex,
    show as suppliersShow,
} from '@/routes/suppliers';
import type { SupplierSummary } from '@/types/master-data';
import type { Pagination } from '@/types/pagination';

type Props = {
    suppliers: SupplierSummary[];
    pagination: Pagination | null;
    filters: ReferenceDataFilters;
};

function creditTerms(days: number | null): string {
    if (days === null) {
        return '—';
    }

    return days === 0 ? 'Cash' : `${days} days`;
}

export default function SuppliersIndex({
    suppliers,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();

    return (
        <>
            <Head title="Suppliers" />

            <ReferenceDataIndex
                id="suppliers"
                actions={
                    <ImportLinks
                        canImport={can('esb.invoke') && can('suppliers.manage')}
                        imports={[
                            { kind: 'suppliers-create', label: 'Import new' },
                            {
                                kind: 'suppliers-update',
                                label: 'Import changes',
                            },
                        ]}
                    />
                }
                title="Suppliers"
                description="Suppliers, read live from the ERP."
                url={suppliersIndex().url}
                filters={filters}
                pagination={pagination}
                rows={suppliers}
                columns={[
                    {
                        key: 'code',
                        header: 'Code',
                        className: 'font-mono text-xs',
                        cell: (supplier) => supplier.code ?? '—',
                    },
                    {
                        key: 'name',
                        header: 'Supplier',
                        className: 'font-medium',
                        cell: (supplier) =>
                            supplier.id === null ? (
                                supplier.name || '—'
                            ) : (
                                <Link
                                    href={suppliersShow(supplier.id)}
                                    className="underline-offset-4 hover:underline"
                                >
                                    {supplier.name || '—'}
                                </Link>
                            ),
                    },
                    {
                        key: 'category',
                        header: 'Category',
                        cell: (supplier) => supplier.categoryName ?? '—',
                    },
                    {
                        key: 'contact',
                        header: 'Contact',
                        className: 'text-muted-foreground',
                        cell: (supplier) =>
                            [supplier.contactPerson, supplier.cellPhone]
                                .filter((part) => part !== null)
                                .join(' · ') || '—',
                    },
                    {
                        key: 'terms',
                        header: 'Credit terms',
                        align: 'right',
                        cell: (supplier) => creditTerms(supplier.creditDays),
                    },
                ]}
                detailUrl={(id) => suppliersShow(id).url}
                noun="suppliers"
                searchPlaceholder="Name, code, address, contact…"
            />
        </>
    );
}

SuppliersIndex.layout = {
    breadcrumbs: [{ title: 'Suppliers', href: suppliersIndex() }],
};
