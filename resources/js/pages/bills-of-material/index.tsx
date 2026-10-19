import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

const ANY = 'any';

export default function BillsOfMaterialIndex({
    boms,
    pagination,
    filters,
}: Props) {
    const [bomName, setBomName] = useState(filters.bomName ?? '');
    const [bomCode, setBomCode] = useState(filters.bomCode ?? '');
    const [productName, setProductName] = useState(filters.productName ?? '');
    const [status, setStatus] = useState(
        filters.flagActive === undefined ? ANY : String(filters.flagActive),
    );

    function search(page = 1, nextStatus = status) {
        const query: Record<string, string | number> = { page };

        if (bomName !== '') {
            query.bomName = bomName;
        }

        if (bomCode !== '') {
            query.bomCode = bomCode;
        }

        if (productName !== '') {
            query.productName = productName;
        }

        if (nextStatus !== ANY) {
            query.flagActive = nextStatus;
        }

        router.get(bomsIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Bill of material" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Bill of material"
                    description="Recipes and their materials, from ESB Core."
                />

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        search();
                    }}
                >
                    <Input
                        value={bomName}
                        onChange={(event) => {
                            setBomName(event.target.value);
                        }}
                        placeholder="BOM name"
                        aria-label="BOM name"
                        className="w-56"
                    />
                    <Input
                        value={bomCode}
                        onChange={(event) => {
                            setBomCode(event.target.value);
                        }}
                        placeholder="BOM code"
                        aria-label="BOM code"
                        className="w-40"
                    />
                    <Input
                        value={productName}
                        onChange={(event) => {
                            setProductName(event.target.value);
                        }}
                        placeholder="Product name"
                        aria-label="Product name"
                        className="w-56"
                    />
                    <Select
                        value={status}
                        onValueChange={(value) => {
                            setStatus(value);
                            search(1, value);
                        }}
                    >
                        <SelectTrigger className="w-36" aria-label="Status">
                            <SelectValue placeholder="Any status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any status</SelectItem>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
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
                                <TableHead>Type</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {boms.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No bills of material matched.
                                    </TableCell>
                                </TableRow>
                            )}

                            {boms.map((bom, index) => (
                                <TableRow
                                    key={`${String(bom.id)}-${String(index)}`}
                                >
                                    <TableCell className="font-mono text-xs">
                                        {bom.code ?? '—'}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {bom.id !== null ? (
                                            <Link
                                                href={bomsShow(bom.id)}
                                                className="hover:underline"
                                            >
                                                {bom.name ||
                                                    `#${String(bom.id)}`}
                                            </Link>
                                        ) : (
                                            bom.name || '—'
                                        )}
                                    </TableCell>
                                    <TableCell>{bom.typeName ?? '—'}</TableCell>
                                    <TableCell>
                                        {bom.productName ?? '—'}
                                    </TableCell>
                                    <TableCell>{bom.uomName ?? '—'}</TableCell>
                                    <TableCell>
                                        {bom.active !== null && (
                                            <Badge
                                                variant={
                                                    bom.active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {bom.active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        )}
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
                            {pagination.count} bills of material
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.page <= 1}
                                onClick={() => {
                                    search(pagination.page - 1);
                                }}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!pagination.hasMorePages}
                                onClick={() => {
                                    search(pagination.page + 1);
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

BillsOfMaterialIndex.layout = {
    breadcrumbs: [
        {
            title: 'Bill of material',
            href: bomsIndex(),
        },
    ],
};
