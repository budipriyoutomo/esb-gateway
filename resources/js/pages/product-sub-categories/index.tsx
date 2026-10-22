import { Head, Link } from '@inertiajs/react';
import { ExportCsvButton, exportQuery } from '@/components/export-csv-button';
import { ImportLinks } from '@/components/import-links';
import { ReferenceDataIndex } from '@/components/reference-data-index';
import type { ReferenceDataFilters } from '@/components/reference-data-index';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import { formatQuantity } from '@/lib/format';
import {
    exportMethod as exportRoute,
    index as subCategoriesIndex,
    show as subCategoriesShow,
    create as createRoute,
} from '@/routes/product-sub-categories';
import type { ProductSubCategorySummary } from '@/types/master-data';
import type { Pagination } from '@/types/pagination';

type Props = {
    subCategories: ProductSubCategorySummary[];
    pagination: Pagination | null;
    filters: ReferenceDataFilters;
};

export default function ProductSubCategoriesIndex({
    subCategories,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();

    return (
        <>
            <Head title="Product sub-categories" />

            <ReferenceDataIndex
                actions={
                    <>
                        <ExportCsvButton
                            href={
                                exportRoute({ query: exportQuery(filters) }).url
                            }
                        />
                        {can('esb.invoke') &&
                        can('product-sub-categories.manage') ? (
                            <>
                                <ImportLinks
                                    canImport
                                    imports={[
                                        {
                                            kind: 'product-sub-categories',
                                            label: 'Import',
                                        },
                                    ]}
                                />
                                <Button asChild>
                                    <Link href={createRoute()}>
                                        New sub-category
                                    </Link>
                                </Button>
                            </>
                        ) : undefined}
                    </>
                }
                id="product-sub-categories"
                title="Product sub-categories"
                description="Product sub-categories, read live from the ERP."
                url={subCategoriesIndex().url}
                filters={filters}
                pagination={pagination}
                rows={subCategories}
                columns={[
                    {
                        key: 'name',
                        header: 'Sub-category',
                        className: 'font-medium',
                        cell: (subCategory) =>
                            subCategory.id === null ? (
                                subCategory.name || '—'
                            ) : (
                                <Link
                                    href={subCategoriesShow(subCategory.id)}
                                    className="underline-offset-4 hover:underline"
                                >
                                    {subCategory.name || '—'}
                                </Link>
                            ),
                    },
                    {
                        key: 'deadStock',
                        header: 'Dead stock after',
                        align: 'right',
                        cell: (subCategory) =>
                            subCategory.deadStockDays === null
                                ? '—'
                                : `${formatQuantity(subCategory.deadStockDays)} days`,
                    },
                    {
                        key: 'notes',
                        header: 'Notes',
                        className: 'text-muted-foreground',
                        cell: (subCategory) => subCategory.notes ?? '—',
                    },
                ]}
                detailUrl={(id) => subCategoriesShow(id).url}
                noun="product sub-categories"
                searchPlaceholder="Sub-category name or notes"
            />
        </>
    );
}
