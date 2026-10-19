import { Head, Link } from '@inertiajs/react';
import { ReferenceDataIndex } from '@/components/reference-data-index';
import type { ReferenceDataFilters } from '@/components/reference-data-index';
import {
    index as categoriesIndex,
    show as categoriesShow,
} from '@/routes/product-categories';
import type { ProductCategorySummary } from '@/types/master-data';
import type { Pagination } from '@/types/pagination';

type Props = {
    categories: ProductCategorySummary[];
    pagination: Pagination | null;
    filters: ReferenceDataFilters;
    /** The vendor's category type legend, id → name. */
    categoryTypes: Record<number, string>;
};

export default function ProductCategoriesIndex({
    categories,
    pagination,
    filters,
    categoryTypes,
}: Props) {
    return (
        <>
            <Head title="Product categories" />

            <ReferenceDataIndex
                id="product-categories"
                title="Product categories"
                description="Product categories and their type, read live from the ERP."
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
                    {
                        key: 'type',
                        header: 'Type',
                        cell: (category) => category.typeName ?? '—',
                    },
                    {
                        key: 'notes',
                        header: 'Notes',
                        className: 'text-muted-foreground',
                        cell: (category) => category.notes ?? '—',
                    },
                ]}
                detailUrl={(id) => categoriesShow(id).url}
                noun="product categories"
                searchPlaceholder="Category or type name"
                extraFilter={{
                    key: 'categoryTypeId',
                    label: 'Type',
                    options: categoryTypes,
                }}
            />
        </>
    );
}

ProductCategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Product categories', href: categoriesIndex() }],
};
