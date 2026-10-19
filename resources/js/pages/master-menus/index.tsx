import { Head, Link, router } from '@inertiajs/react';
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
import { index as menusIndex, show as menusShow } from '@/routes/master-menus';
import type { MasterMenu } from '@/types/master-menu';
import { formatAmount } from '@/lib/format';
import type { Pagination } from '@/types/pagination';

type Props = {
    menus: MasterMenu[];
    pagination: Pagination | null;
    filters: { page?: number; menuCode?: string };
};

// The lowest template price, as a hint. Each template has its own price.
function lowestPrice(menu: MasterMenu): number | null {
    const prices = menu.templates
        .map((template) => template.price)
        .filter((price): price is number => price !== null);

    return prices.length === 0 ? null : Math.min(...prices);
}

export default function MasterMenusIndex({
    menus,
    pagination,
    filters,
}: Props) {
    const [menuCode, setMenuCode] = useState(filters.menuCode ?? '');

    function search(page = 1) {
        const query: Record<string, string | number> = { page };

        if (menuCode !== '') {
            query.menuCode = menuCode;
        }

        router.get(menusIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Master menu" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Master menu"
                    description="Menus with prices per template, packages, and extras, from ESB OMS. Only active menus are listed."
                />

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        search();
                    }}
                >
                    <Input
                        value={menuCode}
                        onChange={(event) => {
                            setMenuCode(event.target.value);
                        }}
                        placeholder="Menu code"
                        aria-label="Menu code"
                        className="w-56"
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
                                <TableHead>Tax</TableHead>
                                <TableHead className="text-right">
                                    From
                                </TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {menus.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No menus matched.
                                    </TableCell>
                                </TableRow>
                            )}

                            {menus.map((menu) => (
                                <TableRow
                                    key={`${String(menu.id)}-${menu.code}`}
                                >
                                    <TableCell className="font-mono text-xs">
                                        {menu.code !== '' ? (
                                            <Link
                                                href={menusShow(menu.code)}
                                                className="hover:underline"
                                            >
                                                {menu.code}
                                            </Link>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {menu.name}
                                    </TableCell>
                                    <TableCell>
                                        {menu.categoryName ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {menu.taxLabel ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatAmount(lowestPrice(menu))}
                                    </TableCell>
                                    <TableCell>
                                        {menu.active !== null && (
                                            <Badge
                                                variant={
                                                    menu.active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {menu.active
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
                            {pagination.count} menus
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

MasterMenusIndex.layout = {
    breadcrumbs: [
        {
            title: 'Master menu',
            href: menusIndex(),
        },
    ],
};
