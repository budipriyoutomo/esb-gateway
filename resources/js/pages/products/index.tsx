import { Head, Link } from '@inertiajs/react';
import { ExportCsvButton, exportQuery } from '@/components/export-csv-button';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ImportLinks } from '@/components/import-links';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { useFilterForm } from '@/hooks/use-filter-form';
import { usePermissions } from '@/hooks/use-permissions';
import {
    exportMethod as exportRoute,
    create as createProduct,
    index as productsIndex,
    show as productsShow,
} from '@/routes/products';
import type { Pagination } from '@/types/pagination';

type Product = {
    // Which provider supplied the row. Two providers may reuse an id, so a
    // canonical product is only identifiable together with its origin.
    provider: string;
    id: number;
    name: string;
    code: string;
    categoryName: string | null;
    subCategoryName: string | null;
    categoryTypeName: string | null;
    bomName: string | null;
    isActive: boolean;
};

type Props = {
    products: Product[];
    pagination: Pagination | null;
    filters: Record<string, string | number>;
};

const columns: DataTableColumn<Product>[] = [
    {
        key: 'code',
        header: 'Code',
        className: 'font-mono text-xs',
        cell: (product) => product.code,
    },
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (product) =>
            product.id > 0 ? (
                <Link
                    href={productsShow(product.id)}
                    className="underline-offset-4 hover:underline"
                >
                    {product.name}
                </Link>
            ) : (
                product.name
            ),
    },
    {
        key: 'category',
        header: 'Category',
        className: 'text-muted-foreground',
        cell: (product) => product.categoryName ?? '—',
    },
    {
        key: 'subCategory',
        header: 'Sub-category',
        className: 'text-muted-foreground',
        cell: (product) => product.subCategoryName ?? '—',
    },
    {
        key: 'type',
        header: 'Type',
        className: 'text-muted-foreground',
        cell: (product) => product.categoryTypeName ?? '—',
    },
    {
        key: 'status',
        header: 'Status',
        cell: (product) => (
            <StatusBadge status={product.isActive ? 'Active' : 'Inactive'} />
        ),
    },
];

export default function ProductsIndex({
    products,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();
    // Pagination is re-derived from page and limit: the ESB's absolute
    // prev/next URLs point at its own host and never reach the browser.
    const form = useFilterForm(productsIndex().url, filters, [
        { key: 'productName', chip: (value) => `“${value}”` },
    ]);

    return (
        <>
            <Head title="Products" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Products"
                    description="Product master data, read live from the ERP."
                    actions={
                        <>
                            <ExportCsvButton
                                href={
                                    exportRoute({ query: exportQuery(filters) })
                                        .url
                                }
                            />
                            {can('esb.invoke') && can('products.manage') && (
                                <>
                                    <ImportLinks
                                        canImport
                                        imports={[
                                            {
                                                kind: 'products-create',
                                                label: 'Import new',
                                            },
                                            {
                                                kind: 'products-update',
                                                label: 'Import changes',
                                            },
                                        ]}
                                    />
                                    <Button asChild>
                                        <Link href={createProduct()}>
                                            New product
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </>
                    }
                />

                <FilterBar
                    id="products"
                    search={{
                        label: 'Product name',
                        value: form.values.productName!,
                        onChange: (value) => form.set('productName', value),
                        placeholder: 'Filter by product name',
                    }}
                    {...form.bar}
                />

                <DataTable
                    caption="Products"
                    columns={columns}
                    rows={products}
                    rowKey={(product) => String(product.id)}
                    rowHref={(product) =>
                        product.id > 0 ? productsShow(product.id).url : null
                    }
                    empty="No products matched this filter."
                />

                <ListPagination
                    pagination={pagination}
                    noun="products"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
