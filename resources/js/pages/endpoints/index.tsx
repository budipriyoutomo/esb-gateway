import { Head, router } from '@inertiajs/react';
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
import { index as endpointsIndex } from '@/routes/endpoints';

type Endpoint = {
    id: number;
    code: string;
    product: string;
    group: string;
    name: string;
    version: string;
    method: string | null;
    path: string | null;
    family: string;
    isOperation: boolean;
    docsUrl: string;
};

type Paginated = {
    data: Endpoint[];
    total: number;
    current_page: number;
    last_page: number;
};

type Props = {
    endpoints: Paginated;
    filters: Record<string, string>;
    groups: string[];
    methods: string[];
    families: string[];
};

const ANY = '__any__';

export default function EndpointsIndex({
    endpoints,
    filters,
    groups,
    methods,
    families,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function apply(overrides: Record<string, string> = {}) {
        const next: Record<string, string> = {
            ...filters,
            search,
            ...overrides,
        };

        for (const key of Object.keys(next)) {
            if (next[key] === '' || next[key] === ANY) {
                delete next[key];
            }
        }

        router.get(endpointsIndex().url, next, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Endpoint catalog" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Endpoint catalog"
                    description="Every operation the ERP service documents. Imported from the vendor documentation; read-only."
                />

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        apply();
                    }}
                >
                    <Input
                        value={search}
                        onChange={(event) => {
                            setSearch(event.target.value);
                        }}
                        placeholder="Search name, path, or group"
                        aria-label="Search"
                        className="w-80"
                    />
                    <Select
                        value={filters.group ?? ANY}
                        onValueChange={(value) => {
                            apply({ group: value });
                        }}
                    >
                        <SelectTrigger className="w-56" aria-label="Group">
                            <SelectValue placeholder="Any group" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any group</SelectItem>
                            {groups.map((group) => (
                                <SelectItem key={group} value={group}>
                                    {group.replace(/_/g, ' ')}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.method ?? ANY}
                        onValueChange={(value) => {
                            apply({ method: value });
                        }}
                    >
                        <SelectTrigger className="w-36" aria-label="Method">
                            <SelectValue placeholder="Any method" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any method</SelectItem>
                            {methods.map((method) => (
                                <SelectItem key={method} value={method}>
                                    {method}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.family ?? ANY}
                        onValueChange={(value) => {
                            apply({ family: value });
                        }}
                    >
                        <SelectTrigger className="w-44" aria-label="Family">
                            <SelectValue placeholder="Any family" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any family</SelectItem>
                            {families.map((family) => (
                                <SelectItem key={family} value={family}>
                                    {family}
                                </SelectItem>
                            ))}
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
                                <TableHead>Method</TableHead>
                                <TableHead>Path</TableHead>
                                <TableHead>Operation</TableHead>
                                <TableHead>Group</TableHead>
                                <TableHead>Family</TableHead>
                                <TableHead>Docs</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {endpoints.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No operations matched these filters.
                                    </TableCell>
                                </TableRow>
                            )}

                            {endpoints.data.map((endpoint) => (
                                <TableRow key={endpoint.id}>
                                    <TableCell>
                                        {endpoint.method ? (
                                            <Badge variant="secondary">
                                                {endpoint.method}
                                            </Badge>
                                        ) : (
                                            <span className="text-muted-foreground text-xs">
                                                Guide
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {endpoint.path ?? '—'}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {endpoint.name}
                                        <span className="text-muted-foreground ml-2 text-xs">
                                            v{endpoint.version}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {endpoint.group.replace(/_/g, ' ')}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {endpoint.family}
                                    </TableCell>
                                    <TableCell>
                                        <a
                                            href={endpoint.docsUrl}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-xs underline underline-offset-4"
                                        >
                                            Vendor docs
                                        </a>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex items-center justify-between gap-4">
                    <p className="text-muted-foreground text-sm">
                        Page {endpoints.current_page} of {endpoints.last_page} —{' '}
                        {endpoints.total} operations
                    </p>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={endpoints.current_page <= 1}
                            onClick={() => {
                                apply({
                                    page: String(endpoints.current_page - 1),
                                });
                            }}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={
                                endpoints.current_page >= endpoints.last_page
                            }
                            onClick={() => {
                                apply({
                                    page: String(endpoints.current_page + 1),
                                });
                            }}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

EndpointsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Endpoint catalog',
            href: endpointsIndex(),
        },
    ],
};
