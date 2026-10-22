import { Head } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { SelectFilterField } from '@/components/select-filter-field';
import { Badge } from '@/components/ui/badge';
import { useFilterForm } from '@/hooks/use-filter-form';
import { fromPaginator } from '@/lib/pagination';
import type { LaravelPaginator } from '@/lib/pagination';
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

type Props = {
    endpoints: LaravelPaginator<Endpoint>;
    filters: Record<string, string>;
    groups: string[];
    methods: string[];
    families: string[];
};

function groupLabel(group: string): string {
    return group.replace(/_/g, ' ');
}

const columns: DataTableColumn<Endpoint>[] = [
    {
        key: 'method',
        header: 'Method',
        cell: (endpoint) =>
            endpoint.method ? (
                <Badge variant="secondary">{endpoint.method}</Badge>
            ) : (
                <span className="text-muted-foreground text-xs">Guide</span>
            ),
    },
    {
        key: 'path',
        header: 'Path',
        className: 'font-mono text-xs',
        cell: (endpoint) => endpoint.path ?? '—',
    },
    {
        key: 'operation',
        header: 'Operation',
        className: 'font-medium',
        cell: (endpoint) => (
            <span>
                {endpoint.name}
                <span className="text-muted-foreground ml-2 text-xs">
                    v{endpoint.version}
                </span>
            </span>
        ),
    },
    {
        key: 'group',
        header: 'Group',
        className: 'text-muted-foreground text-xs',
        cell: (endpoint) => groupLabel(endpoint.group),
    },
    {
        key: 'family',
        header: 'Family',
        className: 'font-mono text-xs',
        cell: (endpoint) => endpoint.family,
    },
    {
        key: 'docs',
        header: 'Docs',
        cell: (endpoint) => (
            <a
                href={endpoint.docsUrl}
                target="_blank"
                rel="noreferrer"
                className="text-xs underline underline-offset-4"
            >
                Vendor docs
            </a>
        ),
    },
];

export default function EndpointsIndex({
    endpoints,
    filters,
    groups,
    methods,
    families,
}: Props) {
    const form = useFilterForm(endpointsIndex().url, filters, [
        { key: 'search', chip: (value) => `“${value}”` },
        { key: 'group', chip: (value) => `Group ${groupLabel(value)}` },
        { key: 'method', chip: (value) => `Method ${value}` },
        { key: 'family', chip: (value) => `Family ${value}` },
    ]);

    // The selects apply at once, as they did before.
    function applyNow(key: string, value: string) {
        form.set(key, value);
        form.apply({ ...form.values, [key]: value });
    }

    const options = (values: string[], label = (value: string) => value) =>
        values.map((value) => ({ value, label: label(value) }));

    return (
        <>
            <Head title="Endpoint catalog" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Endpoint catalog"
                    description="Every operation the ERP service documents. Imported from the vendor documentation; read-only."
                />

                <FilterBar
                    id="endpoints"
                    search={{
                        value: form.values.search!,
                        onChange: (value) => form.set('search', value),
                        placeholder: 'Search name, path, or group',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={Boolean(
                        filters.group || filters.method || filters.family,
                    )}
                >
                    <SelectFilterField
                        id="endpoints-group"
                        label="Group"
                        anyLabel="Any group"
                        options={options(groups, groupLabel)}
                        value={form.values.group!}
                        onChange={(value) => {
                            applyNow('group', value);
                        }}
                        className="w-56"
                    />
                    <SelectFilterField
                        id="endpoints-method"
                        label="Method"
                        anyLabel="Any method"
                        options={options(methods)}
                        value={form.values.method!}
                        onChange={(value) => {
                            applyNow('method', value);
                        }}
                        className="w-36"
                    />
                    <SelectFilterField
                        id="endpoints-family"
                        label="Family"
                        anyLabel="Any family"
                        options={options(families)}
                        value={form.values.family!}
                        onChange={(value) => {
                            applyNow('family', value);
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="ERP operations"
                    columns={columns}
                    rows={endpoints.data}
                    rowKey={(endpoint) => String(endpoint.id)}
                    empty="No operations matched these filters."
                />

                <ListPagination
                    pagination={fromPaginator(endpoints)}
                    noun="operations"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
