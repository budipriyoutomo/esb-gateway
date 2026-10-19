import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
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
import { index as menusIndex } from '@/routes/menus';
import type { Menu } from '@/types/menu';
import type { Pagination, PagingFilters } from '@/types/pagination';

type Filters = PagingFilters & {
    menuName?: string;
    menuCode?: string;
    flagActive?: number;
};

type Props = {
    menus: Menu[];
    pagination: Pagination | null;
    filters: Filters;
};

const ANY = '__any__';

export default function MenusIndex({ menus, pagination, filters }: Props) {
    const [menuName, setMenuName] = useState(filters.menuName ?? '');
    const [menuCode, setMenuCode] = useState(filters.menuCode ?? '');

    function search(overrides: Record<string, string | number> = {}) {
        const query: Record<string, string | number> = {
            page: 1,
            menuName,
            menuCode,
            ...(filters.flagActive === undefined
                ? {}
                : { flagActive: filters.flagActive }),
            ...overrides,
        };

        for (const key of Object.keys(query)) {
            if (query[key] === '' || query[key] === ANY) {
                delete query[key];
            }
        }

        router.get(menusIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Menus" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Menus"
                    description="Point-of-sale menus in the ERP."
                />

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        search();
                    }}
                >
                    <Input
                        value={menuName}
                        onChange={(event) => {
                            setMenuName(event.target.value);
                        }}
                        placeholder="Menu name"
                        aria-label="Menu name"
                        className="w-56"
                    />
                    <Input
                        value={menuCode}
                        onChange={(event) => {
                            setMenuCode(event.target.value);
                        }}
                        placeholder="Menu code"
                        aria-label="Menu code"
                        className="w-44"
                    />
                    <Select
                        value={
                            filters.flagActive === undefined
                                ? ANY
                                : String(filters.flagActive)
                        }
                        onValueChange={(value) => {
                            search({ flagActive: value });
                        }}
                    >
                        <SelectTrigger className="w-40" aria-label="Status">
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
                                <TableHead>Short name</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Sub-category</TableHead>
                                <TableHead>Notes</TableHead>
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

                            {menus.map((menu, index) => (
                                <TableRow
                                    key={`${String(menu.id)}-${String(index)}`}
                                >
                                    <TableCell className="font-mono text-xs">
                                        {menu.code !== '' ? menu.code : '—'}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {menu.name}
                                    </TableCell>
                                    <TableCell>
                                        {menu.shortName ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {menu.categoryName ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {menu.categoryDetailName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {menu.notes ?? '—'}
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
                                    search({ page: pagination.page - 1 });
                                }}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!pagination.hasMorePages}
                                onClick={() => {
                                    search({ page: pagination.page + 1 });
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

MenusIndex.layout = {
    breadcrumbs: [
        {
            title: 'Menus',
            href: menusIndex(),
        },
    ],
};
