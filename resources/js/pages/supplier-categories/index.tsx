import { Head, Link } from '@inertiajs/react';
import { ReferenceDataIndex } from '@/components/reference-data-index';
import type { ReferenceDataFilters } from '@/components/reference-data-index';
import {
    index as categoriesIndex,
    show as categoriesShow,
} from '@/routes/supplier-categories';
import type { SupplierCategorySummary } from '@/types/master-data';
import type { Pagination } from '@/types/pagination';

type Props = {
    categories: SupplierCategorySummary[];
    pagination: Pagination | null;
    filters: ReferenceDataFilters;
};

export default function SupplierCategoriesIndex({
    categories,
    pagination,
    filters,
}: Props) {
    return (
        <>
            <Head title="Supplier categories" />

            <ReferenceDataIndex
                id="supplier-categories"
                title="Supplier categories"
                description="Supplier categories, read live from the ERP."
                url={categoriesIndex().url}
                filters={filters}
                pagination={pagination}
                rows={categories}
                columns={[
                    {
                        key: 'name',
                        header: 'Category',
                        className: 'font-medium',
                        cell: (category) =>
                            category.id === null ? (
                                category.name || '—'
                            ) : (
                                <Link
                                    href={categoriesShow(category.id)}
                                    className="underline-offset-4 hover:underline"
                                >
                                    {category.name || '—'}
                                </Link>
                            ),
                    },
                ]}
                detailUrl={(id) => categoriesShow(id).url}
                noun="supplier categories"
                searchPlaceholder="Category name"
            />
        </>
    );
}

SupplierCategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Supplier categories', href: categoriesIndex() }],
};
