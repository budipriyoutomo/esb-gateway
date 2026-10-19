import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as productsIndex } from '@/routes/products';
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

export default function ProductsIndex({
    products,
    pagination,
    filters,
}: Props) {
    const [search, setSearch] = useState(String(filters.productName ?? ''));

    function applySearch(event: React.FormEvent) {
        event.preventDefault();

        router.get(
            productsIndex().url,
            search === '' ? {} : { productName: search },
            { preserveState: true, replace: true },
        );
    }

    function goToPage(page: number) {
        // Pagination is re-derived here from page and limit. The ESB returns
        // absolute prev/next URLs pointing at its own host, and those never
        // reach the browser.
        router.get(
            productsIndex().url,
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Products" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Products"
                    description="Product master data, read live from the ERP."
                />

                <form onSubmit={applySearch} className="flex max-w-md gap-2">
                    <Input
                        value={search}
                        onChange={(event) => {
                            setSearch(event.target.value);
                        }}
                        placeholder="Filter by product name"
                        aria-label="Filter by product name"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Sub-category</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No products matched this filter.
                                    </TableCell>
                                </TableRow>
                            )}

                            {products.map((product) => (
                                <TableRow key={product.id}>
                                    <TableCell className="font-mono text-xs">
                                        {product.code}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {product.name}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {product.categoryName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {product.subCategoryName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {product.categoryTypeName ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                product.isActive
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {product.isActive
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {pagination && (
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-muted-foreground text-sm">
                            Page {pagination.page} of {pagination.lastPage} —{' '}
                            {pagination.count} products
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.page <= 1}
                                onClick={() => {
                                    goToPage(pagination.page - 1);
                                }}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!pagination.hasMorePages}
                                onClick={() => {
                                    goToPage(pagination.page + 1);
                                }}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: productsIndex(),
        },
    ],
};
